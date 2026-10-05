<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Logbook;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/logbook/{index,create,edit}.
 *
 * Saving a logbook entry creates/updates its MDC calculation and the vehicle's
 * mileage through the Logbook model events (unchanged).
 */
class LogbookController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $vehicleFilter = (string) $request->query('vehicle', '');
        // The old screen opened on the current month. Once the user has changed a filter the
        // page sends `filtered=1`, so a date they cleared stays cleared.
        $touched = $request->boolean('filtered');
        $dateFrom = $touched ? (string) $request->query('dateFrom', '') : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $touched ? (string) $request->query('dateTo', '') : now()->endOfMonth()->format('Y-m-d');

        $logbooks = Logbook::with(['vehicle.vehicleType', 'driver', 'booking'])
            // Kept as in the old screen: the search conditions are not grouped, so
            // their orWhere clauses are OR-ed with the vehicle/date filters below.
            ->when($search, function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vehicleQuery) use ($search) {
                    $vehicleQuery->where('reg_number', 'like', '%'.$search.'%');
                })
                    ->orWhereHas('driver', function ($driverQuery) use ($search) {
                        $driverQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhere('origin_from', 'like', '%'.$search.'%')
                    ->orWhere('origin_to', 'like', '%'.$search.'%');
            })
            ->when($vehicleFilter, fn ($q) => $q->where('vehicle_id', $vehicleFilter))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Logbook $logbook) => [
                'id' => $logbook->id,
                'date' => $logbook->date?->format('Y-m-d'),
                'vehicle_reg' => $logbook->vehicle?->reg_number,
                'vehicle_type' => $logbook->vehicle?->vehicleType?->name,
                'driver' => $logbook->driver?->name,
                'origin_from' => $logbook->origin_from,
                'origin_to' => $logbook->origin_to,
                'start_odometer' => (float) $logbook->start_odometer,
                'end_odometer' => $logbook->end_odometer ? (float) $logbook->end_odometer : null,
                'distance' => (float) $logbook->distance_travelled,
            ]);

        // Summary statistics use the vehicle and date filters only (not the search), as before.
        $summary = Logbook::query()
            ->when($vehicleFilter, fn ($q) => $q->where('vehicle_id', $vehicleFilter))
            ->when($dateFrom, fn ($q) => $q->where('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('date', '<=', $dateTo))
            ->get();

        $user = $request->user();

        return Inertia::render('logbook/index', [
            'logbooks' => $logbooks,
            'vehicles' => $this->vehicleOptions(),
            'totalDistance' => (float) $summary->sum(fn (Logbook $logbook) => $logbook->distance_travelled),
            'totalTrips' => $summary->count(),
            'filters' => [
                'search' => $search,
                'vehicle' => $vehicleFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ],
            // The old screen showed these buttons for the *-vehicles permissions; the routes
            // require the *-logbook permissions, so a button only shows when both allow it.
            'can' => [
                'create' => $user->can('create-vehicles') && $user->can('create-logbook'),
                'edit' => $user->can('edit-vehicles') && $user->can('edit-logbook'),
                'delete' => $user->can('delete-vehicles') && $user->can('delete-logbook'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('logbook/create', [
            ...$this->formOptions(),
            'defaults' => ['date' => now()->format('Y-m-d')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $logbook = Logbook::create([
            ...$this->attributes($validated),
            'created_by' => $request->user()->id,
        ]);

        // MDC calculation is created by the model event; reload to read it.
        $logbook->refresh();
        $mdc = $logbook->mdcCalculation;

        $message = 'Logbook entry created successfully!';
        $message .= $mdc
            ? ' MDC charge calculated: N$'.number_format($mdc->mdc_amount, 2)
            : ' Note: MDC charge could not be calculated (check vehicle GVM and rate cards).';

        return redirect()->route('logbook.index')->with('success', $message);
    }

    public function edit(Logbook $logbook): Response
    {
        return Inertia::render('logbook/edit', [
            ...$this->formOptions(),
            'logbook' => [
                'id' => $logbook->id,
                'vehicle_id' => $logbook->vehicle_id,
                'driver_id' => $logbook->driver_id,
                'booking_id' => $logbook->booking_id,
                'date' => $logbook->date?->format('Y-m-d'),
                'start_odometer' => $logbook->start_odometer,
                'end_odometer' => $logbook->end_odometer,
                'origin_from' => $logbook->origin_from,
                'origin_to' => $logbook->origin_to,
                'purpose' => $logbook->purpose,
                'notes' => $logbook->notes,
            ],
        ]);
    }

    public function update(Request $request, Logbook $logbook): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $logbook->update($this->attributes($validated));

        // MDC calculation is updated by the model event; reload to read it.
        $logbook->refresh();
        $mdc = $logbook->mdcCalculation;

        $message = 'Logbook entry updated successfully!';
        $message .= $mdc
            ? ' MDC charge updated: N$'.number_format($mdc->mdc_amount, 2)
            : ' Note: MDC charge could not be calculated (check vehicle GVM and rate cards).';

        return redirect()->route('logbook.index')->with('success', $message);
    }

    public function destroy(Request $request, Logbook $logbook): RedirectResponse
    {
        // The old deleteLogbook() only acted for users who can delete vehicles.
        if (! $request->user()->can('delete-vehicles')) {
            return back();
        }

        $logbook->delete();

        return back()->with('success', 'Logbook entry deleted successfully');
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'date' => 'required|date',
            'start_odometer' => 'required|numeric|min:0',
            'end_odometer' => 'required|numeric|min:0|gte:start_odometer',
            'origin_from' => 'required|string|max:255',
            'origin_to' => 'required|string|max:255',
            'purpose' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'],
            'booking_id' => ($validated['booking_id'] ?? null) ?: null,
            'date' => $validated['date'],
            'start_odometer' => $validated['start_odometer'],
            'end_odometer' => $validated['end_odometer'],
            'origin_from' => $validated['origin_from'],
            'origin_to' => $validated['origin_to'],
            'purpose' => $validated['purpose'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: int, label: string}>
     */
    private function vehicleOptions()
    {
        return Vehicle::with('vehicleType')->orderBy('reg_number')->get()
            ->map(fn (Vehicle $vehicle) => [
                'value' => $vehicle->id,
                'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name,
            ]);
    }

    /**
     * Select options for the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'vehicles' => $this->vehicleOptions(),
            'drivers' => User::role('driver')->orderBy('name')->get()
                ->map(fn (User $driver) => ['value' => $driver->id, 'label' => $driver->name]),
            'bookings' => Booking::with(['client', 'vehicle'])
                ->where('status', 'in_progress')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn (Booking $booking) => [
                    'value' => $booking->id,
                    'label' => $booking->booking_number.' - '.$booking->client?->name,
                ]),
        ];
    }
}
