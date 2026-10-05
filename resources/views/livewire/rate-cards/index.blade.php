<?php

use App\Models\RateCard;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $rateTypeFilter = '';
    public string $vehicleTypeFilter = '';

    public function with(): array
    {
        $query = RateCard::with(['vehicleType', 'client'])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                  ->orWhere('company_name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('vehicleType', function ($vehicleTypeQuery) {
                      $vehicleTypeQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->rateTypeFilter, function ($q) {
                $q->where('rate_type', $this->rateTypeFilter);
            })
            ->when($this->vehicleTypeFilter, function ($q) {
                $q->where('vehicle_type_id', $this->vehicleTypeFilter);
            })
            ->orderBy('created_at', 'desc');

        return [
            'rateCards' => $query->paginate(10),
            'stats' => [
                'active' => RateCard::where('is_active', true)->count(),
                'inactive' => RateCard::where('is_active', false)->count(),
                'client_specific' => RateCard::whereNotNull('client_id')->count(),
                'general' => RateCard::whereNull('client_id')->count(),
            ],
            'vehicleTypes' => \App\Models\VehicleType::orderBy('name')->get(),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRateTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVehicleTypeFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        if (auth()->user()->can('edit-rate-cards')) {
            $rateCard = RateCard::findOrFail($id);

            $rateCard->update([
                'is_active' => !$rateCard->is_active,
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Rate card status updated'
            );
        }
    }

    public function deleteRateCard(int $id): void
    {
        if (auth()->user()->can('delete-rate-cards')) {
            RateCard::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Rate card deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Rate Card Management</flux:heading>

        @can('create-rate-cards')
            <flux:button wire:navigate href="{{ route('rate-cards.create') }}" icon="plus">
                New Rate Card
            </flux:button>
        @endcan
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Active Rates</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['active'] }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-gray-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Inactive Rates</p>
                    <p class="text-2xl font-bold text-gray-700">{{ $stats['inactive'] }}</p>
                </div>
                <div class="text-gray-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Client-Specific</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $stats['client_specific'] }}</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">General Rates</p>
                    <p class="text-2xl font-bold text-purple-700">{{ $stats['general'] }}</p>
                </div>
                <div class="text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search rate cards..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="vehicleTypeFilter" placeholder="Filter by vehicle type">
                    <option value="">All Vehicle Types</option>
                    @foreach($vehicleTypes as $vehicleType)
                        <option value="{{ $vehicleType->id }}">{{ $vehicleType->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="rateTypeFilter" placeholder="Filter by rate type">
                    <option value="">All Rate Types</option>
                    <option value="hourly">Hourly</option>
                    <option value="daily">Daily</option>
                    <option value="per_km">Per Kilometer</option>
                    <option value="tonnage">Tonnage</option>
                    <option value="load_specific">Load Specific</option>
                </flux:select>
            </div>

            {{-- Rate Cards Table --}}
            <flux:table>
                <flux:columns>
                    <flux:column>Name</flux:column>
                    <flux:column>Vehicle Type</flux:column>
                    <flux:column>Client</flux:column>
                    <flux:column>Rate Type</flux:column>
                    <flux:column>Rate</flux:column>
                    <flux:column>Effective Period</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($rateCards as $rateCard)
                        <flux:row :key="$rateCard->id">
                            <flux:cell>
                                <strong>{{ $rateCard->name }}</strong>
                                @if($rateCard->includes_mdc)
                                    <span class="text-xs text-blue-600 ml-1">(incl. MDC)</span>
                                @endif
                            </flux:cell>

                            <flux:cell>
                                {{ $rateCard->vehicleType->name }}
                            </flux:cell>

                            <flux:cell>
                                @if($rateCard->client)
                                    <div>
                                        <div class="font-medium text-sm">{{ $rateCard->client->name }}</div>
                                        @if($rateCard->client->company_name)
                                            <div class="text-xs text-gray-500">{{ $rateCard->client->company_name }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-500 text-sm">General Rate</span>
                                @endif
                            </flux:cell>

                            <flux:cell>
                                <flux:badge color="gray" size="sm">
                                    {{ ucfirst(str_replace('_', ' ', $rateCard->rate_type)) }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                <div class="font-medium">N${{  number_format($rateCard->rate, 2) }}</div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    <div>{{ $rateCard->effective_from?->format('d M Y') }}</div>
                                    @if($rateCard->effective_to)
                                        <div class="text-gray-500">to {{ $rateCard->effective_to->format('d M Y') }}</div>
                                    @else
                                        <div class="text-gray-500">No end date</div>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <flux:badge
                                    :color="$rateCard->is_active ? 'green' : 'gray'"
                                    size="sm"
                                >
                                    {{ $rateCard->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                <div class="flex gap-2">
                                    @can('edit-rate-cards')
                                        <flux:button
                                            wire:click="toggleActive({{ $rateCard->id }})"
                                            size="sm"
                                            variant="ghost"
                                        >
                                            {{ $rateCard->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:button>

                                        <flux:button
                                            wire:navigate
                                            href="{{ route('rate-cards.edit', $rateCard) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                        >
                                            Edit
                                        </flux:button>
                                    @endcan

                                    @can('delete-rate-cards')
                                        <flux:button
                                            wire:click="deleteRateCard({{ $rateCard->id }})"
                                            wire:confirm="Are you sure you want to delete this rate card?"
                                            size="sm"
                                            variant="danger"
                                            icon="trash"
                                        >
                                            Delete
                                        </flux:button>
                                    @endcan
                                </div>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="8" class="text-center text-gray-500 py-8">
                                No rate cards found. Create your first rate card to get started.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $rateCards->links() }}
            </div>
        </div>
    </flux:card>
</div>
