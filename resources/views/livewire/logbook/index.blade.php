<?php

use App\Models\Logbook;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $vehicleFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = Logbook::with(['vehicle.vehicleType', 'driver', 'booking'])
            ->when($this->search, function ($q) {
                $q->whereHas('vehicle', function ($vehicleQuery) {
                    $vehicleQuery->where('reg_number', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('driver', function ($driverQuery) {
                    $driverQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('origin_from', 'like', '%' . $this->search . '%')
                ->orWhere('origin_to', 'like', '%' . $this->search . '%');
            })
            ->when($this->vehicleFilter, function ($q) {
                $q->where('vehicle_id', $this->vehicleFilter);
            })
            ->when($this->dateFrom, function ($q) {
                $q->where('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->where('date', '<=', $this->dateTo);
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        $vehicles = Vehicle::with('vehicleType')->orderBy('reg_number')->get();

        // Summary statistics
        $totalDistance = Logbook::when($this->vehicleFilter, function ($q) {
                $q->where('vehicle_id', $this->vehicleFilter);
            })
            ->when($this->dateFrom, function ($q) {
                $q->where('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->where('date', '<=', $this->dateTo);
            })
            ->get()
            ->sum(function($logbook) {
                return $logbook->distance_travelled;
            });

        $totalTrips = Logbook::when($this->vehicleFilter, function ($q) {
                $q->where('vehicle_id', $this->vehicleFilter);
            })
            ->when($this->dateFrom, function ($q) {
                $q->where('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->where('date', '<=', $this->dateTo);
            })
            ->count();

        return [
            'logbooks' => $query->paginate(15),
            'vehicles' => $vehicles,
            'totalDistance' => $totalDistance,
            'totalTrips' => $totalTrips,
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingVehicleFilter(): void
    {
        $this->resetPage();
    }

    public function deleteLogbook(int $id): void
    {
        if (auth()->user()->can('delete-vehicles')) {
            Logbook::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Logbook entry deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Vehicle Logbook</flux:heading>

        @can('create-vehicles')
            <flux:button wire:navigate href="{{ route('logbook.create') }}" icon="plus" variant="primary">
                New Entry
            </flux:button>
        @endcan
    </flux:header>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Distance</p>
                    <p class="text-3xl font-bold text-blue-700">{{ number_format($totalDistance, 0) }} km</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Trips</p>
                    <p class="text-3xl font-bold text-green-700">{{ number_format($totalTrips) }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:field>
                <flux:label>Search</flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search vehicle, driver, location..."
                    icon="magnifying-glass"
                />
            </flux:field>

            <flux:field>
                <flux:label>Vehicle</flux:label>
                <flux:select wire:model.live="vehicleFilter" placeholder="All Vehicles">
                    <option value="">All Vehicles</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">
                            {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Date From</flux:label>
                <flux:input wire:model.live="dateFrom" type="date" />
            </flux:field>

            <flux:field>
                <flux:label>Date To</flux:label>
                <flux:input wire:model.live="dateTo" type="date" />
            </flux:field>
        </div>
    </flux:card>

    {{-- Mobile Card View --}}
    <div class="md:hidden mt-6 space-y-4">
        @forelse($logbooks as $logbook)
            <flux:card>
                <div class="space-y-3">
                    {{-- Header with Date and Distance --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-bold text-lg text-gray-900">{{ $logbook->date->format('d M Y') }}</div>
                            <div class="text-sm text-gray-600">{{ $logbook->vehicle->reg_number }} - {{ $logbook->vehicle->vehicleType->name }}</div>
                        </div>
                        @if($logbook->distance_travelled > 0)
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Distance</div>
                                <div class="font-bold text-lg text-blue-700">{{ number_format($logbook->distance_travelled, 0) }} km</div>
                            </div>
                        @endif
                    </div>

                    {{-- Route --}}
                    <div class="border-t pt-3">
                        <div class="text-sm">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium text-gray-900">{{ $logbook->origin_from }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="font-medium text-gray-900">{{ $logbook->origin_to }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Details Grid --}}
                    <div class="grid grid-cols-2 gap-3 border-t pt-3">
                        <div>
                            <div class="text-xs text-gray-500">Driver</div>
                            <div class="text-sm font-medium">{{ $logbook->driver->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Vehicle</div>
                            <div class="text-sm font-medium">{{ $logbook->vehicle->reg_number }}</div>
                            <div class="text-xs text-gray-500">{{ $logbook->vehicle->vehicleType->name }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Start Odometer</div>
                            <div class="text-sm font-medium">{{ number_format($logbook->start_odometer, 0) }} km</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">End Odometer</div>
                            <div class="text-sm font-medium">
                                {{ $logbook->end_odometer ? number_format($logbook->end_odometer, 0) . ' km' : '-' }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="border-t pt-3 flex flex-wrap gap-2">
                        @can('edit-vehicles')
                            <flux:button
                                wire:navigate
                                href="{{ route('logbook.edit', $logbook) }}"
                                size="sm"
                                variant="ghost"
                                icon="pencil"
                                class="flex-1"
                            >
                                Edit
                            </flux:button>
                        @endcan

                        @can('delete-vehicles')
                            <flux:button
                                wire:click="deleteLogbook({{ $logbook->id }})"
                                wire:confirm="Are you sure you want to delete this logbook entry?"
                                size="sm"
                                variant="danger"
                                icon="trash"
                                class="flex-1"
                            >
                                Delete
                            </flux:button>
                        @endcan
                    </div>
                </div>
            </flux:card>
        @empty
            <div class="text-center text-gray-500 py-8">
                No logbook entries found. Add your first entry to get started.
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $logbooks->links() }}
        </div>
    </div>

    {{-- Desktop Table View --}}
    <flux:card class="mt-6 hidden md:block">
        <flux:table>
            <flux:columns>
                <flux:column>Date</flux:column>
                <flux:column>Vehicle</flux:column>
                <flux:column>Driver</flux:column>
                <flux:column>Route</flux:column>
                <flux:column>Start Odometer</flux:column>
                <flux:column>End Odometer</flux:column>
                <flux:column>Distance</flux:column>
                <flux:column>Actions</flux:column>
            </flux:columns>

            <flux:rows>
                @forelse($logbooks as $logbook)
                    <flux:row :key="$logbook->id">
                        <flux:cell>
                            <div class="font-medium">{{ $logbook->date->format('d M Y') }}</div>
                        </flux:cell>

                        <flux:cell>
                            <div class="font-medium">{{ $logbook->vehicle->reg_number }}</div>
                            <div class="text-sm text-gray-500">{{ $logbook->vehicle->vehicleType->name }}</div>
                        </flux:cell>

                        <flux:cell>
                            {{ $logbook->driver->name }}
                        </flux:cell>

                        <flux:cell>
                            <div class="text-sm">
                                <div><strong>From:</strong> {{ $logbook->origin_from }}</div>
                                <div><strong>To:</strong> {{ $logbook->origin_to }}</div>
                            </div>
                        </flux:cell>

                        <flux:cell class="text-right">
                            {{ number_format($logbook->start_odometer, 0) }} km
                        </flux:cell>

                        <flux:cell class="text-right">
                            {{ $logbook->end_odometer ? number_format($logbook->end_odometer, 0) . ' km' : '-' }}
                        </flux:cell>

                        <flux:cell class="text-right font-semibold">
                            {{ $logbook->distance_travelled > 0 ? number_format($logbook->distance_travelled, 0) . ' km' : '-' }}
                        </flux:cell>

                        <flux:cell>
                            <div class="flex gap-2">
                                @can('edit-vehicles')
                                    <flux:button
                                        wire:navigate
                                        href="{{ route('logbook.edit', $logbook) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                    >
                                        Edit
                                    </flux:button>
                                @endcan

                                @can('delete-vehicles')
                                    <flux:button
                                        wire:click="deleteLogbook({{ $logbook->id }})"
                                        wire:confirm="Are you sure you want to delete this logbook entry?"
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
                            No logbook entries found. Add your first entry to get started.
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $logbooks->links() }}
        </div>
    </flux:card>
</div>

