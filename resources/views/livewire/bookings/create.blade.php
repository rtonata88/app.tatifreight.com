<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\MdcCalculation;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    #[Validate('required|exists:clients,id')]
    public $client_id = '';

    #[Validate('required|exists:vehicles,id')]
    public $vehicle_id = '';

    #[Validate('nullable|exists:users,id')]
    public $driver_id = '';

    #[Validate('required|date')]
    public $start_date = '';

    #[Validate('required|date|after:start_date')]
    public $end_date = '';

    #[Validate('nullable|string|max:255')]
    public $pickup_location = '';

    #[Validate('nullable|string|max:255')]
    public $delivery_location = '';

    #[Validate('nullable|numeric|min:0')]
    public $distance_km = '';

    #[Validate('nullable|numeric|min:0')]
    public $load_weight = '';

    #[Validate('nullable|string')]
    public $cargo_description = '';

    #[Validate('nullable|string')]
    public $special_instructions = '';

    #[Validate('nullable|string')]
    public $notes = '';

    #[Validate('boolean')]
    public $is_recurring = false;

    #[Validate('nullable|string')]
    public $recurring_frequency = '';

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')
                ->where('status', 'available')
                ->orderBy('reg_number')
                ->get(),
            'drivers' => User::role('driver')->orderBy('name')->get(),
        ];
    }

    public function save()
    {
        $this->validate();

        // Check vehicle availability for the selected date range
        if ($this->vehicle_id && $this->start_date && $this->end_date) {
            $vehicle = Vehicle::find($this->vehicle_id);
            if ($vehicle && !$vehicle->isAvailable($this->start_date, $this->end_date)) {
                $this->addError('vehicle_id', 'This vehicle is not available for the selected date range. Please choose different dates or another vehicle.');
                return;
            }
        }

        // Generate booking number
        $lastBooking = Booking::latest('id')->first();
        $nextNumber = $lastBooking ? (int)substr($lastBooking->booking_number, 4) + 1 : 1;
        $bookingNumber = 'BKG-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'client_id' => $this->client_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id ?: null,
            'status' => 'pending',
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'pickup_location' => $this->pickup_location,
            'delivery_location' => $this->delivery_location,
            'distance_km' => $this->distance_km ?: null,
            'load_weight' => $this->load_weight ?: null,
            'cargo_description' => $this->cargo_description,
            'special_instructions' => $this->special_instructions,
            'notes' => $this->notes,
            'is_recurring' => $this->is_recurring,
            'recurring_frequency' => $this->is_recurring ? $this->recurring_frequency : null,
            'created_by' => auth()->id(),
        ]);

        // Note: Vehicle status is NOT updated here. It will be updated when:
        // - Booking status changes to 'in_progress' (vehicle becomes 'in_use')
        // - Booking status changes to 'completed' or 'cancelled' (vehicle becomes 'available')
        // This allows future bookings without immediately blocking the vehicle

        // Create MDC calculation if distance is provided and vehicle has rate card or GVM
        if ($this->distance_km && $this->vehicle_id) {
            $vehicle = Vehicle::with('mdcRateCard')->find($this->vehicle_id);
            
            // Get the effective rate card (linked or auto-suggested from GVM)
            $effectiveRateCard = $vehicle?->getEffectiveMdcRateCard();
            
            if ($vehicle && $effectiveRateCard) {
                try {
                    // Calculate MDC: (distance_km / 100) × rate_per_100km
                    $mdcAmount = ($this->distance_km / 100) * $effectiveRateCard->rate_per_100km;

                    MdcCalculation::create([
                        'booking_id' => $booking->id,
                        'vehicle_id' => $vehicle->id,
                        'mdc_rate_card_id' => $effectiveRateCard->id,
                        'gvm_tonnes' => $vehicle->gvm_tonnes,
                        'distance_km' => $this->distance_km,
                        'load_weight' => $this->load_weight ?: null,
                        'rate_per_100kg_km' => $effectiveRateCard->rate_per_100km,
                        'mdc_amount' => round($mdcAmount, 2),
                        'calculation_date' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Log error but don't fail booking creation
                    logger()->error('MDC calculation failed: ' . $e->getMessage());
                }
            }
        }

        $this->dispatch('notify',
            type: 'success',
            message: 'Booking created successfully!'
        );

        return redirect()->route('bookings.index');
    }

    public function updatedVehicleId($value)
    {
        if ($value) {
            $vehicle = Vehicle::with('vehicleType')->find($value);
            if ($vehicle && $vehicle->tare_weight) {
                // You could pre-fill or suggest values based on vehicle
            }
        }
    }

    public function calculateEstimatedMdc()
    {
        if ($this->vehicle_id && $this->distance_km && $this->load_weight) {
            $vehicle = Vehicle::find($this->vehicle_id);
            if ($vehicle && $vehicle->tare_weight && $vehicle->vehicleType->base_rate_per_km) {
                $totalMass = $vehicle->tare_weight + $this->load_weight;
                $mdcEstimate = ($totalMass * $this->distance_km * $vehicle->vehicleType->base_rate_per_km) / 100;

                $this->dispatch('notify',
                    type: 'info',
                    message: 'Estimated MDC: R' . number_format($mdcEstimate, 2)
                );
            }
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Create New Booking</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Client & Vehicle Selection</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Client *</flux:label>
                    <flux:select wire:model="client_id">
                        <option value="">Select a client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->name }}
                                @if($client->company_name)
                                    ({{ $client->company_name }})
                                @endif
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle *</flux:label>
                    <flux:select wire:model.live="vehicle_id">
                        <option value="">Select a vehicle</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                                ({{ $vehicle->make }} {{ $vehicle->model }})
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_id" />
                    <flux:description>Only available vehicles are shown</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Assign Driver</flux:label>
                    <flux:select wire:model="driver_id">
                        <option value="">Select a driver (optional)</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="driver_id" />
                    <flux:description>You can assign a driver now or later</flux:description>
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Booking Dates</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Start Date & Time *</flux:label>
                    <flux:input wire:model="start_date" type="datetime-local" />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>End Date & Time *</flux:label>
                    <flux:input wire:model="end_date" type="datetime-local" />
                    <flux:error name="end_date" />
                </flux:field>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Recurring Booking</flux:label>
                    <flux:checkbox wire:model.live="is_recurring">
                        This is a recurring booking
                    </flux:checkbox>
                    <flux:error name="is_recurring" />
                </flux:field>

                @if($is_recurring)
                    <flux:field>
                        <flux:label>Recurring Frequency</flux:label>
                        <flux:select wire:model="recurring_frequency">
                            <option value="">Select frequency</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </flux:select>
                        <flux:error name="recurring_frequency" />
                    </flux:field>
                @endif
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Trip Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Pickup Location</flux:label>
                    <flux:input wire:model="pickup_location" placeholder="Enter pickup address" />
                    <flux:error name="pickup_location" />
                </flux:field>

                <flux:field>
                    <flux:label>Delivery Location</flux:label>
                    <flux:input wire:model="delivery_location" placeholder="Enter delivery address" />
                    <flux:error name="delivery_location" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Distance (km)</flux:label>
                        <flux:input wire:model.blur="distance_km" type="number" step="0.01" placeholder="0.00" />
                        <flux:error name="distance_km" />
                        <flux:description>Total distance for the trip</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Load Weight (tons)</flux:label>
                        <flux:input wire:model.blur="load_weight" type="number" step="0.01" placeholder="0.00" />
                        <flux:error name="load_weight" />
                        <flux:description>Weight of cargo to be transported</flux:description>
                    </flux:field>
                </div>

                @if($vehicle_id && $distance_km && $load_weight)
                    <div class="flex items-center gap-2">
                        <flux:button
                            type="button"
                            wire:click="calculateEstimatedMdc"
                            variant="ghost"
                            size="sm"
                        >
                            Calculate Estimated MDC
                        </flux:button>
                    </div>
                @endif

                <flux:field>
                    <flux:label>Cargo Description</flux:label>
                    <flux:textarea wire:model="cargo_description" rows="3" placeholder="Describe the cargo being transported..." />
                    <flux:error name="cargo_description" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Special Instructions</flux:label>
                    <flux:textarea wire:model="special_instructions" rows="3" placeholder="Any special handling instructions or requirements..." />
                    <flux:error name="special_instructions" />
                </flux:field>

                <flux:field>
                    <flux:label>Internal Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Internal notes (not visible to client)..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Booking
            </flux:button>

            <flux:button wire:navigate href="{{ route('bookings.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
