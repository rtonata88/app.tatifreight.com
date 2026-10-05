<?php

use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\MdcRateCard;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    #[Validate('required')]
    public $vehicle_type_id = '';

    #[Validate('required|unique:vehicles,reg_number')]
    public $reg_number = '';

    #[Validate('nullable|unique:vehicles,vin')]
    public $vin = '';

    #[Validate('required')]
    public $make = '';

    #[Validate('required')]
    public $model = '';

    #[Validate('nullable|integer|min:1900|max:2100')]
    public $year = '';

    #[Validate('nullable|numeric|min:0')]
    public $load_capacity = '';

    #[Validate('nullable|numeric|min:0')]
    public $tare_weight = '';

    #[Validate('nullable|numeric|min:0')]
    public $gvm_tonnes = '';

    #[Validate('nullable|exists:mdc_rate_cards,id')]
    public $mdc_rate_card_id = '';

    public $suggested_mdc_rate = null;

    #[Validate('nullable|date')]
    public $insurance_expiry = '';

    #[Validate('nullable|date')]
    public $disc_expiry = '';

    #[Validate('nullable|date')]
    public $roadworthy_expiry = '';

    #[Validate('required')]
    public $status = 'available';

    #[Validate('nullable|numeric|min:0')]
    public $current_mileage = 0;

    #[Validate('nullable')]
    public $gps_device_id = '';

    #[Validate('nullable|image|max:2048')]
    public $license_disc_upload = null;

    #[Validate('nullable|image|max:2048')]
    public $insurance_upload = null;

    #[Validate('nullable|date')]
    public $next_service_date = '';

    #[Validate('nullable|integer|min:0')]
    public $next_service_mileage = '';

    #[Validate('nullable')]
    public $notes = '';

    public function with(): array
    {
        return [
            'vehicleTypes' => VehicleType::all(),
            'mdcRateCards' => MdcRateCard::where('is_active', true)
                ->orderBy('min_gvm_tonnes')
                ->get(),
        ];
    }

    public function updatedGvmTonnes($value)
    {
        if ($value) {
            $this->suggested_mdc_rate = MdcRateCard::getActiveRateForGvm((float)$value);
            
            // Auto-select the suggested rate if no rate is manually selected
            if (!$this->mdc_rate_card_id && $this->suggested_mdc_rate) {
                $this->mdc_rate_card_id = $this->suggested_mdc_rate->id;
            }
        } else {
            $this->suggested_mdc_rate = null;
        }
    }

    public function save()
    {
        $this->validate();

        $vehicle = Vehicle::create([
            'vehicle_type_id' => $this->vehicle_type_id,
            'reg_number' => $this->reg_number,
            'vin' => $this->vin,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'load_capacity' => $this->load_capacity,
            'tare_weight' => $this->tare_weight,
            'gvm_tonnes' => $this->gvm_tonnes ?: null,
            'mdc_rate_card_id' => $this->mdc_rate_card_id ?: null,
            'insurance_expiry' => $this->insurance_expiry,
            'disc_expiry' => $this->disc_expiry,
            'roadworthy_expiry' => $this->roadworthy_expiry,
            'status' => $this->status,
            'current_mileage' => $this->current_mileage ?: 0,
            'gps_device_id' => $this->gps_device_id,
            'next_service_date' => $this->next_service_date,
            'next_service_mileage' => $this->next_service_mileage,
            'notes' => $this->notes,
        ]);

        // Handle file uploads
        if ($this->license_disc_upload) {
            $path = $this->license_disc_upload->store('vehicles/license-discs', 'public');
            $vehicle->update(['license_disc_path' => $path]);
        }

        if ($this->insurance_upload) {
            $path = $this->insurance_upload->store('vehicles/insurance', 'public');
            $vehicle->update(['insurance_path' => $path]);
        }

        session()->flash('success', 'Vehicle created successfully!');

        return redirect()->route('vehicles.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Add New Vehicle</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Basic Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Vehicle Type *</flux:label>
                    <flux:select wire:model="vehicle_type_id" placeholder="Select vehicle type">
                        @foreach($vehicleTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_type_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Registration Number *</flux:label>
                    <flux:input wire:model="reg_number" placeholder="e.g., ABC 123 GP" />
                    <flux:error name="reg_number" />
                </flux:field>

                <flux:field>
                    <flux:label>VIN</flux:label>
                    <flux:input wire:model="vin" placeholder="Vehicle Identification Number" />
                    <flux:error name="vin" />
                </flux:field>

                <flux:field>
                    <flux:label>Make *</flux:label>
                    <flux:input wire:model="make" placeholder="e.g., Mercedes-Benz" />
                    <flux:error name="make" />
                </flux:field>

                <flux:field>
                    <flux:label>Model *</flux:label>
                    <flux:input wire:model="model" placeholder="e.g., Actros" />
                    <flux:error name="model" />
                </flux:field>

                <flux:field>
                    <flux:label>Year</flux:label>
                    <flux:input wire:model="year" type="number" placeholder="e.g., 2023" />
                    <flux:error name="year" />
                </flux:field>

                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="available">Available</option>
                        <option value="in_use">In Use</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="retired">Retired</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>Current Mileage (km)</flux:label>
                    <flux:input wire:model="current_mileage" type="number" step="0.01" />
                    <flux:error name="current_mileage" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Specifications</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Load Capacity (tons)</flux:label>
                    <flux:input wire:model="load_capacity" type="number" step="0.01" />
                    <flux:error name="load_capacity" />
                </flux:field>

                <flux:field>
                    <flux:label>Tare Weight (tons)</flux:label>
                    <flux:input wire:model="tare_weight" type="number" step="0.01" />
                    <flux:error name="tare_weight" />
                </flux:field>

                <flux:field>
                    <flux:label>GPS Device ID</flux:label>
                    <flux:input wire:model="gps_device_id" placeholder="Optional tracking device ID" />
                    <flux:error name="gps_device_id" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">MDC (Mass Distance Charges)</flux:heading>
            <flux:description>Configure RFANAM Mass Distance Charges for this vehicle</flux:description>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>GVM - Gross Vehicle Mass (tonnes)</flux:label>
                    <flux:input wire:model.live="gvm_tonnes" type="number" step="0.01" placeholder="e.g., 34.5" />
                    <flux:error name="gvm_tonnes" />
                    <flux:description>The total permissible weight of the vehicle including load</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>MDC Rate Card</flux:label>
                    <flux:select wire:model="mdc_rate_card_id" placeholder="Select MDC rate card">
                        @foreach($mdcRateCards as $rateCard)
                            <option value="{{ $rateCard->id }}">
                                {{ $rateCard->category_name }} - N${{ number_format($rateCard->rate_per_100km, 2) }}/100km
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="mdc_rate_card_id" />
                    @if($suggested_mdc_rate)
                        <flux:description>
                            💡 Suggested based on GVM: {{ $suggested_mdc_rate->category_name }} 
                            (N${{ number_format($suggested_mdc_rate->rate_per_100km, 2) }}/100km)
                        </flux:description>
                    @else
                        <flux:description>Enter GVM above to get rate suggestion</flux:description>
                    @endif
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Compliance & Expiry Dates</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Insurance Expiry</flux:label>
                    <flux:input wire:model="insurance_expiry" type="date" />
                    <flux:error name="insurance_expiry" />
                </flux:field>

                <flux:field>
                    <flux:label>License Disc Expiry</flux:label>
                    <flux:input wire:model="disc_expiry" type="date" />
                    <flux:error name="disc_expiry" />
                </flux:field>

                <flux:field>
                    <flux:label>Roadworthy Expiry</flux:label>
                    <flux:input wire:model="roadworthy_expiry" type="date" />
                    <flux:error name="roadworthy_expiry" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Document Uploads</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>License Disc (Image)</flux:label>
                    <input type="file" wire:model="license_disc_upload" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    <flux:error name="license_disc_upload" />
                    @if ($license_disc_upload)
                        <div class="mt-2">
                            <img src="{{ $license_disc_upload->temporaryUrl() }}" class="h-32 rounded border">
                        </div>
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label>Insurance Certificate (Image)</flux:label>
                    <input type="file" wire:model="insurance_upload" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    <flux:error name="insurance_upload" />
                    @if ($insurance_upload)
                        <div class="mt-2">
                            <img src="{{ $insurance_upload->temporaryUrl() }}" class="h-32 rounded border">
                        </div>
                    @endif
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Service Schedule</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Next Service Date</flux:label>
                    <flux:input wire:model="next_service_date" type="date" />
                    <flux:error name="next_service_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Next Service Mileage (km)</flux:label>
                    <flux:input wire:model="next_service_mileage" type="number" />
                    <flux:error name="next_service_mileage" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="4" placeholder="Additional information about this vehicle..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Vehicle
            </flux:button>

            <flux:button wire:navigate href="{{ route('vehicles.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
