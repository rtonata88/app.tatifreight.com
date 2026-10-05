<?php

use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $query = Vehicle::with('vehicleType')
            ->when($this->search, function ($q) {
                $q->where('reg_number', 'like', '%' . $this->search . '%')
                  ->orWhere('make', 'like', '%' . $this->search . '%')
                  ->orWhere('model', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');

        return [
            'vehicles' => $query->paginate(10),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteVehicle(int $id): void
    {
        if (auth()->user()->can('delete-vehicles')) {
            Vehicle::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Vehicle deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Fleet Management</flux:heading>

        <flux:button wire:navigate href="{{ route('vehicles.create') }}" icon="plus" variant="primary" class="bg-black hover:bg-gray-800">
            Add Vehicle
        </flux:button>
    </flux:header>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by reg number, make, or model..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="in_use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="retired">Retired</option>
                </flux:select>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-4">
                @forelse($vehicles as $vehicle)
                    <flux:card>
                        <div class="space-y-3">
                            {{-- Header with Reg Number and Status --}}
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-xl text-gray-900">{{ $vehicle->reg_number }}</div>
                                    <div class="text-sm text-gray-600">{{ $vehicle->vehicleType->name }}</div>
                                </div>
                                <flux:badge
                                    :color="match($vehicle->status) {
                                        'available' => 'green',
                                        'in_use' => 'blue',
                                        'maintenance' => 'yellow',
                                        'retired' => 'red',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                                </flux:badge>
                            </div>

                            {{-- Make & Model --}}
                            <div class="border-t pt-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $vehicle->make }} {{ $vehicle->model }}
                                    @if($vehicle->year)
                                        <span class="text-gray-500">({{ $vehicle->year }})</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 border-t pt-3">
                                <div>
                                    <div class="text-xs text-gray-500">Mileage</div>
                                    <div class="text-sm font-medium">{{ number_format($vehicle->current_mileage, 0) }} km</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Insurance Expiry</div>
                                    @if($vehicle->insurance_expiry)
                                        <div class="text-sm font-medium {{ $vehicle->insurance_expiry->isPast() ? 'text-red-600' : '' }}">
                                            {{ $vehicle->insurance_expiry->format('d M Y') }}
                                        </div>
                                        @if($vehicle->insurance_expiry->isPast())
                                            <flux:badge color="red" size="sm" class="mt-1">Expired</flux:badge>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-400">Not set</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="border-t pt-3 flex flex-wrap gap-2">
                                <flux:button
                                    wire:navigate
                                    href="{{ route('vehicles.show', $vehicle) }}"
                                    size="sm"
                                    variant="ghost"
                                    icon="eye"
                                    class="flex-1"
                                >
                                    View
                                </flux:button>
                                <flux:button
                                    wire:navigate
                                    href="{{ route('vehicles.edit', $vehicle) }}"
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil"
                                    class="flex-1"
                                >
                                    Edit
                                </flux:button>

                                @can('delete-vehicles')
                                    <flux:button
                                        wire:click="deleteVehicle({{ $vehicle->id }})"
                                        wire:confirm="Are you sure you want to delete this vehicle?"
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
                        No vehicles found. Add your first vehicle to get started.
                    </div>
                @endforelse
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block">
                <flux:table>
                    <flux:columns>
                        <flux:column>Reg Number</flux:column>
                        <flux:column>Type</flux:column>
                        <flux:column>Make & Model</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Mileage</flux:column>
                        <flux:column>Insurance Expiry</flux:column>
                        <flux:column>Actions</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse($vehicles as $vehicle)
                            <flux:row :key="$vehicle->id">
                                <flux:cell>
                                    <strong>{{ $vehicle->reg_number }}</strong>
                                </flux:cell>

                                <flux:cell>
                                    {{ $vehicle->vehicleType->name }}
                                </flux:cell>

                                <flux:cell>
                                    {{ $vehicle->make }} {{ $vehicle->model }}
                                    @if($vehicle->year)
                                        <span class="text-gray-500">({{ $vehicle->year }})</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <flux:badge
                                        :color="match($vehicle->status) {
                                            'available' => 'green',
                                            'in_use' => 'blue',
                                            'maintenance' => 'yellow',
                                            'retired' => 'red',
                                            default => 'gray'
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                                    </flux:badge>
                                </flux:cell>

                                <flux:cell>
                                    {{ number_format($vehicle->current_mileage, 0) }} km
                                </flux:cell>

                                <flux:cell>
                                    @if($vehicle->insurance_expiry)
                                        <span class="{{ $vehicle->insurance_expiry->isPast() ? 'text-red-600' : '' }}">
                                            {{ $vehicle->insurance_expiry->format('d M Y') }}
                                        </span>
                                        @if($vehicle->insurance_expiry->isPast())
                                            <flux:badge color="red" size="sm">Expired</flux:badge>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Not set</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <div class="flex gap-2">
                                        <flux:button
                                            wire:navigate
                                            href="{{ route('vehicles.show', $vehicle) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="eye"
                                        >
                                            View
                                        </flux:button>
                                        <flux:button
                                            wire:navigate
                                            href="{{ route('vehicles.edit', $vehicle) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                        >
                                            Edit
                                        </flux:button>

                                        @can('delete-vehicles')
                                            <flux:button
                                                wire:click="deleteVehicle({{ $vehicle->id }})"
                                                wire:confirm="Are you sure you want to delete this vehicle?"
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
                                <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                    No vehicles found. Add your first vehicle to get started.
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $vehicles->links() }}
            </div>
        </div>
    </flux:card>
</div>
