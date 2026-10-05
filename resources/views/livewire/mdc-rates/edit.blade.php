<?php

use App\Models\MdcRateCard;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public MdcRateCard $mdcRateCard;
    
    public string $category_name = '';
    public string $min_gvm_tonnes = '';
    public string $max_gvm_tonnes = '';
    public string $rate_per_100km = '';
    public string $effective_from = '';
    public string $effective_to = '';
    public bool $is_active = true;
    public string $notes = '';

    public function mount(MdcRateCard $mdcRateCard): void
    {
        if (!auth()->user()->can('edit-vehicles')) {
            $this->redirect(route('mdc-rates.index'));
        }

        $this->mdcRateCard = $mdcRateCard;
        $this->category_name = $mdcRateCard->category_name;
        $this->min_gvm_tonnes = $mdcRateCard->min_gvm_tonnes;
        $this->max_gvm_tonnes = $mdcRateCard->max_gvm_tonnes ?? '';
        $this->rate_per_100km = $mdcRateCard->rate_per_100km;
        $this->effective_from = $mdcRateCard->effective_from->format('Y-m-d');
        $this->effective_to = $mdcRateCard->effective_to?->format('Y-m-d') ?? '';
        $this->is_active = $mdcRateCard->is_active;
        $this->notes = $mdcRateCard->notes ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'category_name' => 'required|string|max:255',
            'min_gvm_tonnes' => 'required|numeric|min:0',
            'max_gvm_tonnes' => 'nullable|numeric|gt:min_gvm_tonnes',
            'rate_per_100km' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
        ]);

        $this->mdcRateCard->update([
            'category_name' => $this->category_name,
            'min_gvm_tonnes' => $this->min_gvm_tonnes,
            'max_gvm_tonnes' => $this->max_gvm_tonnes ?: null,
            'rate_per_100km' => $this->rate_per_100km,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to ?: null,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
        ]);

        $this->dispatch('notify', type: 'success', message: 'MDC rate card updated successfully!');
        $this->redirect(route('mdc-rates.index'), navigate: true);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading>Edit MDC Rate Card</flux:heading>
        <flux:subheading>Update RFANAM Mass Distance Charge rate</flux:subheading>
    </flux:header>

    <form wire:submit="save" class="space-y-6 mt-6">
        <flux:card class="space-y-6">
            <flux:heading size="lg">Rate Information</flux:heading>

            {{-- Category Name --}}
            <flux:field>
                <flux:label>Category Name *</flux:label>
                <flux:input wire:model="category_name" placeholder="e.g., Heavy Truck (24t - 27t)" />
                <flux:error name="category_name" />
                <flux:description>Descriptive name for this GVM category</flux:description>
            </flux:field>

            {{-- GVM Range --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Minimum GVM (tonnes) *</flux:label>
                    <flux:input type="number" step="0.01" wire:model="min_gvm_tonnes" placeholder="0.00" />
                    <flux:error name="min_gvm_tonnes" />
                </flux:field>

                <flux:field>
                    <flux:label>Maximum GVM (tonnes)</flux:label>
                    <flux:input type="number" step="0.01" wire:model="max_gvm_tonnes" placeholder="Leave empty for unlimited" />
                    <flux:error name="max_gvm_tonnes" />
                    <flux:description>Leave empty for no upper limit</flux:description>
                </flux:field>
            </div>

            {{-- Rate --}}
            <flux:field>
                <flux:label>Rate per 100km (N$) *</flux:label>
                <flux:input type="number" step="0.01" wire:model="rate_per_100km" placeholder="0.00" />
                <flux:error name="rate_per_100km" />
                <flux:description>RFANAM charge rate in Namibian Dollars per 100 kilometers</flux:description>
            </flux:field>

            {{-- Effective Period --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Effective From *</flux:label>
                    <flux:input type="date" wire:model="effective_from" />
                    <flux:error name="effective_from" />
                </flux:field>

                <flux:field>
                    <flux:label>Effective To</flux:label>
                    <flux:input type="date" wire:model="effective_to" />
                    <flux:error name="effective_to" />
                    <flux:description>Leave empty for ongoing</flux:description>
                </flux:field>
            </div>

            {{-- Status --}}
            <flux:field>
                <flux:checkbox wire:model="is_active">
                    Active
                </flux:checkbox>
                <flux:description>Only active rates will be used in MDC calculations</flux:description>
            </flux:field>

            {{-- Notes --}}
            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" rows="3" placeholder="Additional information about this rate category..." />
                <flux:error name="notes" />
            </flux:field>
        </flux:card>

        {{-- Actions --}}
        <div class="flex gap-4 justify-end">
            <flux:button variant="ghost" :href="route('mdc-rates.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">Update MDC Rate</flux:button>
        </div>
    </form>
</div>

