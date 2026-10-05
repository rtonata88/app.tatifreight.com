<?php

use App\Models\Booking;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $dateFilter = '';

    public function with(): array
    {
        $user = auth()->user();
        $isDriver = $user->hasRole('driver');

        $query = Booking::with(['client', 'vehicle.vehicleType', 'driver'])
            // Filter for drivers: only show bookings assigned to them
            ->when($isDriver, function ($q) use ($user) {
                $q->where('driver_id', $user->id);
            })
            ->when($this->search, function ($q) {
                $q->where('booking_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('client', function ($clientQuery) {
                      $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                  ->orWhere('company_name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('vehicle', function ($vehicleQuery) {
                      $vehicleQuery->where('reg_number', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->dateFilter, function ($q) {
                match($this->dateFilter) {
                    'today' => $q->whereDate('start_date', today()),
                    'upcoming' => $q->where('start_date', '>', now()),
                    'past' => $q->where('end_date', '<', now()),
                    default => null
                };
            })
            ->orderBy('created_at', 'desc');

        $statsQuery = Booking::query();
        if ($isDriver) {
            $statsQuery->where('driver_id', $user->id);
        }

        return [
            'bookings' => $query->paginate(10),
            'stats' => [
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'confirmed' => (clone $statsQuery)->where('status', 'confirmed')->count(),
                'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            ],
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

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        if (auth()->user()->can('edit-bookings')) {
            $booking = Booking::findOrFail($id);

            $timestampField = match($status) {
                'confirmed' => 'confirmed_at',
                'in_progress' => 'started_at',
                'completed' => 'completed_at',
                'cancelled' => 'cancelled_at',
                default => null
            };

            $booking->update([
                'status' => $status,
                $timestampField => $timestampField ? now() : null,
            ]);

            $this->dispatch('notify',
                type: 'success',
                message: 'Booking status updated successfully'
            );
        }
    }

    public function deleteBooking(int $id): void
    {
        if (auth()->user()->can('delete-bookings')) {
            Booking::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Booking deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Booking Management</flux:heading>

        {{-- Desktop Buttons --}}
        <div class="hidden md:flex gap-3">
            <flux:button wire:navigate href="{{ route('bookings.calendar') }}" icon="calendar-days" variant="ghost">
                Calendar View
            </flux:button>

            @can('create-bookings')
                <flux:button wire:navigate href="{{ route('bookings.create') }}" icon="plus" variant="primary" class="bg-black hover:bg-gray-800">
                    New Booking
                </flux:button>
            @endcan
        </div>

        {{-- Mobile Dropdown --}}
        <div class="md:hidden">
            <flux:dropdown position="right" align="end">
                <flux:button variant="ghost" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                <flux:menu class="min-w-40">
                    <flux:menu.item
                        wire:navigate
                        href="{{ route('bookings.calendar') }}"
                        icon="calendar-days"
                    >
                        Calendar View
                    </flux:menu.item>

                    @can('create-bookings')
                        <flux:menu.item
                            wire:navigate
                            href="{{ route('bookings.create') }}"
                            icon="plus"
                        >
                            New Booking
                        </flux:menu.item>
                    @endcan
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    {{-- Statistics Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="bg-yellow-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-700">{{ $stats['pending'] }}</p>
                </div>
                <div class="text-yellow-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Confirmed</p>
                    <p class="text-2xl font-bold text-blue-700">{{ $stats['confirmed'] }}</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">In Progress</p>
                    <p class="text-2xl font-bold text-purple-700">{{ $stats['in_progress'] }}</p>
                </div>
                <div class="text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-2xl font-bold text-green-700">{{ $stats['completed'] }}</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
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
                    placeholder="Search bookings, clients, vehicles..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </flux:select>

                <flux:select wire:model.live="dateFilter" placeholder="Filter by date">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="past">Past</option>
                </flux:select>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-4">
                @forelse($bookings as $booking)
                    <flux:card>
                        <div class="space-y-3">
                            {{-- Header with Booking Number and Status --}}
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-lg">{{ $booking->booking_number }}</div>
                                    <div class="text-sm text-gray-600">{{ $booking->client->name }}</div>
                                    @if($booking->client->phone)
                                        <div class="text-xs text-gray-500">
                                            <a href="tel:{{ $booking->client->phone }}" class="hover:text-blue-600">
                                                {{ $booking->client->phone }}
                                            </a>
                                        </div>
                                    @endif
                                    @if($booking->client->company_name)
                                        <div class="text-xs text-gray-500">{{ $booking->client->company_name }}</div>
                                    @endif
                                </div>
                                <flux:badge
                                    :color="match($booking->status) {
                                        'pending' => 'yellow',
                                        'confirmed' => 'blue',
                                        'in_progress' => 'purple',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </flux:badge>
                            </div>

                            {{-- Route Information --}}
                            <div class="border-t pt-3">
                                <div class="grid grid-cols-1 gap-2">
                                    @if($booking->pickup_location)
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <div>
                                                <div class="text-xs text-gray-500">Pickup</div>
                                                <div class="text-sm font-medium">{{ $booking->pickup_location }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($booking->delivery_location)
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <div>
                                                <div class="text-xs text-gray-500">Delivery</div>
                                                <div class="text-sm font-medium">{{ $booking->delivery_location }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 border-t pt-3">
                                <div>
                                    <div class="text-xs text-gray-500">Vehicle</div>
                                    <div class="text-sm font-medium">{{ $booking->vehicle->reg_number }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->vehicle->vehicleType->name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Driver</div>
                                    @if($booking->driver)
                                        <div class="text-sm font-medium">{{ $booking->driver->name }}</div>
                                    @else
                                        <div class="text-sm text-gray-400">Not assigned</div>
                                    @endif
                                </div>
                                @if($booking->distance_km)
                                    <div>
                                        <div class="text-xs text-gray-500">Distance</div>
                                        <div class="text-sm font-medium">{{ number_format($booking->distance_km, 2) }} km</div>
                                    </div>
                                @endif
                                @if($booking->load_weight)
                                    <div>
                                        <div class="text-xs text-gray-500">Load Weight</div>
                                        <div class="text-sm font-medium">{{ number_format($booking->load_weight, 2) }} tons</div>
                                    </div>
                                @endif
                            </div>

                            @if($booking->cargo_description)
                                <div class="border-t pt-3">
                                    <div class="text-xs text-gray-500">Cargo</div>
                                    <div class="text-sm">{{ $booking->cargo_description }}</div>
                                </div>
                            @endif

                            {{-- Dates --}}
                            <div class="border-t pt-3">
                                <div class="text-xs text-gray-500">Duration</div>
                                <div class="text-sm">
                                    {{ $booking->start_date->format('d M Y') }} - {{ $booking->end_date->format('d M Y') }}
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="border-t pt-3 flex flex-wrap gap-2">
                                @can('edit-bookings')
                                    @if($booking->status === 'pending')
                                        <flux:button
                                            wire:click="updateStatus({{ $booking->id }}, 'confirmed')"
                                            size="sm"
                                            variant="primary"
                                            class="flex-1"
                                        >
                                            Confirm
                                        </flux:button>
                                    @endif

                                    @if($booking->status === 'confirmed')
                                        <flux:button
                                            wire:click="updateStatus({{ $booking->id }}, 'in_progress')"
                                            size="sm"
                                            variant="primary"
                                            class="flex-1"
                                        >
                                            Start
                                        </flux:button>
                                    @endif

                                    @if($booking->status === 'in_progress')
                                        <flux:button
                                            wire:click="updateStatus({{ $booking->id }}, 'completed')"
                                            size="sm"
                                            variant="primary"
                                            class="flex-1"
                                        >
                                            Complete
                                        </flux:button>
                                    @endif

                                    <flux:button
                                        wire:navigate
                                        href="{{ route('bookings.edit', $booking) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                        class="flex-1"
                                    >
                                        Edit
                                    </flux:button>
                                @endcan

                                @can('delete-bookings')
                                    <flux:button
                                        wire:click="deleteBooking({{ $booking->id }})"
                                        wire:confirm="Are you sure you want to delete this booking?"
                                        size="sm"
                                        variant="danger"
                                        icon="trash"
                                    >
                                        Delete
                                    </flux:button>
                                @endcan
                            </div>
                        </div>
                    </flux:card>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        No bookings found. Create your first booking to get started.
                    </div>
                @endforelse
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block">
                <flux:table>
                    <flux:columns>
                        <flux:column>Booking #</flux:column>
                        <flux:column>Client</flux:column>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Route</flux:column>
                        <flux:column>Distance</flux:column>
                        <flux:column>Load Details</flux:column>
                        <flux:column>Dates</flux:column>
                        <flux:column>Driver</flux:column>
                        <flux:column>Status</flux:column>
                        <flux:column>Actions</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse($bookings as $booking)
                            <flux:row :key="$booking->id">
                                <flux:cell>
                                    <strong>{{ $booking->booking_number }}</strong>
                                </flux:cell>

                                <flux:cell>
                                    <div>
                                        <div class="font-medium">{{ $booking->client->name }}</div>
                                        @if($booking->client->phone)
                                            <div class="text-xs text-gray-500">{{ $booking->client->phone }}</div>
                                        @endif
                                        @if($booking->client->company_name)
                                            <div class="text-sm text-gray-500">{{ $booking->client->company_name }}</div>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div>
                                        <div class="font-medium">{{ $booking->vehicle->reg_number }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->vehicle->vehicleType->name }}</div>
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">
                                        @if($booking->pickup_location)
                                            <div class="font-medium">From: {{ $booking->pickup_location }}</div>
                                        @endif
                                        @if($booking->delivery_location)
                                            <div class="text-gray-500">To: {{ $booking->delivery_location }}</div>
                                        @endif
                                        @if(!$booking->pickup_location && !$booking->delivery_location)
                                            <span class="text-gray-400">Not specified</span>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    @if($booking->distance_km)
                                        <div class="text-sm font-medium">{{ number_format($booking->distance_km, 2) }} km</div>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    @if($booking->load_weight || $booking->cargo_description)
                                        <div class="text-sm">
                                            @if($booking->load_weight)
                                                <div class="font-medium">{{ number_format($booking->load_weight, 2) }} tons</div>
                                            @endif
                                            @if($booking->cargo_description)
                                                <div class="text-gray-500">{{ Str::limit($booking->cargo_description, 30) }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">
                                        <div>{{ $booking->start_date->format('d M Y') }}</div>
                                        <div class="text-gray-500">to {{ $booking->end_date->format('d M Y') }}</div>
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    @if($booking->driver)
                                        <div class="text-sm">{{ $booking->driver->name }}</div>
                                    @else
                                        <span class="text-gray-400">Not assigned</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <flux:badge
                                        :color="match($booking->status) {
                                            'pending' => 'yellow',
                                            'confirmed' => 'blue',
                                            'in_progress' => 'purple',
                                            'completed' => 'green',
                                            'cancelled' => 'red',
                                            default => 'gray'
                                        }"
                                        size="sm"
                                    >
                                        {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                    </flux:badge>
                                </flux:cell>

                                <flux:cell>
                                    <flux:dropdown position="left" align="start">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                                        <flux:menu class="min-w-32">
                                            @can('edit-bookings')
                                                <flux:menu.item wire:navigate href="{{ route('bookings.edit', $booking) }}" icon="pencil">
                                                    Edit
                                                </flux:menu.item>

                                                @if($booking->status === 'pending')
                                                    <flux:menu.item wire:click="updateStatus({{ $booking->id }}, 'confirmed')" icon="check-circle">
                                                        Confirm Booking
                                                    </flux:menu.item>
                                                @endif

                                                @if($booking->status === 'confirmed')
                                                    <flux:menu.item wire:click="updateStatus({{ $booking->id }}, 'in_progress')" icon="play">
                                                        Start Trip
                                                    </flux:menu.item>
                                                @endif

                                                @if($booking->status === 'in_progress')
                                                    <flux:menu.item wire:click="updateStatus({{ $booking->id }}, 'completed')" icon="check">
                                                        Complete Trip
                                                    </flux:menu.item>
                                                @endif

                                                @if(in_array($booking->status, ['pending', 'confirmed']))
                                                    <flux:menu.item wire:click="updateStatus({{ $booking->id }}, 'cancelled')" icon="x-circle">
                                                        Cancel Booking
                                                    </flux:menu.item>
                                                @endif

                                                <flux:menu.separator />
                                            @endcan

                                            @can('delete-bookings')
                                                <flux:menu.item 
                                                    wire:click="deleteBooking({{ $booking->id }})" 
                                                    wire:confirm="Are you sure you want to delete this booking?" 
                                                    icon="trash"
                                                    variant="danger"
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
                                <flux:cell colspan="10" class="text-center text-gray-500 py-8">
                                    No bookings found. Create your first booking to get started.
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $bookings->links() }}
            </div>
        </div>
    </flux:card>
</div>
