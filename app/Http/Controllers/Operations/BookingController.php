<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\MdcCalculation;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/bookings/{index,create,edit}.
 * The month calendar lives in BookingCalendarController.
 */
class BookingController extends Controller
{
    public const STATUSES = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

    public const UNAVAILABLE_MESSAGE = 'This vehicle is not available for the selected date range. Please choose different dates or another vehicle.';

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isDriver = $user->hasRole('driver');

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $date = (string) $request->query('date', '');
        // Set by the "View all" link on a vehicle's page.
        $vehicleId = (string) $request->query('vehicle', '');

        $bookings = Booking::with(['client', 'vehicle.vehicleType', 'driver'])
            // Drivers only see bookings assigned to them
            ->when($isDriver, fn ($q) => $q->where('driver_id', $user->id))
            ->when($search, function ($q) use ($search) {
                // Grouped so the OR terms cannot escape the driver / status filters.
                $q->where(function ($q) use ($search) {
                    $q->where('booking_number', 'like', '%'.$search.'%')
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('company_name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('reg_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($date, function ($q) use ($date) {
                match ($date) {
                    'today' => $q->whereDate('start_date', today()),
                    'upcoming' => $q->where('start_date', '>', now()),
                    'past' => $q->where('end_date', '<', now()),
                    default => null,
                };
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'client' => [
                    'name' => $booking->client?->name,
                    'phone' => $booking->client?->phone,
                    'company_name' => $booking->client?->company_name,
                ],
                'vehicle' => [
                    'reg_number' => $booking->vehicle?->reg_number,
                    'type' => $booking->vehicle?->vehicleType?->name,
                ],
                'driver' => $booking->driver?->name,
                'pickup_location' => $booking->pickup_location,
                'delivery_location' => $booking->delivery_location,
                'distance_km' => $booking->distance_km !== null ? (float) $booking->distance_km : null,
                'load_weight' => $booking->load_weight !== null ? (float) $booking->load_weight : null,
                'cargo_description' => $booking->cargo_description,
                'cargo_excerpt' => $booking->cargo_description ? Str::limit($booking->cargo_description, 30) : null,
                'start_date' => $booking->start_date?->format('Y-m-d'),
                'end_date' => $booking->end_date?->format('Y-m-d'),
            ]);

        $statsQuery = Booking::query();
        if ($isDriver) {
            $statsQuery->where('driver_id', $user->id);
        }

        return Inertia::render('bookings/index', [
            'bookings' => $bookings,
            'stats' => [
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'confirmed' => (clone $statsQuery)->where('status', 'confirmed')->count(),
                'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            ],
            'filters' => ['search' => $search, 'status' => $status, 'date' => $date, 'vehicle' => $vehicleId],
            'filteredVehicle' => $vehicleId ? Vehicle::find($vehicleId)?->reg_number : null,
            'can' => [
                'create' => $user->can('create-bookings'),
                'edit' => $user->can('edit-bookings'),
                'delete' => $user->can('delete-bookings'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('bookings/create', $this->formOptions(
            Vehicle::with('vehicleType')->where('status', 'available')->orderBy('reg_number')->get(),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        // Check vehicle availability for the selected date range
        $vehicle = Vehicle::find($validated['vehicle_id']);
        if ($vehicle && ! $vehicle->isAvailable(Carbon::parse($validated['start_date']), Carbon::parse($validated['end_date']))) {
            return back()->withErrors(['vehicle_id' => self::UNAVAILABLE_MESSAGE])->withInput();
        }

        // Generate booking number
        $lastBooking = Booking::withTrashed()->latest('id')->first(); // include deleted bookings: numbers are unique
        $nextNumber = $lastBooking ? (int) substr($lastBooking->booking_number, 4) + 1 : 1;
        $bookingNumber = 'BKG-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            ...$this->attributes($validated),
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        // Note: Vehicle status is NOT updated here. It changes when the booking goes
        // in_progress (vehicle in_use) or completed/cancelled (vehicle available).

        // Create MDC calculation if distance is provided and vehicle has rate card or GVM
        if (! empty($validated['distance_km'])) {
            $vehicle = Vehicle::with('mdcRateCard')->find($validated['vehicle_id']);
            $effectiveRateCard = $vehicle?->getEffectiveMdcRateCard();

            if ($vehicle && $effectiveRateCard) {
                try {
                    // Calculate MDC: (distance_km / 100) × rate_per_100km
                    $mdcAmount = ($validated['distance_km'] / 100) * $effectiveRateCard->rate_per_100km;

                    MdcCalculation::create([
                        'booking_id' => $booking->id,
                        'vehicle_id' => $vehicle->id,
                        'mdc_rate_card_id' => $effectiveRateCard->id,
                        'gvm_tonnes' => $vehicle->gvm_tonnes,
                        'distance_km' => $validated['distance_km'],
                        'load_weight' => ($validated['load_weight'] ?? null) ?: null,
                        'rate_per_100kg_km' => $effectiveRateCard->rate_per_100km,
                        'mdc_amount' => round($mdcAmount, 2),
                        'calculation_date' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't fail booking creation
                    logger()->error('MDC calculation failed: '.$e->getMessage());
                }
            }
        }

        return redirect()->route('bookings.index')->with('success', 'Booking created successfully!');
    }

    public function edit(Booking $booking): Response
    {
        $vehicles = Vehicle::with('vehicleType')
            ->where(function ($query) use ($booking) {
                $query->where('status', 'available')->orWhere('id', $booking->vehicle_id);
            })
            ->orderBy('reg_number')
            ->get();

        return Inertia::render('bookings/edit', [
            ...$this->formOptions($vehicles),
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'client_id' => $booking->client_id,
                'vehicle_id' => $booking->vehicle_id,
                'driver_id' => $booking->driver_id,
                'status' => $booking->status,
                'start_date' => $booking->start_date?->format('Y-m-d\TH:i'),
                'end_date' => $booking->end_date?->format('Y-m-d\TH:i'),
                'pickup_location' => $booking->pickup_location,
                'delivery_location' => $booking->delivery_location,
                'distance_km' => $booking->distance_km,
                'load_weight' => $booking->load_weight,
                'cargo_description' => $booking->cargo_description,
                'special_instructions' => $booking->special_instructions,
                'notes' => $booking->notes,
                'is_recurring' => (bool) $booking->is_recurring,
                'recurring_frequency' => $booking->recurring_frequency,
                'confirmed_at' => $booking->confirmed_at?->format('d M Y, H:i'),
                'started_at' => $booking->started_at?->format('d M Y, H:i'),
                'completed_at' => $booking->completed_at?->format('d M Y, H:i'),
                'cancelled_at' => $booking->cancelled_at?->format('d M Y, H:i'),
            ],
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
        ]);

        // Check vehicle availability for the selected date range (exclude current booking)
        $vehicle = Vehicle::find($validated['vehicle_id']);
        if ($vehicle && ! $vehicle->isAvailable(Carbon::parse($validated['start_date']), Carbon::parse($validated['end_date']), $booking->id)) {
            return back()->withErrors(['vehicle_id' => self::UNAVAILABLE_MESSAGE])->withInput();
        }

        $originalVehicleId = $booking->vehicle_id;
        $originalStatus = $booking->status;
        $newVehicleId = $validated['vehicle_id'];
        $newStatus = $validated['status'];

        $booking->update([
            ...$this->attributes($validated),
            'status' => $newStatus,
        ]);

        // Handle vehicle status changes based on status transitions
        if ($originalVehicleId != $newVehicleId) {
            // Vehicle was changed
            if ($originalStatus === 'in_progress') {
                // Free up the old vehicle if it was in use
                Vehicle::find($originalVehicleId)->update(['status' => 'available']);
            }

            if ($newStatus === 'in_progress') {
                // Mark new vehicle as in use if booking is in progress
                Vehicle::find($newVehicleId)->update(['status' => 'in_use']);
            }
        } elseif ($originalStatus !== $newStatus) {
            if (in_array($newStatus, ['completed', 'cancelled'])) {
                // Booking ended, free up vehicle
                Vehicle::find($newVehicleId)->update(['status' => 'available']);
            } elseif ($newStatus === 'in_progress' && ! in_array($originalStatus, ['in_progress'])) {
                // Booking started, mark vehicle as in use
                Vehicle::find($newVehicleId)->update(['status' => 'in_use']);
            }
        }

        $success = 'Booking updated successfully!';
        $error = null;

        // Create or update MDC calculation when booking is completed
        if ($newStatus === 'completed' && ! empty($validated['distance_km'])) {
            $vehicle = Vehicle::with('mdcRateCard')->find($newVehicleId);
            $effectiveRateCard = $vehicle?->getEffectiveMdcRateCard();

            if (! $vehicle) {
                $error = 'Vehicle not found. MDC calculation skipped.';
            } elseif (! $effectiveRateCard) {
                $error = 'MDC Rate Card not configured for this vehicle. Please set up MDC rate in vehicle settings.';
            } else {
                try {
                    // Calculate MDC: (distance_km / 100) × rate_per_100km
                    $mdcAmount = ($validated['distance_km'] / 100) * $effectiveRateCard->rate_per_100km;

                    $existingMdc = MdcCalculation::where('booking_id', $booking->id)->first();

                    $mdcData = [
                        'vehicle_id' => $vehicle->id,
                        'mdc_rate_card_id' => $effectiveRateCard->id,
                        'gvm_tonnes' => $vehicle->gvm_tonnes,
                        'distance_km' => $validated['distance_km'],
                        'load_weight' => ($validated['load_weight'] ?? null) ?: null,
                        'rate_per_100kg_km' => $effectiveRateCard->rate_per_100km,
                        'mdc_amount' => round($mdcAmount, 2),
                        'calculation_date' => now(),
                    ];

                    if ($existingMdc) {
                        $existingMdc->update($mdcData);
                        $success .= ' MDC calculation updated: N$'.number_format($mdcAmount, 2);
                    } else {
                        MdcCalculation::create(array_merge(['booking_id' => $booking->id], $mdcData));
                        $success .= ' MDC calculation created: N$'.number_format($mdcAmount, 2);
                    }
                } catch (\Exception $e) {
                    logger()->error('MDC calculation failed: '.$e->getMessage());
                    $error = 'MDC calculation failed: '.$e->getMessage();
                }
            }
        }

        $redirect = redirect()->route('bookings.index')->with('success', $success);

        return $error ? $redirect->with('error', $error) : $redirect;
    }

    /**
     * The status buttons on the bookings list (old updateStatus()).
     * Like the Volt method it only stamps the matching timestamp; it does not
     * touch the vehicle status or re-check availability.
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $status = $validated['status'];

        $timestampField = match ($status) {
            'confirmed' => 'confirmed_at',
            'in_progress' => 'started_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
            default => null,
        };

        $booking->update([
            'status' => $status,
            ...($timestampField ? [$timestampField => now()] : []),
        ]);

        return back()->with('success', 'Booking status updated successfully');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return back()->with('success', 'Booking deleted successfully');
    }

    /**
     * Validation shared by create and edit (the #[Validate] attributes).
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'nullable|string|max:255',
            'delivery_location' => 'nullable|string|max:255',
            'distance_km' => 'nullable|numeric|min:0',
            'load_weight' => 'nullable|numeric|min:0',
            'cargo_description' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        $isRecurring = (bool) ($validated['is_recurring'] ?? false);

        return [
            'client_id' => $validated['client_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => ($validated['driver_id'] ?? null) ?: null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'pickup_location' => $validated['pickup_location'] ?? null,
            'delivery_location' => $validated['delivery_location'] ?? null,
            'distance_km' => ($validated['distance_km'] ?? null) ?: null,
            'load_weight' => ($validated['load_weight'] ?? null) ?: null,
            'cargo_description' => $validated['cargo_description'] ?? null,
            'special_instructions' => $validated['special_instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_recurring' => $isRecurring,
            'recurring_frequency' => $isRecurring ? ($validated['recurring_frequency'] ?? null) : null,
        ];
    }

    /**
     * Select options for the create and edit forms.
     *
     * @param  \Illuminate\Support\Collection<int, Vehicle>  $vehicles
     * @return array<string, mixed>
     */
    private function formOptions($vehicles): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Client $client) => [
                    'value' => $client->id,
                    'label' => $client->name.($client->company_name ? ' ('.$client->company_name.')' : ''),
                ]),
            // tare_weight and base_rate_per_km feed the "Calculate Estimated MDC" button.
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => [
                'value' => $vehicle->id,
                'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name.' ('.$vehicle->make.' '.$vehicle->model.')',
                'tare_weight' => $vehicle->tare_weight !== null ? (float) $vehicle->tare_weight : null,
                'base_rate_per_km' => $vehicle->vehicleType?->base_rate_per_km !== null ? (float) $vehicle->vehicleType->base_rate_per_km : null,
            ])->values(),
            'drivers' => User::role('driver')->orderBy('name')->get()
                ->map(fn (User $driver) => ['value' => $driver->id, 'label' => $driver->name]),
        ];
    }
}
