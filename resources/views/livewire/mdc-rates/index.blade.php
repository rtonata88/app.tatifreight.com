<?php

use App\Models\MdcRateCard;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = MdcRateCard::query();

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('category_name', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by status
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $mdcRates = $query->orderBy('min_gvm_tonnes')->paginate(20);

        return [
            'mdcRates' => $mdcRates,
            'totalRates' => MdcRateCard::count(),
            'activeRates' => MdcRateCard::where('is_active', true)->count(),
        ];
    }

    public function toggleStatus(int $id): void
    {
        if (!auth()->user()->can('edit-vehicles')) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to edit MDC rates');
            return;
        }

        $rate = MdcRateCard::findOrFail($id);
        $rate->update(['is_active' => !$rate->is_active]);

        $this->dispatch('notify', type: 'success', message: 'MDC rate status updated');
    }

    public function delete(int $id): void
    {
        if (!auth()->user()->can('delete-vehicles')) {
            $this->dispatch('notify', type: 'error', message: 'You do not have permission to delete MDC rates');
            return;
        }

        $rate = MdcRateCard::findOrFail($id);
        $rate->delete();

        $this->dispatch('notify', type: 'success', message: 'MDC rate deleted successfully');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading>MDC Rate Cards</flux:heading>
        <flux:subheading>Manage RFANAM Mass Distance Charge rates</flux:subheading>

        <x-slot:actions>
            @can('create-vehicles')
                <flux:button :href="route('mdc-rates.create')" icon="plus" wire:navigate>Add MDC Rate</flux:button>
            @endcan
        </x-slot:actions>
    </flux:header>

    <flux:card class="space-y-6 mt-6">
        {{-- Statistics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Rate Categories</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalRates }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <div class="text-sm text-green-600 dark:text-green-400">Active Rates</div>
                <div class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $activeRates }}</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Search by category or notes..." 
                    icon="magnifying-glass"
                />
            </div>
            <div class="w-full sm:w-48">
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </flux:select>
            </div>
        </div>

        {{-- Table --}}
        <flux:table>
            <flux:columns>
                <flux:column>Category</flux:column>
                <flux:column>GVM Range</flux:column>
                <flux:column>Rate (N$/100km)</flux:column>
                <flux:column>Effective Period</flux:column>
                <flux:column>Status</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($mdcRates as $rate)
                    <flux:row :key="$rate->id">
                        <flux:cell>
                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $rate->category_name }}</div>
                            @if($rate->notes)
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $rate->notes }}</div>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            {{ number_format($rate->min_gvm_tonnes, 2) }}t - 
                            {{ $rate->max_gvm_tonnes ? number_format($rate->max_gvm_tonnes, 2) . 't' : '∞' }}
                        </flux:cell>
                        <flux:cell>
                            <span class="font-mono font-semibold">N$ {{ number_format($rate->rate_per_100km, 2) }}</span>
                        </flux:cell>
                        <flux:cell>
                            <div class="text-sm">
                                <div>From: {{ $rate->effective_from->format('d M Y') }}</div>
                                @if($rate->effective_to)
                                    <div class="text-gray-500">To: {{ $rate->effective_to->format('d M Y') }}</div>
                                @else
                                    <div class="text-green-600">Ongoing</div>
                                @endif
                            </div>
                        </flux:cell>
                        <flux:cell>
                            @if($rate->is_active)
                                <flux:badge color="green" size="sm">Active</flux:badge>
                            @else
                                <flux:badge color="gray" size="sm">Inactive</flux:badge>
                            @endif
                        </flux:cell>
                        <flux:cell>
                            <flux:dropdown position="left">
                                <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" inset="top bottom"></flux:button>

                                <flux:menu>
                                    @can('edit-vehicles')
                                        <flux:menu.item icon="pencil" :href="route('mdc-rates.edit', $rate)" wire:navigate>
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.item 
                                            :icon="$rate->is_active ? 'x-circle' : 'check-circle'" 
                                            wire:click="toggleStatus({{ $rate->id }})"
                                        >
                                            {{ $rate->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>
                                    @endcan

                                    @can('delete-vehicles')
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            icon="trash" 
                                            variant="danger" 
                                            wire:click="delete({{ $rate->id }})"
                                            wire:confirm="Are you sure you want to delete this MDC rate?"
                                        >
                                            Delete
                                        </flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="6" class="text-center py-8 text-gray-500">
                            No MDC rates found. Add your first rate to get started.
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $mdcRates->links() }}
        </div>
    </flux:card>
</div>

