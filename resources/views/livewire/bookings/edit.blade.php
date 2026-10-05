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

    public Booking $booking;

    #[Validate('required|exists:clients,id')]
    public $client_id;

    #[Validate('required|exists:vehicles,id')]
    public $vehicle_id;

    #[Validate('nullable|exists:users,id')]
    public $driver_id;

    #[Validate('required|date')]
    public $start_date;

    #[Validate('required|date|after:start_date')]
    public $end_date;

    #[Validate('nullable|string|max:255')]
    public $pickup_location;

    #[Validate('nullable|string|max:255')]
    public $delivery_location;

    #[Validate('nullable|numeric|min:0')]
    public $distance_km;

    #[Validate('nullable|numeric|min:0')]
    public $load_weight;

    #[Validate('nullable|string')]
    public $cargo_description;

    #[Validate('nullable|string')]
    public $special_instructions;

    #[Validate('nullable|string')]
    public $notes;

    #[Validate('boolean')]
    public $is_recurring;

    #[Validate('nullable|string')]
    public $recurring_frequency;

    #[Validate('required|in:pending,confirmed,in_progress,completed,cancelled')]
    public $status;

    public function mount(Booking $booking)
    {
        $this->booking = $booking;

        $this->client_id = $this->booking->client_id;
        $this->vehicle_id = $this->booking->vehicle_id;
        $this->driver_id = $this->booking->driver_id;
        $this->start_date = $this->booking->start_date?->format('Y-m-d\TH:i');
        $this->end_date = $this->booking->end_date?->format('Y-m-d\TH:i');
        $this->pickup_location = $this->booking->pickup_location;
        $this->delivery_location = $this->booking->delivery_location;
        $this->distance_km = $this->booking->distance_km;
        $this->load_weight = $this->booking->load_weight;
        $this->cargo_description = $this->booking->cargo_description;
        $this->special_instructions = $this->booking->special_instructions;
        $this->notes = $this->booking->notes;
        $this->is_recurring = $this->booking->is_recurring;
        $this->recurring_frequency = $this->booking->recurring_frequency;
        $this->status = $this->booking->status;
    }

    public function with(): array
    {
        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::with('vehicleType')
                ->where(function($query) {
                    $query->where('status', 'available')
                          ->orWhere('id', $this->booking->vehicle_id);
                })
                ->orderBy('reg_number')
                ->get(),
            'drivers' => User::role('driver')->orderBy('name')->get(),
        ];
    }

    public function save()
    {
        $this->validate();

        // Check vehicle availability for the selected date range (exclude current booking)
        if ($this->vehicle_id && $this->start_date && $this->end_date) {
            $vehicle = Vehicle::find($this->vehicle_id);
            if ($vehicle && !$vehicle->isAvailable($this->start_date, $this->end_date, $this->booking->id)) {
                $this->addError('vehicle_id', 'This vehicle is not available for the selected date range. Please choose different dates or another vehicle.');
                return;
            }
        }

        $originalVehicleId = $this->booking->vehicle_id;
        $originalStatus = $this->booking->status;

        $this->booking->update([
            'client_id' => $this->client_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id ?: null,
            'status' => $this->status,
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
        ]);

        // Handle vehicle status changes based on status transitions
        if ($originalVehicleId != $this->vehicle_id) {
            // Vehicle was changed
            if ($originalStatus === 'in_progress') {
                // Free up the old vehicle if it was in use
                Vehicle::find($originalVehicleId)->update(['status' => 'available']);
            }
            
            if ($this->status === 'in_progress') {
                // Mark new vehicle as in use if booking is in progress
                Vehicle::find($this->vehicle_id)->update(['status' => 'in_use']);
            }
        } else {
            // Same vehicle, check if status changed
            if ($originalStatus !== $this->status) {
                if (in_array($this->status, ['completed', 'cancelled'])) {
                    // Booking ended, free up vehicle
                    Vehicle::find($this->vehicle_id)->update(['status' => 'available']);
                } elseif ($this->status === 'in_progress' && !in_array($originalStatus, ['in_progress'])) {
                    // Booking started, mark vehicle as in use
                    Vehicle::find($this->vehicle_id)->update(['status' => 'in_use']);
                }
            }
        }

        // Create or update MDC calculation when booking is completed
        if ($this->status === 'completed' && $this->distance_km && $this->vehicle_id) {
            $vehicle = Vehicle::with('mdcRateCard')->find($this->vehicle_id);
            
            // Get the effective rate card (linked or auto-suggested from GVM)
            $effectiveRateCard = $vehicle?->getEffectiveMdcRateCard();
            
            if (!$vehicle) {
                $this->dispatch('notify',
                    type: 'warning',
                    message: 'Vehicle not found. MDC calculation skipped.'
                );
            } elseif (!$effectiveRateCard) {
                $this->dispatch('notify',
                    type: 'warning',
                    message: 'MDC Rate Card not configured for this vehicle. Please set up MDC rate in vehicle settings.'
                );
            } else {
                try {
                    // Calculate MDC: (distance_km / 100) × rate_per_100km
                    $mdcAmount = ($this->distance_km / 100) * $effectiveRateCard->rate_per_100km;

                    // Check if MDC calculation already exists for this booking
                    $existingMdc = MdcCalculation::where('booking_id', $this->booking->id)->first();

                    $mdcData = [
                        'vehicle_id' => $vehicle->id,
                        'mdc_rate_card_id' => $effectiveRateCard->id,
                        'gvm_tonnes' => $vehicle->gvm_tonnes,
                        'distance_km' => $this->distance_km,
                        'load_weight' => $this->load_weight ?: null,
                        'rate_per_100kg_km' => $effectiveRateCard->rate_per_100km,
                        'mdc_amount' => round($mdcAmount, 2),
                        'calculation_date' => now(),
                    ];

                    if ($existingMdc) {
                        // Update existing MDC calculation
                        $existingMdc->update($mdcData);
                        $this->dispatch('notify',
                            type: 'success',
                            message: 'MDC calculation updated: N$' . number_format($mdcAmount, 2)
                        );
                    } else {
                        // Create new MDC calculation
                        MdcCalculation::create(array_merge(['booking_id' => $this->booking->id], $mdcData));
                        $this->dispatch('notify',
                            type: 'success',
                            message: 'MDC calculation created: N$' . number_format($mdcAmount, 2)
                        );
                    }
                } catch (\Exception $e) {
                    // Log error and notify user
                    logger()->error('MDC calculation failed: ' . $e->getMessage());
                    $this->dispatch('notify',
                        type: 'error',
                        message: 'MDC calculation failed: ' . $e->getMessage()
                    );
                }
            }
        }

        $this->dispatch('notify',
            type: 'success',
            message: 'Booking updated successfully!'
        );

        return redirect()->route('bookings.index');
    }

    public function calculateEstimatedMdc()
    {
        if ($this->vehicle_id && $this->distance_km && $this->load_weight) {
            $vehicle = Vehicle::with('vehicleType')->find($this->vehicle_id);
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
        <flux:heading size="xl">Edit Booking: {{ $booking->booking_number }}</flux:heading>

        <div class="flex items-center gap-2">
            <flux:badge
                :color="match($booking->status) {
                    'pending' => 'yellow',
                    'confirmed' => 'blue',
                    'in_progress' => 'purple',
                    'completed' => 'green',
                    'cancelled' => 'red',
                    default => 'gray'
                }"
            >
                {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
            </flux:badge>
        </div>
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
                                @if($vehicle->id == $booking->vehicle_id)
                                    [Current]
                                @endif
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_id" />
                    <flux:description>Available vehicles + current vehicle shown</flux:description>
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
                </flux:field>

                <flux:field>
                    <flux:label>Booking Status *</flux:label>
                    <flux:select wire:model="status">
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </flux:select>
                    <flux:error name="status" />
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

        @if($booking->confirmed_at || $booking->started_at || $booking->completed_at || $booking->cancelled_at)
            <flux:card>
                <flux:heading size="lg">Status Timestamps</flux:heading>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($booking->confirmed_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Confirmed At</p>
                            <p class="text-sm text-gray-600">{{ $booking->confirmed_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif

                    @if($booking->started_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Started At</p>
                            <p class="text-sm text-gray-600">{{ $booking->started_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif

                    @if($booking->completed_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Completed At</p>
                            <p class="text-sm text-gray-600">{{ $booking->completed_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif

                    @if($booking->cancelled_at)
                        <div>
                            <p class="text-sm font-medium text-gray-700">Cancelled At</p>
                            <p class="text-sm text-gray-600">{{ $booking->cancelled_at->format('d M Y, H:i') }}</p>
                        </div>
                    @endif
                </div>
            </flux:card>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Booking
            </flux:button>

            <flux:button wire:navigate href="{{ route('bookings.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
