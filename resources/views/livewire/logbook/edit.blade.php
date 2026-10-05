<?php

use App\Models\Logbook;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    public Logbook $logbook;

    #[Validate('required|exists:vehicles,id')]
    public $vehicle_id;

    #[Validate('required|exists:users,id')]
    public $driver_id;

    #[Validate('nullable|exists:bookings,id')]
    public $booking_id;

    #[Validate('required|date')]
    public $date;

    #[Validate('required|numeric|min:0')]
    public $start_odometer;

    #[Validate('required|numeric|min:0|gte:start_odometer')]
    public $end_odometer;

    #[Validate('required|string|max:255')]
    public $origin_from;

    #[Validate('required|string|max:255')]
    public $origin_to;

    #[Validate('nullable|string')]
    public $purpose;

    #[Validate('nullable|string')]
    public $notes;

    public function mount(Logbook $logbook)
    {
        $this->logbook = $logbook;
        $this->vehicle_id = $logbook->vehicle_id;
        $this->driver_id = $logbook->driver_id;
        $this->booking_id = $logbook->booking_id;
        $this->date = $logbook->date->format('Y-m-d');
        $this->start_odometer = $logbook->start_odometer;
        $this->end_odometer = $logbook->end_odometer;
        $this->origin_from = $logbook->origin_from;
        $this->origin_to = $logbook->origin_to;
        $this->purpose = $logbook->purpose;
        $this->notes = $logbook->notes;
    }

    public function with(): array
    {
        return [
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get(),
            'drivers' => User::role('driver')->orderBy('name')->get(),
            'bookings' => Booking::with(['client', 'vehicle'])
                ->where('status', 'in_progress')
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }

    public function save()
    {
        $this->validate();

        $this->logbook->update([
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'booking_id' => $this->booking_id ?: null,
            'date' => $this->date,
            'start_odometer' => $this->start_odometer,
            'end_odometer' => $this->end_odometer,
            'origin_from' => $this->origin_from,
            'origin_to' => $this->origin_to,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
        ]);

        // MDC calculation is automatically updated via model event
        // Refresh to load the relationship
        $this->logbook->refresh();
        $mdcExists = $this->logbook->mdcCalculation !== null;
        
        $message = 'Logbook entry updated successfully!';
        if ($mdcExists) {
            $message .= ' MDC charge updated: N$' . number_format($this->logbook->mdcCalculation->mdc_amount, 2);
        } else {
            $message .= ' Note: MDC charge could not be calculated (check vehicle GVM and rate cards).';
        }

        $this->dispatch('notify',
            type: $mdcExists ? 'success' : 'info',
            message: $message
        );

        return $this->redirect(route('logbook.index'), navigate: true);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Logbook Entry</flux:heading>

        <flux:button wire:navigate href="{{ route('logbook.index') }}" variant="ghost" icon="arrow-left">
            Back
        </flux:button>
    </flux:header>

    <form wire:submit="save">
        <flux:card class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Date *</flux:label>
                    <flux:input wire:model="date" type="date" />
                    <flux:error name="date" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle *</flux:label>
                    <flux:select wire:model="vehicle_id" placeholder="Select vehicle">
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">
                                {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Driver *</flux:label>
                    <flux:select wire:model="driver_id" placeholder="Select driver">
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="driver_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Link to Booking (Optional)</flux:label>
                    <flux:select wire:model="booking_id" placeholder="Select booking (optional)">
                        <option value="">None</option>
                        @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">
                                {{ $booking->booking_number }} - {{ $booking->client->name }}
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="booking_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Origin From *</flux:label>
                    <flux:input wire:model="origin_from" placeholder="e.g., Windhoek" />
                    <flux:error name="origin_from" />
                </flux:field>

                <flux:field>
                    <flux:label>Origin To *</flux:label>
                    <flux:input wire:model="origin_to" placeholder="e.g., Walvis Bay" />
                    <flux:error name="origin_to" />
                </flux:field>

                <flux:field>
                    <flux:label>Start Odometer Reading (km) *</flux:label>
                    <flux:input wire:model="start_odometer" type="number" step="0.01" placeholder="Enter start odometer reading" />
                    <flux:error name="start_odometer" />
                    <flux:description>Starting point for trip</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>End Odometer Reading (km) *</flux:label>
                    <flux:input wire:model="end_odometer" type="number" step="0.01" placeholder="Enter end odometer reading" />
                    <flux:error name="end_odometer" />
                    <flux:description>Required for MDC charge calculation</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Purpose</flux:label>
                    <flux:textarea wire:model="purpose" rows="2" placeholder="Trip purpose or description..." />
                    <flux:error name="purpose" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Additional notes..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="mt-6 flex gap-3">
            <flux:button type="submit" variant="primary">Update Entry</flux:button>
            <flux:button wire:navigate href="{{ route('logbook.index') }}" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>

