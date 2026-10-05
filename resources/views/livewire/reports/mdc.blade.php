<?php

use App\Models\MdcCalculation;
use App\Models\Vehicle;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {

    public $dateFrom;
    public $dateTo;
    public $groupBy = 'none'; // none, vehicle, client, month

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        // Summary statistics
        $totalMdc = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->sum('mdc_amount');
        
        $totalPaid = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->sum('amount_paid');
        
        $totalOutstanding = $totalMdc - $totalPaid;
        
        $totalCount = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->count();
        
        $totalDistance = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->sum('distance_km');
        
        $totalMass = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->sum('total_mass');

        // Grouped data based on selection
        $groupedData = [];
        
        if ($this->groupBy === 'vehicle') {
            $groupedData = MdcCalculation::with('vehicle.vehicleType')
                ->whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->select('vehicle_id', 
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(distance_km) as total_distance'),
                    DB::raw('SUM(mdc_amount) as total_amount'))
                ->groupBy('vehicle_id')
                ->get();
        } elseif ($this->groupBy === 'client') {
            $groupedData = MdcCalculation::with('logbook.booking.client')
                ->whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->join('logbooks', 'mdc_calculations.logbook_id', '=', 'logbooks.id')
                ->leftJoin('bookings', 'logbooks.booking_id', '=', 'bookings.id')
                ->select('bookings.client_id', 
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(mdc_calculations.distance_km) as total_distance'),
                    DB::raw('SUM(mdc_calculations.mdc_amount) as total_amount'))
                ->whereNotNull('bookings.client_id')
                ->groupBy('bookings.client_id')
                ->get();
        } elseif ($this->groupBy === 'month') {
            $groupedData = MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->select(
                    DB::raw('DATE_FORMAT(calculation_date, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(distance_km) as total_distance'),
                    DB::raw('SUM(mdc_amount) as total_amount'))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            // Individual calculations
            $groupedData = MdcCalculation::with(['logbook.booking.client', 'logbook.driver', 'vehicle.vehicleType'])
                ->whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->orderBy('calculation_date', 'desc')
                ->get();
        }

        return [
            'totalMdc' => $totalMdc,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'totalCount' => $totalCount,
            'totalDistance' => $totalDistance,
            'totalMass' => $totalMass,
            'avgPerCalculation' => $totalCount > 0 ? $totalMdc / $totalCount : 0,
            'groupedData' => $groupedData,
        ];
    }

    public function exportPdf()
    {
        $this->dispatch('notify',
            type: 'info',
            message: 'PDF export feature coming soon'
        );
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">MDC Report</flux:heading>

        <div class="flex gap-3">
            <flux:button wire:click="exportPdf" variant="ghost" icon="document-arrow-down">
                Export PDF
            </flux:button>
        </div>
    </flux:header>

    {{-- Date Range & Grouping Filters --}}
    <flux:card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:field>
                <flux:label>From Date</flux:label>
                <flux:input wire:model.live="dateFrom" type="date" />
            </flux:field>

            <flux:field>
                <flux:label>To Date</flux:label>
                <flux:input wire:model.live="dateTo" type="date" />
            </flux:field>

            <flux:field>
                <flux:label>Group By</flux:label>
                <flux:select wire:model.live="groupBy">
                    <option value="none">None (All Calculations)</option>
                    <option value="vehicle">By Vehicle</option>
                    <option value="client">By Client</option>
                    <option value="month">By Month</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-6">
        <flux:card class="bg-gray-50">
            <p class="text-sm text-gray-600">Total MDC Amount</p>
            <p class="text-3xl font-bold text-gray-900">N${{ number_format($totalMdc, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total charges</p>
        </flux:card>

        <flux:card class="bg-green-50">
            <p class="text-sm text-gray-600">Total Paid</p>
            <p class="text-3xl font-bold text-green-700">N${{ number_format($totalPaid, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Payments made</p>
        </flux:card>

        <flux:card class="bg-amber-50">
            <p class="text-sm text-gray-600">Outstanding</p>
            <p class="text-3xl font-bold text-amber-700">N${{ number_format($totalOutstanding, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">Still owed</p>
        </flux:card>

        <flux:card class="bg-blue-50">
            <p class="text-sm text-gray-600">Total Calculations</p>
            <p class="text-3xl font-bold text-blue-700">{{ $totalCount }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ number_format($totalDistance, 0) }} km total</p>
        </flux:card>

        <flux:card class="bg-purple-50">
            <p class="text-sm text-gray-600">Total Mass</p>
            <p class="text-3xl font-bold text-purple-700">{{ number_format($totalMass / 1000, 1) }}t</p>
            <p class="text-xs text-gray-500 mt-1">Combined mass</p>
        </flux:card>
    </div>

    {{-- Detailed Breakdown --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">
            @if($groupBy === 'vehicle')
                MDC by Vehicle
            @elseif($groupBy === 'client')
                MDC by Client
            @elseif($groupBy === 'month')
                MDC by Month
            @else
                All MDC Calculations
            @endif
        </flux:heading>

        <div class="mt-6">
            @if($groupBy === 'vehicle')
                {{-- Grouped by Vehicle --}}
                <flux:table>
                    <flux:columns>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Type</flux:column>
                        <flux:column>Calculations</flux:column>
                        <flux:column>Total Distance</flux:column>
                        <flux:column>Total MDC Amount</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @foreach($groupedData as $data)
                            <flux:row>
                                <flux:cell>
                                    <strong>{{ $data->vehicle->reg_number ?? 'N/A' }}</strong>
                                </flux:cell>
                                <flux:cell>
                                    {{ $data->vehicle->vehicleType->name ?? 'N/A' }}
                                </flux:cell>
                                <flux:cell>
                                    {{ $data->count }}
                                </flux:cell>
                                <flux:cell>
                                    {{ number_format($data->total_distance, 2) }} km
                                </flux:cell>
                                <flux:cell>
                                    <strong class="text-red-700">N${{ number_format($data->total_amount, 2) }}</strong>
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>

            @elseif($groupBy === 'client')
                {{-- Grouped by Client --}}
                <flux:table>
                    <flux:columns>
                        <flux:column>Client</flux:column>
                        <flux:column>Calculations</flux:column>
                        <flux:column>Total Distance</flux:column>
                        <flux:column>Total MDC Amount</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @foreach($groupedData as $data)
                            <flux:row>
                                <flux:cell>
                                    @php
                                        $client = \App\Models\Client::find($data->client_id);
                                    @endphp
                                    @if($client)
                                        <div>
                                            <strong>{{ $client->name }}</strong>
                                            @if($client->company_name)
                                                <div class="text-sm text-gray-500">{{ $client->company_name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </flux:cell>
                                <flux:cell>
                                    {{ $data->count }}
                                </flux:cell>
                                <flux:cell>
                                    {{ number_format($data->total_distance, 2) }} km
                                </flux:cell>
                                <flux:cell>
                                    <strong class="text-red-700">N${{ number_format($data->total_amount, 2) }}</strong>
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>

            @elseif($groupBy === 'month')
                {{-- Grouped by Month --}}
                <flux:table>
                    <flux:columns>
                        <flux:column>Month</flux:column>
                        <flux:column>Calculations</flux:column>
                        <flux:column>Total Distance</flux:column>
                        <flux:column>Total MDC Amount</flux:column>
                        <flux:column>Running Total</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @php $runningTotal = 0; @endphp
                        @foreach($groupedData as $data)
                            @php 
                                $runningTotal += $data->total_amount;
                                $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $data->month);
                            @endphp
                            <flux:row>
                                <flux:cell>
                                    <strong>{{ $monthDate->format('F Y') }}</strong>
                                </flux:cell>
                                <flux:cell>
                                    {{ $data->count }}
                                </flux:cell>
                                <flux:cell>
                                    {{ number_format($data->total_distance, 2) }} km
                                </flux:cell>
                                <flux:cell>
                                    <strong class="text-red-700">N${{ number_format($data->total_amount, 2) }}</strong>
                                </flux:cell>
                                <flux:cell>
                                    <strong class="text-gray-700">N${{ number_format($runningTotal, 2) }}</strong>
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>

            @else
                {{-- Individual Calculations --}}
                <flux:table>
                    <flux:columns>
                        <flux:column>Date</flux:column>
                        <flux:column>Logbook Entry</flux:column>
                        <flux:column>Client</flux:column>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Distance</flux:column>
                        <flux:column>GVM</flux:column>
                        <flux:column>MDC Amount</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @foreach($groupedData as $mdc)
                            <flux:row>
                                <flux:cell>
                                    {{ $mdc->calculation_date->format('d M Y') }}
                                </flux:cell>
                                <flux:cell>
                                    @if($mdc->logbook)
                                        <div>
                                            <div class="font-medium">{{ $mdc->logbook->date->format('d M Y') }}</div>
                                            <div class="text-xs text-gray-500">{{ $mdc->logbook->origin_from }} → {{ $mdc->logbook->origin_to }}</div>
                                            @if($mdc->logbook->booking)
                                                <div class="text-xs text-blue-600">Booking: {{ $mdc->logbook->booking->booking_number }}</div>
                                            @endif
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </flux:cell>
                                <flux:cell>
                                    @if($mdc->logbook?->booking?->client)
                                        <div>
                                            <strong>{{ $mdc->logbook->booking->client->name }}</strong>
                                            @if($mdc->logbook->booking->client->company_name)
                                                <div class="text-sm text-gray-500">{{ $mdc->logbook->booking->client->company_name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">Internal</span>
                                    @endif
                                </flux:cell>
                                <flux:cell>
                                    <div>
                                        <strong>{{ $mdc->vehicle->reg_number ?? 'N/A' }}</strong>
                                        @if($mdc->vehicle && $mdc->vehicle->vehicleType)
                                            <div class="text-sm text-gray-500">{{ $mdc->vehicle->vehicleType->name }}</div>
                                        @endif
                                    </div>
                                </flux:cell>
                                <flux:cell>
                                    {{ number_format($mdc->distance_km, 2) }} km
                                </flux:cell>
                                <flux:cell>
                                    @if($mdc->gvm_tonnes)
                                        {{ number_format($mdc->gvm_tonnes, 2) }}t
                                    @else
                                        N/A
                                    @endif
                                </flux:cell>
                                <flux:cell>
                                    <strong class="text-red-700">N${{ number_format($mdc->mdc_amount, 2) }}</strong>
                                </flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            @endif
        </div>
    </flux:card>

    {{-- Summary Footer --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">Period Summary</flux:heading>
        
        <div class="mt-6 max-w-2xl mx-auto space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Total Calculations</span>
                <span class="text-lg font-bold">{{ $totalCount }}</span>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Total Distance Covered</span>
                <span class="text-lg font-bold">{{ number_format($totalDistance, 0) }} km</span>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Total Mass Transported</span>
                <span class="text-lg font-bold">{{ number_format($totalMass / 1000, 1) }} tonnes</span>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Total MDC Charges</span>
                <span class="text-lg font-bold">N${{ number_format($totalMdc, 2) }}</span>
            </div>

            <div class="flex justify-between items-center p-4 bg-green-50 rounded">
                <span class="text-lg font-medium">Total Payments Made</span>
                <span class="text-lg font-bold text-green-700">N${{ number_format($totalPaid, 2) }}</span>
            </div>

            <div class="flex justify-between items-center p-6 bg-amber-50 rounded border-2 border-amber-300">
                <div>
                    <span class="text-xl font-bold text-gray-900">Outstanding Balance to RFANAM</span>
                    <p class="text-sm text-gray-600 mt-1">For period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
                </div>
                <span class="text-2xl font-bold text-amber-700">
                    N${{ number_format($totalOutstanding, 2) }}
                </span>
            </div>
        </div>
    </flux:card>
</div>

