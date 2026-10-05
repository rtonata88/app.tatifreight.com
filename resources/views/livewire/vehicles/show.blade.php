<?php

use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {

    public Vehicle $vehicle;

    public function mount(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
    }

    public function with(): array
    {
        $vehicle = Vehicle::with([
            'vehicleType',
            'mdcRateCard',
            'bookings' => function($query) {
                $query->orderBy('start_date', 'desc')->limit(10);
            },
            'bookings.client',
            'mdcCalculations' => function($query) {
                $query->orderBy('calculation_date', 'desc')->limit(10);
            },
            'mdcCalculations.logbook',
            'expenses' => function($query) {
                $query->orderBy('expense_date', 'desc')->limit(10);
            },
            'inspections' => function($query) {
                $query->orderBy('inspection_date', 'desc')->limit(5);
            },
            'inspections.inspector',
            'logbooks' => function($query) {
                $query->orderBy('date', 'desc')->limit(10);
            },
            'logbooks.driver',
        ])->findOrFail($this->vehicle->id);

        // Calculate statistics
        $totalBookings = $vehicle->bookings()->count();
        $totalMdcCharges = $vehicle->mdcCalculations()->sum('mdc_amount');
        $totalMdcPaid = $vehicle->mdcCalculations()->sum('amount_paid');
        $totalExpenses = $vehicle->expenses()->where('status', 'approved')->sum('amount');
        $totalDistance = $vehicle->logbooks()->sum(DB::raw('end_odometer - start_odometer'));

        return [
            'vehicle' => $vehicle,
            'stats' => [
                'total_bookings' => $totalBookings,
                'total_mdc_charges' => $totalMdcCharges,
                'total_mdc_paid' => $totalMdcPaid,
                'total_mdc_outstanding' => $totalMdcCharges - $totalMdcPaid,
                'total_expenses' => $totalExpenses,
                'total_distance' => $totalDistance,
            ],
        ];
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Vehicle Details</flux:heading>

        <div class="flex gap-3">
            <flux:button wire:navigate href="{{ route('vehicles.index') }}" variant="ghost" icon="arrow-left">
                Back to Fleet
            </flux:button>
            @can('edit-vehicles')
                <flux:button wire:navigate href="{{ route('vehicles.edit', $vehicle) }}" variant="primary" icon="pencil">
                    Edit Vehicle
                </flux:button>
            @endcan
        </div>
    </flux:header>

    <div class="mt-6 space-y-6">
        {{-- Vehicle Header Card --}}
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-4">
                        <h2 class="text-2xl font-bold">{{ $vehicle->reg_number }}</h2>
                        <flux:badge
                            :color="match($vehicle->status) {
                                'available' => 'green',
                                'in_use' => 'blue',
                                'maintenance' => 'yellow',
                                'retired' => 'red',
                                default => 'gray'
                            }"
                            size="lg"
                        >
                            {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                        </flux:badge>
                    </div>
                    <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                        {{ $vehicle->make }} {{ $vehicle->model }}
                        @if($vehicle->year)
                            <span class="text-gray-500">({{ $vehicle->year }})</span>
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-1">{{ $vehicle->vehicleType->name }}</p>
                </div>
                @if($vehicle->photos && count($vehicle->photos) > 0)
                    <div class="w-32 h-32 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        <img src="{{ Storage::url($vehicle->photos[0]) }}" alt="{{ $vehicle->reg_number }}" class="w-full h-full object-cover">
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="bg-blue-50 dark:bg-blue-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Bookings</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $stats['total_bookings'] }}</p>
                    </div>
                    <div class="text-blue-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </flux:card>

            <flux:card class="bg-purple-50 dark:bg-purple-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">MDC Outstanding</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">N${{ number_format($stats['total_mdc_outstanding'], 2) }}</p>
                    </div>
                    <div class="text-purple-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </flux:card>

            <flux:card class="bg-green-50 dark:bg-green-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Expenses</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">N${{ number_format($stats['total_expenses'], 2) }}</p>
                    </div>
                    <div class="text-green-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </flux:card>

            <flux:card class="bg-amber-50 dark:bg-amber-900/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Distance</p>
                        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($stats['total_distance'], 0) }} km</p>
                    </div>
                    <div class="text-amber-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Vehicle Details --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Basic Information --}}
                <flux:card>
                    <flux:heading size="lg">Basic Information</flux:heading>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Registration Number</span>
                            <p class="font-medium">{{ $vehicle->reg_number }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Vehicle Type</span>
                            <p class="font-medium">{{ $vehicle->vehicleType->name }}</p>
                        </div>
                        @if($vehicle->vin)
                            <div>
                                <span class="text-sm text-gray-500">VIN</span>
                                <p class="font-medium">{{ $vehicle->vin }}</p>
                            </div>
                        @endif
                        @if($vehicle->make)
                            <div>
                                <span class="text-sm text-gray-500">Make</span>
                                <p class="font-medium">{{ $vehicle->make }}</p>
                            </div>
                        @endif
                        @if($vehicle->model)
                            <div>
                                <span class="text-sm text-gray-500">Model</span>
                                <p class="font-medium">{{ $vehicle->model }}</p>
                            </div>
                        @endif
                        @if($vehicle->year)
                            <div>
                                <span class="text-sm text-gray-500">Year</span>
                                <p class="font-medium">{{ $vehicle->year }}</p>
                            </div>
                        @endif
                        <div>
                            <span class="text-sm text-gray-500">Current Mileage</span>
                            <p class="font-medium">{{ number_format($vehicle->current_mileage, 0) }} km</p>
                        </div>
                        @if($vehicle->gps_device_id)
                            <div>
                                <span class="text-sm text-gray-500">GPS Device ID</span>
                                <p class="font-medium">{{ $vehicle->gps_device_id }}</p>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Specifications --}}
                <flux:card>
                    <flux:heading size="lg">Specifications</flux:heading>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        @if($vehicle->gvm_tonnes)
                            <div>
                                <span class="text-sm text-gray-500">GVM (Gross Vehicle Mass)</span>
                                <p class="font-medium">{{ number_format($vehicle->gvm_tonnes, 2) }} tonnes</p>
                            </div>
                        @endif
                        @if($vehicle->load_capacity)
                            <div>
                                <span class="text-sm text-gray-500">Load Capacity</span>
                                <p class="font-medium">{{ number_format($vehicle->load_capacity, 2) }} tonnes</p>
                            </div>
                        @endif
                        @if($vehicle->tare_weight)
                            <div>
                                <span class="text-sm text-gray-500">Tare Weight</span>
                                <p class="font-medium">{{ number_format($vehicle->tare_weight, 2) }} kg</p>
                            </div>
                        @endif
                        @if($vehicle->mdcRateCard)
                            <div>
                                <span class="text-sm text-gray-500">MDC Rate Card</span>
                                <p class="font-medium">{{ $vehicle->mdcRateCard->category_name }}</p>
                                <p class="text-xs text-gray-500">N${{ number_format($vehicle->mdcRateCard->rate_per_100km, 2) }} per 100km</p>
                            </div>
                        @elseif($vehicle->getEffectiveMdcRateCard())
                            <div>
                                <span class="text-sm text-gray-500">MDC Rate Card (Suggested)</span>
                                <p class="font-medium">{{ $vehicle->getEffectiveMdcRateCard()->category_name }}</p>
                                <p class="text-xs text-gray-500">N${{ number_format($vehicle->getEffectiveMdcRateCard()->rate_per_100km, 2) }} per 100km</p>
                            </div>
                        @endif
                    </div>
                </flux:card>

                {{-- Compliance & Expiry Dates --}}
                <flux:card>
                    <flux:heading size="lg">Compliance & Expiry Dates</flux:heading>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Insurance Expiry</span>
                            @if($vehicle->insurance_expiry)
                                <p class="font-medium {{ $vehicle->insurance_expiry->isPast() ? 'text-red-600' : ($vehicle->insurance_expiry->isBefore(now()->addDays(30)) ? 'text-amber-600' : '') }}">
                                    {{ $vehicle->insurance_expiry->format('d M Y') }}
                                </p>
                                @if($vehicle->insurance_expiry->isPast())
                                    <flux:badge color="red" size="sm">Expired</flux:badge>
                                @elseif($vehicle->insurance_expiry->isBefore(now()->addDays(30)))
                                    <flux:badge color="yellow" size="sm">Expiring Soon</flux:badge>
                                @endif
                            @else
                                <p class="text-gray-400">Not set</p>
                            @endif
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">License Disc Expiry</span>
                            @if($vehicle->disc_expiry)
                                <p class="font-medium {{ $vehicle->disc_expiry->isPast() ? 'text-red-600' : ($vehicle->disc_expiry->isBefore(now()->addDays(30)) ? 'text-amber-600' : '') }}">
                                    {{ $vehicle->disc_expiry->format('d M Y') }}
                                </p>
                                @if($vehicle->disc_expiry->isPast())
                                    <flux:badge color="red" size="sm">Expired</flux:badge>
                                @elseif($vehicle->disc_expiry->isBefore(now()->addDays(30)))
                                    <flux:badge color="yellow" size="sm">Expiring Soon</flux:badge>
                                @endif
                            @else
                                <p class="text-gray-400">Not set</p>
                            @endif
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Roadworthy Expiry</span>
                            @if($vehicle->roadworthy_expiry)
                                <p class="font-medium {{ $vehicle->roadworthy_expiry->isPast() ? 'text-red-600' : ($vehicle->roadworthy_expiry->isBefore(now()->addDays(30)) ? 'text-amber-600' : '') }}">
                                    {{ $vehicle->roadworthy_expiry->format('d M Y') }}
                                </p>
                                @if($vehicle->roadworthy_expiry->isPast())
                                    <flux:badge color="red" size="sm">Expired</flux:badge>
                                @elseif($vehicle->roadworthy_expiry->isBefore(now()->addDays(30)))
                                    <flux:badge color="yellow" size="sm">Expiring Soon</flux:badge>
                                @endif
                            @else
                                <p class="text-gray-400">Not set</p>
                            @endif
                        </div>
                        @if($vehicle->next_service_date)
                            <div>
                                <span class="text-sm text-gray-500">Next Service Date</span>
                                <p class="font-medium {{ $vehicle->next_service_date->isPast() ? 'text-red-600' : ($vehicle->next_service_date->isBefore(now()->addDays(30)) ? 'text-amber-600' : '') }}">
                                    {{ $vehicle->next_service_date->format('d M Y') }}
                                </p>
                                @if($vehicle->next_service_date)
                                    @if($vehicle->next_service_date->isPast())
                                        <flux:badge color="red" size="sm">Overdue</flux:badge>
                                    @elseif($vehicle->next_service_date->isBefore(now()->addDays(30)))
                                        <flux:badge color="yellow" size="sm">Due Soon</flux:badge>
                                    @endif
                                @endif
                                @if($vehicle->next_service_mileage)
                                    <p class="text-xs text-gray-500">or at {{ number_format($vehicle->next_service_mileage, 0) }} km</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </flux:card>

                @if($vehicle->notes)
                    <flux:card>
                        <flux:heading size="lg">Notes</flux:heading>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $vehicle->notes }}</p>
                    </flux:card>
                @endif
            </div>

            {{-- Right Column: Related Data --}}
            <div class="space-y-6">
                {{-- Recent Bookings --}}
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Recent Bookings</flux:heading>
                        <flux:button wire:navigate href="{{ route('bookings.index', ['vehicle' => $vehicle->id]) }}" variant="ghost" size="sm">
                            View All
                        </flux:button>
                    </div>
                    @if($vehicle->bookings->count() > 0)
                        <div class="space-y-3">
                            @foreach($vehicle->bookings as $booking)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium">{{ $booking->booking_number }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->client->company_name ?: $booking->client->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $booking->start_date->format('d M Y') }}</p>
                                        </div>
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
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No bookings yet</p>
                    @endif
                </flux:card>

                {{-- Recent Logbook Entries --}}
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Recent Logbook Entries</flux:heading>
                        <flux:button wire:navigate href="{{ route('logbook.index', ['vehicle' => $vehicle->id]) }}" variant="ghost" size="sm">
                            View All
                        </flux:button>
                    </div>
                    @if($vehicle->logbooks->count() > 0)
                        <div class="space-y-3">
                            @foreach($vehicle->logbooks as $logbook)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium">{{ $logbook->origin_from }} → {{ $logbook->origin_to }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $logbook->driver->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $logbook->date->format('d M Y') }} • {{ number_format($logbook->distance_travelled, 0) }} km</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No logbook entries yet</p>
                    @endif
                </flux:card>

                {{-- Recent MDC Charges --}}
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Recent MDC Charges</flux:heading>
                        <flux:button wire:navigate href="{{ route('mdc.index', ['vehicle' => $vehicle->id]) }}" variant="ghost" size="sm">
                            View All
                        </flux:button>
                    </div>
                    @if($vehicle->mdcCalculations->count() > 0)
                        <div class="space-y-3">
                            @foreach($vehicle->mdcCalculations as $mdc)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium">N${{ number_format($mdc->mdc_amount, 2) }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($mdc->distance_km, 0) }} km</p>
                                            <p class="text-xs text-gray-500">{{ $mdc->calculation_date->format('d M Y') }}</p>
                                        </div>
                                        <flux:badge
                                            :color="match($mdc->payment_status) {
                                                'paid' => 'green',
                                                'partially_paid' => 'yellow',
                                                'unpaid' => 'red',
                                                default => 'gray'
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $mdc->payment_status)) }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No MDC charges yet</p>
                    @endif
                </flux:card>

                {{-- Recent Inspections --}}
                @if($vehicle->inspections->count() > 0)
                    <flux:card>
                        <flux:heading size="lg">Recent Inspections</flux:heading>
                        <div class="mt-4 space-y-3">
                            @foreach($vehicle->inspections as $inspection)
                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium">{{ $inspection->inspection_type }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $inspection->inspector->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-500">{{ $inspection->inspection_date->format('d M Y') }}</p>
                                        </div>
                                        <flux:badge
                                            :color="$inspection->passed ? 'green' : 'red'"
                                            size="sm"
                                        >
                                            {{ $inspection->passed ? 'Passed' : 'Failed' }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </flux:card>
                @endif
            </div>
        </div>
    </div>
</div>

