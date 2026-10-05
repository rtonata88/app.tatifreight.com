<?php

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\CompanySetting;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {

    public $dateFrom;
    public $dateTo;
    public $groupBy = 'month'; // month, quarter, none

    public function mount()
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->endOfYear()->format('Y-m-d');
    }

    public function with(): array
    {
        // VAT Output (Sales - from Invoices)
        $invoices = Invoice::whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        $vatOutput = [
            'total_sales' => $invoices->sum('subtotal'),
            'vat_collected' => $invoices->sum('tax_amount'),
            'total_with_vat' => $invoices->sum('total'),
        ];

        // VAT Input (Purchases - from Expenses with VAT)
        // Assuming expenses include VAT in the amount
        $expenses = Expense::whereBetween('expense_date', [$this->dateFrom, $this->dateTo])
            ->where('status', 'approved')
            ->get();

        // Calculate VAT from expenses (assuming 15% VAT rate)
        $vatRate = 0.15;
        $totalExpensesWithVat = $expenses->sum('amount');
        $totalExpensesExcludingVat = $totalExpensesWithVat / (1 + $vatRate);
        $vatInput = $totalExpensesWithVat - $totalExpensesExcludingVat;

        $vatInput = [
            'total_purchases' => $totalExpensesExcludingVat,
            'vat_paid' => $vatInput,
            'total_with_vat' => $totalExpensesWithVat,
        ];

        // VAT Payable/Refundable
        $vatPayable = $vatOutput['vat_collected'] - $vatInput['vat_paid'];

        // Breakdown by month
        $monthlyBreakdown = [];
        if ($this->groupBy === 'month') {
            $startDate = Carbon::parse($this->dateFrom);
            $endDate = Carbon::parse($this->dateTo);

            for ($date = $startDate->copy(); $date <= $endDate; $date->addMonth()) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthInvoices = Invoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                    ->whereIn('status', ['paid', 'partial'])
                    ->get();

                $monthExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
                    ->where('status', 'approved')
                    ->get();

                $monthVatOutput = $monthInvoices->sum('tax_amount');
                $monthExpensesWithVat = $monthExpenses->sum('amount');
                $monthExpensesExcludingVat = $monthExpensesWithVat / (1 + $vatRate);
                $monthVatInput = $monthExpensesWithVat - $monthExpensesExcludingVat;

                $monthlyBreakdown[] = [
                    'period' => $date->format('F Y'),
                    'sales' => $monthInvoices->sum('subtotal'),
                    'vat_output' => $monthVatOutput,
                    'purchases' => $monthExpensesExcludingVat,
                    'vat_input' => $monthVatInput,
                    'vat_payable' => $monthVatOutput - $monthVatInput,
                ];
            }
        }

        return [
            'vatOutput' => $vatOutput,
            'vatInput' => $vatInput,
            'vatPayable' => $vatPayable,
            'monthlyBreakdown' => $monthlyBreakdown,
        ];
    }

    public function exportPdf()
    {
        return redirect()->route('reports.vat.pdf', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">VAT Report</flux:heading>

        <div class="flex gap-3">
            <flux:button wire:click="exportPdf" icon="arrow-down-tray" variant="ghost">
                Export to PDF
            </flux:button>
        </div>
    </flux:header>

    {{-- Filters --}}
    <flux:card class="mt-6">
        <div class="flex flex-wrap items-end gap-4">
            <flux:field>
                <flux:label>Date From</flux:label>
                <flux:input type="date" wire:model.live="dateFrom" />
            </flux:field>

            <flux:field>
                <flux:label>Date To</flux:label>
                <flux:input type="date" wire:model.live="dateTo" />
            </flux:field>

            <flux:field>
                <flux:label>Group By</flux:label>
                <flux:select wire:model.live="groupBy">
                    <option value="none">No Grouping</option>
                    <option value="month">By Month</option>
                    <option value="quarter">By Quarter</option>
                </flux:select>
            </flux:field>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- VAT Output (Collected) --}}
        <flux:card class="bg-green-50">
            <div class="text-sm text-gray-600 mb-1">VAT Output (Collected)</div>
            <div class="text-3xl font-bold text-green-700 mb-2">N${{ number_format($vatOutput['vat_collected'], 2) }}</div>
            <div class="text-xs text-gray-500">
                Sales: N${{ number_format($vatOutput['total_sales'], 2) }}
            </div>
        </flux:card>

        {{-- VAT Input (Paid) --}}
        <flux:card class="bg-blue-50">
            <div class="text-sm text-gray-600 mb-1">VAT Input (Paid)</div>
            <div class="text-3xl font-bold text-blue-700 mb-2">N${{ number_format($vatInput['vat_paid'], 2) }}</div>
            <div class="text-xs text-gray-500">
                Purchases: N${{ number_format($vatInput['total_purchases'], 2) }}
            </div>
        </flux:card>

        {{-- VAT Payable/Refundable --}}
        <flux:card class="{{ $vatPayable >= 0 ? 'bg-red-50' : 'bg-purple-50' }}">
            <div class="text-sm text-gray-600 mb-1">
                {{ $vatPayable >= 0 ? 'VAT Payable' : 'VAT Refundable' }}
            </div>
            <div class="text-3xl font-bold {{ $vatPayable >= 0 ? 'text-red-700' : 'text-purple-700' }} mb-2">
                N${{ number_format(abs($vatPayable), 2) }}
            </div>
            <div class="text-xs text-gray-500">
                Due to {{ $vatPayable >= 0 ? 'Inland Revenue' : 'be Refunded' }}
            </div>
        </flux:card>
    </div>

    {{-- Detailed VAT Calculation --}}
    <flux:card class="mt-6">
        <flux:heading size="lg" class="mb-4">VAT Calculation Summary</flux:heading>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <tbody class="divide-y divide-gray-200">
                    <tr class="bg-green-50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700" colspan="2">
                            OUTPUT VAT (VAT on Sales)
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">Sales (Excluding VAT)</td>
                        <td class="px-4 py-2 text-sm text-right font-medium">N${{ number_format($vatOutput['total_sales'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">VAT @ 15%</td>
                        <td class="px-4 py-2 text-sm text-right font-medium">N${{ number_format($vatOutput['vat_collected'], 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 text-sm font-semibold text-gray-900">Total Sales (Including VAT)</td>
                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">N${{ number_format($vatOutput['total_with_vat'], 2) }}</td>
                    </tr>

                    <tr class="h-4"></tr>

                    <tr class="bg-blue-50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700" colspan="2">
                            INPUT VAT (VAT on Purchases)
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">Purchases (Excluding VAT)</td>
                        <td class="px-4 py-2 text-sm text-right font-medium">N${{ number_format($vatInput['total_purchases'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-700">VAT @ 15%</td>
                        <td class="px-4 py-2 text-sm text-right font-medium">N${{ number_format($vatInput['vat_paid'], 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 text-sm font-semibold text-gray-900">Total Purchases (Including VAT)</td>
                        <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">N${{ number_format($vatInput['total_with_vat'], 2) }}</td>
                    </tr>

                    <tr class="h-4"></tr>

                    <tr class="{{ $vatPayable >= 0 ? 'bg-red-50' : 'bg-purple-50' }}">
                        <td class="px-4 py-3 text-sm font-bold text-gray-900">
                            {{ $vatPayable >= 0 ? 'VAT PAYABLE TO INLAND REVENUE' : 'VAT REFUNDABLE FROM INLAND REVENUE' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 text-lg">
                            N${{ number_format(abs($vatPayable), 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </flux:card>

    {{-- Monthly Breakdown --}}
    @if($groupBy === 'month' && count($monthlyBreakdown) > 0)
        <flux:card class="mt-6">
            <flux:heading size="lg" class="mb-4">Monthly VAT Breakdown</flux:heading>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Period</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Sales</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">VAT Output</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Purchases</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">VAT Input</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">VAT Payable</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($monthlyBreakdown as $month)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $month['period'] }}</td>
                                <td class="px-4 py-3 text-sm text-right">N${{ number_format($month['sales'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-700">N${{ number_format($month['vat_output'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right">N${{ number_format($month['purchases'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-blue-700">N${{ number_format($month['vat_input'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium {{ $month['vat_payable'] >= 0 ? 'text-red-700' : 'text-purple-700' }}">
                                    N${{ number_format(abs($month['vat_payable']), 2) }}
                                    @if($month['vat_payable'] < 0)
                                        <span class="text-xs">(Refund)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">TOTAL</td>
                            <td class="px-4 py-3 text-sm text-right font-bold">N${{ number_format($vatOutput['total_sales'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-green-700">N${{ number_format($vatOutput['vat_collected'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold">N${{ number_format($vatInput['total_purchases'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-blue-700">N${{ number_format($vatInput['vat_paid'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold {{ $vatPayable >= 0 ? 'text-red-700' : 'text-purple-700' }}">
                                N${{ number_format(abs($vatPayable), 2) }}
                                @if($vatPayable < 0)
                                    <span class="text-xs">(Refund)</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </flux:card>
    @endif

    {{-- Information Note --}}
    <flux:card class="mt-6 bg-blue-50">
        <div class="flex gap-3">
            <div class="text-blue-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="text-sm text-blue-900">
                <p class="font-semibold mb-1">VAT Calculation Notes:</p>
                <ul class="list-disc list-inside space-y-1 text-blue-800">
                    <li>VAT Output is calculated from paid and partially paid invoices</li>
                    <li>VAT Input is calculated from approved expenses (assuming VAT is included in expense amounts)</li>
                    <li>Standard VAT rate of 15% is applied</li>
                    <li>This report should be reviewed by your accountant before submission to Inland Revenue</li>
                </ul>
            </div>
        </div>
    </flux:card>
</div>

