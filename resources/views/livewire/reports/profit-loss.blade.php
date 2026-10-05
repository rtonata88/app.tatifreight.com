<?php

use App\Models\Invoice;
use App\Models\Expense;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {

    public $dateFrom;
    public $dateTo;
    public $groupBy = 'month'; // month, quarter, year

    public function mount()
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->endOfYear()->format('Y-m-d');
    }

    public function with(): array
    {
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        // Revenue breakdown by unit
        $revenue = [
            'vehicle_rentals' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['day', 'hour', 'week', 'month']) // Time-based rentals
                ->sum('invoice_line_items.amount'),

            'distance_based' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['trip', 'km']) // Distance/trip-based
                ->sum('invoice_line_items.amount'),

            'cargo_services' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['tonne', 'load', 'pallet', 'container', 'cbm', 'item']) // Cargo-based
                ->sum('invoice_line_items.amount'),
        ];

        $totalRevenue = array_sum($revenue);

        // Expense breakdown
        $expenses = [
            'fuel' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'fuel')
                ->sum('amount'),

            'maintenance' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->whereIn('category', ['maintenance', 'repairs'])
                ->sum('amount'),

            'mdc_payment' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'mdc_payment')
                ->sum('amount'),

            'insurance' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'insurance')
                ->sum('amount'),

            'licenses' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'licenses')
                ->sum('amount'),

            'wages' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'wages')
                ->sum('amount'),

            'tolls' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'tolls')
                ->sum('amount'),

            'other' => Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->where('category', 'other')
                ->sum('amount'),
        ];

        $totalExpenses = array_sum($expenses);

        // Calculations
        $grossProfit = $totalRevenue;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // Trending data
        $dateFormat = match($this->groupBy) {
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m',
        };

        $trendingData = DB::table('invoices')
            ->select(
                DB::raw("DATE_FORMAT(invoice_date, '$dateFormat') as period"),
                DB::raw('SUM(amount_paid) as revenue')
            )
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'revenue' => $revenue,
            'totalRevenue' => $totalRevenue,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'grossProfit' => $grossProfit,
            'netProfit' => $netProfit,
            'profitMargin' => $profitMargin,
            'trendingData' => $trendingData,
        ];
    }

    public function exportPdf()
    {
        // Redirect to PDF export route with date parameters
        return redirect()->route('reports.profit-loss.pdf', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Profit & Loss Statement</flux:heading>

        <div class="flex gap-3">
            <flux:button wire:click="exportPdf" variant="ghost" icon="document-arrow-down">
                Export PDF
            </flux:button>
        </div>
    </flux:header>

    {{-- Date Range Filters --}}
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
                    <option value="month">Monthly</option>
                    <option value="quarter">Quarterly</option>
                    <option value="year">Yearly</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card class="bg-green-50">
            <p class="text-sm text-gray-600">Total Revenue</p>
            <p class="text-3xl font-bold text-green-700">N${{  number_format($totalRevenue, 2) }}</p>
        </flux:card>

        <flux:card class="bg-red-50">
            <p class="text-sm text-gray-600">Total Expenses</p>
            <p class="text-3xl font-bold text-red-700">N${{  number_format($totalExpenses, 2) }}</p>
        </flux:card>

        <flux:card class="{{ $netProfit >= 0 ? 'bg-blue-50' : 'bg-orange-50' }}">
            <p class="text-sm text-gray-600">Net Profit</p>
            <p class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-orange-700' }}">
                N${{  number_format($netProfit, 2) }}
            </p>
            <p class="text-sm text-gray-600 mt-2">
                Margin: {{ number_format($profitMargin, 1) }}%
            </p>
        </flux:card>
    </div>

    {{-- Detailed P&L Statement --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Revenue Section --}}
        <flux:card>
            <flux:heading size="lg" class="text-green-700">Revenue</flux:heading>

            <div class="mt-6 space-y-3">
                <div class="p-3 bg-green-50 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-700 font-medium">Time-Based Rentals</div>
                            <div class="text-xs text-gray-500">Day, Hour, Week, Month</div>
                        </div>
                        <span class="font-semibold text-green-700">N${{  number_format($revenue['vehicle_rentals'], 2) }}</span>
                    </div>
                </div>

                <div class="p-3 bg-green-50 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-700 font-medium">Distance-Based Services</div>
                            <div class="text-xs text-gray-500">Trip, Km</div>
                        </div>
                        <span class="font-semibold text-green-700">N${{  number_format($revenue['distance_based'], 2) }}</span>
                    </div>
                </div>

                <div class="p-3 bg-green-50 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm text-gray-700 font-medium">Cargo Services</div>
                            <div class="text-xs text-gray-500">Tonne, Load, Pallet, Container, etc.</div>
                        </div>
                        <span class="font-semibold text-green-700">N${{  number_format($revenue['cargo_services'], 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between items-center p-4 bg-green-100 rounded border-2 border-green-300">
                    <span class="font-bold text-gray-900">Total Revenue</span>
                    <span class="font-bold text-lg text-green-700">N${{  number_format($totalRevenue, 2) }}</span>
                </div>
            </div>
        </flux:card>

        {{-- Expenses Section --}}
        <flux:card>
            <flux:heading size="lg" class="text-red-700">Operating Expenses</flux:heading>

            <div class="mt-6 space-y-3">
                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Fuel</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['fuel'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Maintenance & Repairs</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['maintenance'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">MDC Payments to RFANAM</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['mdc_payment'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Insurance</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['insurance'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Licenses & Permits</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['licenses'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Driver Wages</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['wages'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Tolls</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['tolls'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                    <span class="text-sm text-gray-700">Other Expenses</span>
                    <span class="font-semibold text-red-700">N${{  number_format($expenses['other'], 2) }}</span>
                </div>

                <div class="flex justify-between items-center p-4 bg-red-100 rounded border-2 border-red-300">
                    <span class="font-bold text-gray-900">Total Expenses</span>
                    <span class="font-bold text-lg text-red-700">N${{  number_format($totalExpenses, 2) }}</span>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Final Summary --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">Profit Summary</flux:heading>

        <div class="mt-6 max-w-2xl mx-auto space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Gross Profit</span>
                <span class="text-lg font-bold text-green-600">N${{  number_format($grossProfit, 2) }}</span>
            </div>

            <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                <span class="text-lg font-medium">Less: Operating Expenses</span>
                <span class="text-lg font-bold text-red-600">N${{  number_format($totalExpenses, 2) }}</span>
            </div>

            <div class="flex justify-between items-center p-6 bg-{{ $netProfit >= 0 ? 'blue' : 'orange' }}-50 rounded border-2 border-{{ $netProfit >= 0 ? 'blue' : 'orange' }}-300">
                <div>
                    <span class="text-xl font-bold text-gray-900">Net Profit</span>
                    <p class="text-sm text-gray-600 mt-1">Profit Margin: {{ number_format($profitMargin, 1) }}%</p>
                </div>
                <span class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-blue-700' : 'text-orange-700' }}">
                    N${{  number_format($netProfit, 2) }}
                </span>
            </div>
        </div>
    </flux:card>
</div>
