<?php

use App\Models\RateCard;
use App\Models\VehicleType;
use App\Models\Client;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    public RateCard $rateCard;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('required|exists:vehicle_types,id')]
    public $vehicle_type_id;

    #[Validate('nullable|exists:clients,id')]
    public $client_id;

    #[Validate('required|in:hourly,daily,per_km,tonnage,load_specific')]
    public $rate_type;

    #[Validate('required|numeric|min:0.01')]
    public $rate;

    #[Validate('boolean')]
    public $includes_mdc;

    #[Validate('required|date')]
    public $effective_from;

    #[Validate('nullable|date|after:effective_from')]
    public $effective_to;

    #[Validate('boolean')]
    public $is_active;

    #[Validate('nullable|string')]
    public $notes;

    public function mount(RateCard $rateCard)
    {
        $this->rateCard = $rateCard;

        $this->name = $this->rateCard->name;
        $this->vehicle_type_id = $this->rateCard->vehicle_type_id;
        $this->client_id = $this->rateCard->client_id;
        $this->rate_type = $this->rateCard->rate_type;
        $this->rate = $this->rateCard->rate;
        $this->includes_mdc = $this->rateCard->includes_mdc;
        $this->effective_from = $this->rateCard->effective_from?->format('Y-m-d');
        $this->effective_to = $this->rateCard->effective_to?->format('Y-m-d');
        $this->is_active = $this->rateCard->is_active;
        $this->notes = $this->rateCard->notes;
    }

    public function with(): array
    {
        return [
            'vehicleTypes' => VehicleType::orderBy('name')->get(),
            'clients' => Client::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    public function save()
    {
        $this->validate();

        $this->rateCard->update([
            'vehicle_type_id' => $this->vehicle_type_id,
            'client_id' => $this->client_id ?: null,
            'name' => $this->name,
            'rate_type' => $this->rate_type,
            'rate' => $this->rate,
            'includes_mdc' => $this->includes_mdc,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to ?: null,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ]);

        $this->dispatch('notify',
            type: 'success',
            message: 'Rate card updated successfully!'
        );

        return redirect()->route('rate-cards.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Rate Card</flux:heading>

        <div class="flex items-center gap-2">
            <flux:badge :color="$rateCard->is_active ? 'green' : 'gray'">
                {{ $rateCard->is_active ? 'Active' : 'Inactive' }}
            </flux:badge>
        </div>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Rate Card Details</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Rate Card Name *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle Type *</flux:label>
                    <flux:select wire:model="vehicle_type_id">
                        <option value="">Select vehicle type</option>
                        @foreach($vehicleTypes as $vehicleType)
                            <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="vehicle_type_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Client (Optional)</flux:label>
                    <flux:select wire:model="client_id">
                        <option value="">General Rate (all clients)</option>
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
                    <flux:description>Client-specific rate or general rate</flux:description>
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Pricing</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Rate Type *</flux:label>
                    <flux:select wire:model="rate_type">
                        <option value="">Select rate type</option>
                        <option value="hourly">Hourly Rate</option>
                        <option value="daily">Daily Rate</option>
                        <option value="per_km">Per Kilometer</option>
                        <option value="tonnage">Per Ton</option>
                        <option value="load_specific">Load Specific</option>
                    </flux:select>
                    <flux:error name="rate_type" />
                </flux:field>

                <flux:field>
                    <flux:label>Rate (R) *</flux:label>
                    <flux:input wire:model="rate" type="number" step="0.01" min="0.01" />
                    <flux:error name="rate" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:checkbox wire:model="includes_mdc">
                        Rate includes MDC (Mass Distance Charge)
                    </flux:checkbox>
                    <flux:error name="includes_mdc" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Effective Period</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Effective From *</flux:label>
                    <flux:input wire:model="effective_from" type="date" />
                    <flux:error name="effective_from" />
                </flux:field>

                <flux:field>
                    <flux:label>Effective To</flux:label>
                    <flux:input wire:model="effective_to" type="date" />
                    <flux:error name="effective_to" />
                    <flux:description>Leave blank for no end date</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:checkbox wire:model="is_active">
                        Rate card is active
                    </flux:checkbox>
                    <flux:error name="is_active" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        @if($rateCard->created_at)
            <flux:card>
                <flux:heading size="lg">Audit Trail</flux:heading>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Created</p>
                        <p class="text-sm font-medium">{{ $rateCard->created_at->format('d M Y, H:i') }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Last Updated</p>
                        <p class="text-sm font-medium">{{ $rateCard->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </flux:card>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Rate Card
            </flux:button>

            <flux:button wire:navigate href="{{ route('rate-cards.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
