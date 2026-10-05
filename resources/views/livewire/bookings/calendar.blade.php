<?php

use App\Models\Booking;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {

    public $currentDate;
    public $viewType = 'month'; // month, week, list
    public $selectedVehicle = '';
    public $selectedBooking = null;
    public $showModal = false;

    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function viewBooking($bookingId)
    {
        $this->selectedBooking = Booking::with(['client', 'vehicle.vehicleType', 'driver', 'createdBy'])
            ->find($bookingId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedBooking = null;
    }

    public function with(): array
    {
        $date = Carbon::parse($this->currentDate);
        $vehicles = Vehicle::with('vehicleType')->orderBy('reg_number')->get();

        // Get bookings for the current month
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $bookingsQuery = Booking::with(['client', 'vehicle.vehicleType'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate])
            ->orWhere(function($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $startDate)
                      ->where('end_date', '>=', $endDate);
            });

        if ($this->selectedVehicle) {
            $bookingsQuery->where('vehicle_id', $this->selectedVehicle);
        }

        $bookings = $bookingsQuery->orderBy('start_date')->get();

        // Build calendar grid
        $calendarStart = $startDate->copy()->startOfWeek();
        $calendarEnd = $endDate->copy()->endOfWeek();
        $weeks = [];
        $currentWeek = [];
        
        for ($day = $calendarStart->copy(); $day <= $calendarEnd; $day->addDay()) {
            $currentWeek[] = [
                'date' => $day->copy(),
                'isCurrentMonth' => $day->month === $date->month,
                'isToday' => $day->isToday(),
                'bookings' => $bookings->filter(function($booking) use ($day) {
                    return $booking->start_date->lte($day) && $booking->end_date->gte($day);
                })
            ];

            if ($day->isSunday() || $day->eq($calendarEnd)) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        return [
            'vehicles' => $vehicles,
            'bookings' => $bookings,
            'weeks' => $weeks,
            'date' => $date,
        ];
    }

    public function previousMonth()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subMonth()->format('Y-m-d');
    }

    public function nextMonth()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonth()->format('Y-m-d');
    }

    public function today()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function setViewType($type)
    {
        $this->viewType = $type;
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Vehicle Booking Calendar</flux:heading>

        {{-- Desktop Buttons --}}
        <div class="hidden md:flex gap-3">
            <flux:button wire:navigate href="{{ route('bookings.index') }}" icon="list-bullet" variant="ghost">
                List View
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
                        href="{{ route('bookings.index') }}"
                        icon="list-bullet"
                    >
                        List View
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

    {{-- Controls --}}
    <flux:card class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{-- Date Navigation --}}
            <div class="flex items-center gap-2">
                <flux:button wire:click="previousMonth" variant="ghost" icon="chevron-left" size="sm"></flux:button>
                <div class="text-lg font-semibold min-w-[200px] text-center">
                    {{ $date->format('F Y') }}
                </div>
                <flux:button wire:click="nextMonth" variant="ghost" icon="chevron-right" size="sm"></flux:button>
                <flux:button wire:click="today" variant="ghost" size="sm">Today</flux:button>
            </div>

            {{-- Filter by Vehicle --}}
            <div class="flex items-center gap-4">
                <flux:select wire:model.live="selectedVehicle" placeholder="All Vehicles" class="min-w-[200px]">
                    <option value="">All Vehicles</option>
                    @foreach($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">
                            {{ $vehicle->reg_number }} - {{ $vehicle->vehicleType->name }}
                        </option>
                    @endforeach
                </flux:select>

                {{-- View Type Toggle --}}
                <div class="flex gap-1 bg-gray-100 rounded p-1">
                    <button 
                        wire:click="setViewType('month')"
                        class="px-3 py-1 rounded text-sm {{ $viewType === 'month' ? 'bg-white shadow' : 'text-gray-600' }}"
                    >
                        Month
                    </button>
                    <button 
                        wire:click="setViewType('list')"
                        class="px-3 py-1 rounded text-sm {{ $viewType === 'list' ? 'bg-white shadow' : 'text-gray-600' }}"
                    >
                        List
                    </button>
                </div>
            </div>
        </div>
    </flux:card>

    @if($viewType === 'month')
        {{-- Calendar View --}}
        <flux:card class="mt-6">
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Monday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Tuesday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Wednesday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Thursday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Friday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Saturday</th>
                            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700 w-[14.28%]">Sunday</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($weeks as $week)
                            <tr>
                                @foreach($week as $day)
                                    <td class="border border-gray-300 p-1 align-top h-32 {{ !$day['isCurrentMonth'] ? 'bg-gray-50' : '' }} {{ $day['isToday'] ? 'bg-blue-50' : '' }}">
                                        <div class="text-sm font-medium mb-1 {{ !$day['isCurrentMonth'] ? 'text-gray-400' : ($day['isToday'] ? 'text-blue-600' : 'text-gray-700') }}">
                                            {{ $day['date']->format('d') }}
                                        </div>
                                        
                                        <div class="space-y-1">
                                            @foreach($day['bookings'] as $booking)
                                                <button 
                                                    wire:click="viewBooking({{ $booking->id }})"
                                                    type="button"
                                                    class="block text-xs px-1 py-0.5 rounded cursor-pointer hover:opacity-80 w-full text-left
                                                        {{ $booking->status === 'confirmed' ? 'bg-blue-100 text-blue-800 border-l-2 border-blue-500' : '' }}
                                                        {{ $booking->status === 'in_progress' ? 'bg-green-100 text-green-800 border-l-2 border-green-500' : '' }}
                                                        {{ $booking->status === 'completed' ? 'bg-gray-100 text-gray-800 border-l-2 border-gray-500' : '' }}
                                                        {{ $booking->status === 'pending' ? 'bg-yellow-100 text-yellow-800 border-l-2 border-yellow-500' : '' }}
                                                        {{ $booking->status === 'cancelled' ? 'bg-red-100 text-red-800 border-l-2 border-red-500' : '' }}"
                                                    title="{{ $booking->client->company_name ?: $booking->client->name }} - {{ $booking->vehicle->reg_number }} ({{ $booking->vehicle->vehicleType->name }})"
                                                >
                                                    <div class="font-semibold truncate">{{ $booking->vehicle->reg_number }}</div>
                                                    <div class="text-[10px] text-gray-600 truncate">{{ $booking->vehicle->vehicleType->name }}</div>
                                                    <div class="truncate">{{ $booking->client->company_name ?: $booking->client->name }}</div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="mt-4 flex flex-wrap gap-4 text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-yellow-100 border-l-2 border-yellow-500"></div>
                    <span>Pending</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-blue-100 border-l-2 border-blue-500"></div>
                    <span>Confirmed</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-green-100 border-l-2 border-green-500"></div>
                    <span>In Progress</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-gray-100 border-l-2 border-gray-500"></div>
                    <span>Completed</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 bg-red-100 border-l-2 border-red-500"></div>
                    <span>Cancelled</span>
                </div>
            </div>
        </flux:card>
    @else
        {{-- List View --}}
        <flux:card class="mt-6">
            <flux:table>
                <flux:columns>
                    <flux:column>Booking #</flux:column>
                    <flux:column>Vehicle</flux:column>
                    <flux:column>Client</flux:column>
                    <flux:column>Start Date</flux:column>
                    <flux:column>End Date</flux:column>
                    <flux:column>Duration</flux:column>
                    <flux:column>Status</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($bookings as $booking)
                        <flux:row :key="$booking->id">
                            <flux:cell>
                                <button wire:click="viewBooking({{ $booking->id }})" class="text-blue-600 hover:underline">
                                    {{ $booking->booking_number }}
                                </button>
                            </flux:cell>
                            <flux:cell>
                                <div class="font-medium">{{ $booking->vehicle->reg_number }}</div>
                                <div class="text-sm text-gray-500">{{ $booking->vehicle->vehicleType->name }}</div>
                            </flux:cell>
                            <flux:cell>{{ $booking->client->company_name ?: $booking->client->name }}</flux:cell>
                            <flux:cell>{{ $booking->start_date->format('d M Y') }}</flux:cell>
                            <flux:cell>{{ $booking->end_date->format('d M Y') }}</flux:cell>
                            <flux:cell>{{ round($booking->start_date->diffInDays($booking->end_date)) + 1 }} days</flux:cell>
                            <flux:cell>
                                <flux:badge
                                    :color="match($booking->status) {
                                        'pending' => 'yellow',
                                        'confirmed' => 'blue',
                                        'in_progress' => 'green',
                                        'completed' => 'gray',
                                        'cancelled' => 'red',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                >
                                    {{ ucfirst($booking->status) }}
                                </flux:badge>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                No bookings found for this period.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Booking Details Modal --}}
    @if($showModal && $selectedBooking)
        <flux:modal name="booking-details" class="min-w-[600px]" wire:model="showModal">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:heading size="lg">Booking Details</flux:heading>
                        <p class="text-sm font-semibold text-gray-700 mt-1">{{ $selectedBooking->booking_number }}</p>
                        @if($selectedBooking->createdBy)
                            <p class="text-xs text-gray-500 mt-1">
                                Created by {{ $selectedBooking->createdBy->name }} on {{ $selectedBooking->created_at->format('d M Y, H:i') }}
                            </p>
                        @endif
                    </div>
                    <flux:badge
                        :color="match($selectedBooking->status) {
                            'pending' => 'yellow',
                            'confirmed' => 'blue',
                            'in_progress' => 'green',
                            'completed' => 'gray',
                            'cancelled' => 'red',
                            default => 'gray'
                        }"
                        size="lg"
                    >
                        {{ ucfirst($selectedBooking->status) }}
                    </flux:badge>
                </div>

                <div class="space-y-6">
                    {{-- Client Information --}}
                    <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-lg">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3">Client Information</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if($selectedBooking->client->company_name)
                                <div>
                                    <span class="text-gray-500">Company:</span>
                                    <p class="font-medium">{{ $selectedBooking->client->company_name }}</p>
                                </div>
                            @endif
                            <div>
                                <span class="text-gray-500">Contact Person:</span>
                                <p class="font-medium">{{ $selectedBooking->client->name }}</p>
                            </div>
                            @if($selectedBooking->client->phone)
                                <div>
                                    <span class="text-gray-500">Phone:</span>
                                    <p class="font-medium">{{ $selectedBooking->client->phone }}</p>
                                </div>
                            @endif
                            @if($selectedBooking->client->email)
                                <div>
                                    <span class="text-gray-500">Email:</span>
                                    <p class="font-medium">{{ $selectedBooking->client->email }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Vehicle Information --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3">Vehicle Information</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Registration:</span>
                                <p class="font-medium">{{ $selectedBooking->vehicle->reg_number }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Type:</span>
                                <p class="font-medium">{{ $selectedBooking->vehicle->vehicleType->name }}</p>
                            </div>
                            @if($selectedBooking->vehicle->make)
                                <div>
                                    <span class="text-gray-500">Make/Model:</span>
                                    <p class="font-medium">{{ $selectedBooking->vehicle->make }} {{ $selectedBooking->vehicle->model }}</p>
                                </div>
                            @endif
                            @if($selectedBooking->vehicle->capacity_tonnes)
                                <div>
                                    <span class="text-gray-500">Capacity:</span>
                                    <p class="font-medium">{{ $selectedBooking->vehicle->capacity_tonnes }} tonnes</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Booking Details --}}
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3">Trip Details</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Start Date:</span>
                                <p class="font-medium">{{ $selectedBooking->start_date->format('d M Y, H:i') }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">End Date:</span>
                                <p class="font-medium">{{ $selectedBooking->end_date->format('d M Y, H:i') }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500">Duration:</span>
                                <p class="font-medium">{{ round($selectedBooking->start_date->diffInDays($selectedBooking->end_date)) + 1 }} days</p>
                            </div>
                            @if($selectedBooking->distance_km)
                                <div>
                                    <span class="text-gray-500">Distance:</span>
                                    <p class="font-medium">{{ number_format($selectedBooking->distance_km, 0) }} km</p>
                                </div>
                            @endif
                            @if($selectedBooking->pickup_location)
                                <div class="col-span-2">
                                    <span class="text-gray-500">Pickup Location:</span>
                                    <p class="font-medium">{{ $selectedBooking->pickup_location }}</p>
                                </div>
                            @endif
                            @if($selectedBooking->dropoff_location)
                                <div class="col-span-2">
                                    <span class="text-gray-500">Drop-off Location:</span>
                                    <p class="font-medium">{{ $selectedBooking->dropoff_location }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Driver Information --}}
                    @if($selectedBooking->driver)
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3">Driver Information</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Driver:</span>
                                    <p class="font-medium">{{ $selectedBooking->driver->name }}</p>
                                </div>
                                @if($selectedBooking->driver->email)
                                    <div>
                                        <span class="text-gray-500">Email:</span>
                                        <p class="font-medium">{{ $selectedBooking->driver->email }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Financial Information --}}
                    @if($selectedBooking->invoice)
                        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-3">Financial Details</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Invoice Total:</span>
                                    <p class="font-medium text-lg">N${{ number_format($selectedBooking->invoice->total, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-500">Invoice Status:</span>
                                    <p class="font-medium">
                                        <flux:badge
                                            :color="match($selectedBooking->invoice->status) {
                                                'paid' => 'green',
                                                'partial' => 'yellow',
                                                'unpaid' => 'red',
                                                'overdue' => 'red',
                                                default => 'gray'
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst($selectedBooking->invoice->status) }}
                                        </flux:badge>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if($selectedBooking->notes)
                        <div class="bg-gray-50 dark:bg-zinc-800 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedBooking->notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex gap-3 mt-6 justify-end">
                    <flux:button variant="ghost" wire:click="closeModal">Close</flux:button>
                    @can('edit-bookings')
                        <flux:button variant="primary" wire:navigate href="{{ route('bookings.edit', $selectedBooking) }}">
                            Edit Booking
                        </flux:button>
                    @endcan
                </div>
            </div>
        </flux:modal>
    @endif
</div>

