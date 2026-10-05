<?php

use App\Models\MdcCalculation;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $vehicleFilter = '';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = MdcCalculation::with(['logbook.booking.client', 'logbook.driver', 'vehicle.vehicleType'])
            ->when($this->search, function ($q) {
                $q->whereHas('logbook', function ($logbookQuery) {
                    $logbookQuery->where('purpose', 'like', '%' . $this->search . '%')
                                 ->orWhereHas('booking.client', function ($clientQuery) {
                                     $clientQuery->where('name', 'like', '%' . $this->search . '%')
                                                 ->orWhere('company_name', 'like', '%' . $this->search . '%');
                                 });
                })
                ->orWhereHas('vehicle', function ($vehicleQuery) {
                    $vehicleQuery->where('reg_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('calculation_date', [$this->dateFrom, $this->dateTo]);
            })
            ->when($this->vehicleFilter, function ($q) {
                $q->where('vehicle_id', $this->vehicleFilter);
            })
            ->orderBy('calculation_date', 'desc');

        // Calculate statistics
        $statsQuery = MdcCalculation::query()
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('calculation_date', [$this->dateFrom, $this->dateTo]);
            });

        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();

        // Calculate payment statistics
        $totalPaid = MdcCalculation::sum('amount_paid');
        $totalOwed = $statsQuery->sum('mdc_amount');
        $totalOutstanding = $totalOwed - $totalPaid;

        return [
            'mdcCalculations' => $query->paginate(15),
            'vehicles' => Vehicle::orderBy('reg_number')->get(),
            'stats' => [
                'total_accumulated' => $totalOwed,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'total_count' => $statsQuery->count(),
                'this_month' => MdcCalculation::whereBetween('calculation_date', [$thisMonthStart, $thisMonthEnd])
                    ->sum('mdc_amount'),
                'average_per_calculation' => $statsQuery->count() > 0 
                    ? $statsQuery->sum('mdc_amount') / $statsQuery->count() 
                    : 0,
                'total_distance' => $statsQuery->sum('distance_km'),
                'total_mass' => $statsQuery->sum('total_mass'),
            ],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingVehicleFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">MDC Charges</flux:heading>

        {{-- Desktop Buttons --}}
        <div class="hidden md:flex gap-2">
            <flux:button 
                wire:navigate 
                href="{{ route('mdc.record-payment') }}" 
                variant="primary" 
                icon="banknotes"
            >
                Record Payment
            </flux:button>
            <flux:button 
                wire:navigate 
                href="{{ route('mdc.payments') }}" 
                variant="ghost" 
                icon="document-text"
            >
                Payment History
            </flux:button>
            <flux:button 
                wire:navigate 
                href="{{ route('reports.mdc') }}" 
                variant="ghost" 
                icon="chart-bar"
            >
                View Report
            </flux:button>
        </div>

        {{-- Mobile Dropdown --}}
        <div class="md:hidden">
            <flux:dropdown position="right" align="end">
                <flux:button variant="ghost" icon="ellipsis-horizontal" inset="top bottom"></flux:button>

                <flux:menu class="min-w-40">
                    <flux:menu.item
                        wire:navigate
                        href="{{ route('mdc.record-payment') }}"
                        icon="banknotes"
                    >
                        Record Payment
                    </flux:menu.item>

                    <flux:menu.item
                        wire:navigate
                        href="{{ route('mdc.payments') }}"
                        icon="document-text"
                    >
                        Payment History
                    </flux:menu.item>

                    <flux:menu.item
                        wire:navigate
                        href="{{ route('reports.mdc') }}"
                        icon="chart-bar"
                    >
                        View Report
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="bg-red-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Accumulated</p>
                    <p class="text-2xl font-bold text-red-700">N${{ number_format($stats['total_accumulated'], 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Owed to RFANAM</p>
                </div>
                <div class="text-red-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-blue-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">This Month</p>
                    <p class="text-2xl font-bold text-blue-700">N${{ number_format($stats['this_month'], 2) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Current period</p>
                </div>
                <div class="text-blue-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Calculations</p>
                    <p class="text-2xl font-bold text-purple-700">{{ $stats['total_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Avg: N${{ number_format($stats['average_per_calculation'], 2) }}</p>
                </div>
                <div class="text-purple-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Distance</p>
                    <p class="text-2xl font-bold text-green-700">{{ number_format($stats['total_distance'], 0) }} km</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($stats['total_mass'] / 1000, 1) }}t total mass</p>
                </div>
                <div class="text-green-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Warning Alert if amount is high --}}
    @if($stats['total_accumulated'] > 10000)
        <div class="mt-6 p-4 rounded-lg border-l-4 {{ $stats['total_accumulated'] > 50000 ? 'bg-red-50 border-red-500 text-red-800' : ($stats['total_accumulated'] > 25000 ? 'bg-orange-50 border-orange-500 text-orange-800' : 'bg-yellow-50 border-yellow-500 text-yellow-800') }}">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 {{ $stats['total_accumulated'] > 50000 ? 'text-red-400' : ($stats['total_accumulated'] > 25000 ? 'text-orange-400' : 'text-yellow-400') }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">
                        <strong>Attention:</strong> You have accumulated N${{ number_format($stats['total_accumulated'], 2) }} in MDC charges. 
                        Consider making a payment to RFANAM to avoid large outstanding balances.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Client, vehicle, or trip purpose..."
                        icon="magnifying-glass"
                    />
                </flux:field>

                <flux:field>
                    <flux:label>From Date</flux:label>
                    <flux:input wire:model.live="dateFrom" type="date" />
                </flux:field>

                <flux:field>
                    <flux:label>To Date</flux:label>
                    <flux:input wire:model.live="dateTo" type="date" />
                </flux:field>

                <flux:field>
                    <flux:label>Vehicle</flux:label>
                    <flux:select wire:model.live="vehicleFilter">
                        <option value="">All Vehicles</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->reg_number }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Mobile Card View --}}
            <div class="md:hidden space-y-4">
                @forelse($mdcCalculations as $mdc)
                    <flux:card>
                        <div class="space-y-3">
                            {{-- Header with Date and MDC Amount --}}
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-lg text-gray-900">{{ $mdc->calculation_date->format('d M Y') }}</div>
                                    @if($mdc->logbook)
                                        <div class="text-sm text-gray-600">{{ $mdc->logbook->date->format('d M Y') }}</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">MDC Amount</div>
                                    <div class="font-bold text-xl text-red-700">N${{ number_format($mdc->mdc_amount, 2) }}</div>
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div>
                                @if($mdc->payment_status === 'paid')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        ✓ Paid
                                    </span>
                                @elseif($mdc->payment_status === 'partially_paid')
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                        ⚠ Partial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        ✗ Unpaid
                                    </span>
                                @endif
                            </div>

                            {{-- Route --}}
                            @if($mdc->logbook)
                                <div class="border-t pt-3">
                                    <div class="text-sm">
                                        <div class="flex items-center gap-2 mb-1">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="font-medium text-gray-900">{{ $mdc->logbook->origin_from }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="font-medium text-gray-900">{{ $mdc->logbook->origin_to }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-3 border-t pt-3">
                                <div>
                                    <div class="text-xs text-gray-500">Client</div>
                                    @if($mdc->logbook?->booking?->client)
                                        <div class="text-sm font-medium">{{ $mdc->logbook->booking->client->name }}</div>
                                        @if($mdc->logbook->booking->client->company_name)
                                            <div class="text-xs text-gray-500">{{ $mdc->logbook->booking->client->company_name }}</div>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-400">Internal</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Vehicle</div>
                                    @if($mdc->vehicle)
                                        <div class="text-sm font-medium">{{ $mdc->vehicle->reg_number }}</div>
                                        @if($mdc->vehicle->vehicleType)
                                            <div class="text-xs text-gray-500">{{ $mdc->vehicle->vehicleType->name }}</div>
                                        @endif
                                    @else
                                        <div class="text-sm text-gray-400">N/A</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Distance</div>
                                    <div class="text-sm font-medium">{{ number_format($mdc->distance_km, 2) }} km</div>
                                    @if($mdc->gvm_tonnes)
                                        <div class="text-xs text-gray-500">GVM: {{ number_format($mdc->gvm_tonnes, 1) }}t</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Paid</div>
                                    <div class="text-sm font-medium {{ $mdc->amount_paid > 0 ? 'text-green-700' : 'text-gray-500' }}">
                                        N${{ number_format($mdc->amount_paid, 2) }}
                                    </div>
                                </div>
                            </div>

                            {{-- Outstanding Amount --}}
                            @if($mdc->outstanding_amount > 0)
                                <div class="border-t pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Outstanding</span>
                                        <span class="text-lg font-bold text-amber-700">N${{ number_format($mdc->outstanding_amount, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </flux:card>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        No MDC calculations found for the selected period.
                    </div>
                @endforelse

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $mdcCalculations->links() }}
                </div>
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block">
                <flux:table>
                    <flux:columns>
                        <flux:column>Date</flux:column>
                        <flux:column>Logbook Entry</flux:column>
                        <flux:column>Client</flux:column>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Distance</flux:column>
                        <flux:column>MDC Amount</flux:column>
                        <flux:column>Paid</flux:column>
                        <flux:column>Outstanding</flux:column>
                        <flux:column>Status</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse($mdcCalculations as $mdc)
                            <flux:row :key="$mdc->id">
                                <flux:cell>
                                    <div class="text-sm">
                                        {{ $mdc->calculation_date->format('d M Y') }}
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    @if($mdc->logbook)
                                        <div>
                                            <div class="font-medium">{{ $mdc->logbook->date->format('d M Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $mdc->logbook->origin_from }} → {{ $mdc->logbook->origin_to }}</div>
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    @if($mdc->logbook?->booking?->client)
                                        <div>
                                            <div class="font-medium">{{ $mdc->logbook->booking->client->name }}</div>
                                            @if($mdc->logbook->booking->client->company_name)
                                                <div class="text-xs text-gray-500">{{ $mdc->logbook->booking->client->company_name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">Internal</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    @if($mdc->vehicle)
                                        <div>
                                            <div class="font-medium">{{ $mdc->vehicle->reg_number }}</div>
                                            @if($mdc->vehicle->vehicleType)
                                                <div class="text-xs text-gray-500">{{ $mdc->vehicle->vehicleType->name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm">
                                        {{ number_format($mdc->distance_km, 2) }} km
                                        @if($mdc->gvm_tonnes)
                                            <div class="text-xs text-gray-500">GVM: {{ number_format($mdc->gvm_tonnes, 1) }}t</div>
                                        @endif
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="font-bold text-gray-900 dark:text-gray-100">
                                        N${{ number_format($mdc->mdc_amount, 2) }}
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm {{ $mdc->amount_paid > 0 ? 'text-green-700 font-medium' : 'text-gray-500' }}">
                                        N${{ number_format($mdc->amount_paid, 2) }}
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    <div class="text-sm {{ $mdc->outstanding_amount > 0 ? 'text-amber-700 font-medium' : 'text-gray-500' }}">
                                        N${{ number_format($mdc->outstanding_amount, 2) }}
                                    </div>
                                </flux:cell>

                                <flux:cell>
                                    @if($mdc->payment_status === 'paid')
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ✓ Paid
                                        </span>
                                    @elseif($mdc->payment_status === 'partially_paid')
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            ⚠ Partial
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            ✗ Unpaid
                                        </span>
                                    @endif
                                </flux:cell>
                            </flux:row>
                        @empty
                            <flux:row>
                                <flux:cell colspan="9" class="text-center text-gray-500 py-8">
                                    No MDC calculations found for the selected period.
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>

                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $mdcCalculations->links() }}
                </div>
            </div>
        </div>
    </flux:card>
</div>

