<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\MdcRateCard;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/vehicles/{index,create,edit,show}.
 */
class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');

        $vehicles = Vehicle::with('vehicleType')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('reg_number', 'like', '%'.$search.'%')
                        ->orWhere('make', 'like', '%'.$search.'%')
                        ->orWhere('model', 'like', '%'.$search.'%');
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'reg_number' => $vehicle->reg_number,
                'type' => $vehicle->vehicleType?->name,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'status' => $vehicle->status,
                'current_mileage' => (float) $vehicle->current_mileage,
                'insurance_expiry' => $vehicle->insurance_expiry?->format('Y-m-d'),
                'insurance_expired' => (bool) $vehicle->insurance_expiry?->isPast(),
            ]);

        return Inertia::render('vehicles/index', [
            'vehicles' => $vehicles,
            'filters' => ['search' => $search, 'status' => $status],
            'can' => [
                'create' => $request->user()->can('create-vehicles'),
                'edit' => $request->user()->can('edit-vehicles'),
                'delete' => $request->user()->can('delete-vehicles'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('vehicles/create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'reg_number' => 'required|unique:vehicles,reg_number',
            'vin' => 'nullable|unique:vehicles,vin',
        ]);

        $vehicle = Vehicle::create($this->attributes($validated));
        $this->storeUploads($request, $vehicle);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle created successfully!');
    }

    public function show(Vehicle $vehicle): Response
    {
        // TODO(module: vehicles show) — port livewire/vehicles/show.blade.php
        abort(501);
    }

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('vehicles/edit', [
            ...$this->formOptions(),
            'vehicle' => [
                'id' => $vehicle->id,
                'vehicle_type_id' => $vehicle->vehicle_type_id,
                'reg_number' => $vehicle->reg_number,
                'vin' => $vehicle->vin,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'load_capacity' => $vehicle->load_capacity,
                'tare_weight' => $vehicle->tare_weight,
                'gvm_tonnes' => $vehicle->gvm_tonnes,
                'mdc_rate_card_id' => $vehicle->mdc_rate_card_id,
                'insurance_expiry' => $vehicle->insurance_expiry?->format('Y-m-d'),
                'disc_expiry' => $vehicle->disc_expiry?->format('Y-m-d'),
                'roadworthy_expiry' => $vehicle->roadworthy_expiry?->format('Y-m-d'),
                'status' => $vehicle->status,
                'current_mileage' => $vehicle->current_mileage,
                'gps_device_id' => $vehicle->gps_device_id,
                'next_service_date' => $vehicle->next_service_date?->format('Y-m-d'),
                'next_service_mileage' => $vehicle->next_service_mileage,
                'notes' => $vehicle->notes,
                'license_disc_url' => $vehicle->license_disc_path ? Storage::url($vehicle->license_disc_path) : null,
                'insurance_url' => $vehicle->insurance_path ? Storage::url($vehicle->insurance_path) : null,
            ],
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        // Same rules the old edit screen used (no unique check on reg number / VIN).
        $validated = $request->validate($this->rules());

        $vehicle->update($this->attributes($validated));
        $this->storeUploads($request, $vehicle);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully!');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return back()->with('success', 'Vehicle deleted successfully');
    }

    /**
     * Validation shared by create and edit.
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'vehicle_type_id' => 'required',
            'reg_number' => 'required',
            'vin' => 'nullable',
            'make' => 'required',
            'model' => 'required',
            'year' => 'nullable|integer|min:1900|max:2100',
            'load_capacity' => 'nullable|numeric|min:0',
            'tare_weight' => 'nullable|numeric|min:0',
            'gvm_tonnes' => 'nullable|numeric|min:0',
            'mdc_rate_card_id' => 'nullable|exists:mdc_rate_cards,id',
            'insurance_expiry' => 'nullable|date',
            'disc_expiry' => 'nullable|date',
            'roadworthy_expiry' => 'nullable|date',
            'status' => 'required',
            'current_mileage' => 'nullable|numeric|min:0',
            'gps_device_id' => 'nullable',
            'license_disc_upload' => 'nullable|image|max:2048',
            'insurance_upload' => 'nullable|image|max:2048',
            'next_service_date' => 'nullable|date',
            'next_service_mileage' => 'nullable|integer|min:0',
            'notes' => 'nullable',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        $fields = collect($validated)->except(['license_disc_upload', 'insurance_upload'])->all();

        return [
            ...$fields,
            'gvm_tonnes' => ($validated['gvm_tonnes'] ?? null) ?: null,
            'mdc_rate_card_id' => ($validated['mdc_rate_card_id'] ?? null) ?: null,
            'current_mileage' => ($validated['current_mileage'] ?? null) ?: 0,
        ];
    }

    private function storeUploads(Request $request, Vehicle $vehicle): void
    {
        if ($request->hasFile('license_disc_upload')) {
            $vehicle->update(['license_disc_path' => $request->file('license_disc_upload')->store('vehicles/license-discs', 'public')]);
        }

        if ($request->hasFile('insurance_upload')) {
            $vehicle->update(['insurance_path' => $request->file('insurance_upload')->store('vehicles/insurance', 'public')]);
        }
    }

    /**
     * Select options for the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $today = now()->toDateString();

        return [
            'vehicleTypes' => VehicleType::all()->map(fn ($type) => ['value' => $type->id, 'label' => $type->name]),
            // The page picks the suggested rate for the GVM entered, like getActiveRateForGvm().
            'mdcRateCards' => MdcRateCard::where('is_active', true)
                ->orderBy('min_gvm_tonnes')
                ->get()
                ->map(fn (MdcRateCard $card) => [
                    'id' => $card->id,
                    'category_name' => $card->category_name,
                    'rate_per_100km' => (float) $card->rate_per_100km,
                    'min_gvm_tonnes' => (float) $card->min_gvm_tonnes,
                    'max_gvm_tonnes' => $card->max_gvm_tonnes !== null ? (float) $card->max_gvm_tonnes : null,
                    'effective_from' => $card->effective_from?->toDateString(),
                    'in_effect' => $card->effective_from !== null
                        && $card->effective_from->toDateString() <= $today
                        && ($card->effective_to === null || $card->effective_to->toDateString() >= $today),
                ]),
        ];
    }
}
