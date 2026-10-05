<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/reports/vat.
 * The PDF export stays in App\Http\Controllers\VatReportController (reads dateFrom/dateTo).
 */
class VatReportPageController extends Controller
{
    private const VAT_RATE = 0.15;

    public function __invoke(Request $request): Response
    {
        $dateFrom = (string) ($request->query('dateFrom') ?: now()->startOfYear()->format('Y-m-d'));
        $dateTo = (string) ($request->query('dateTo') ?: now()->endOfYear()->format('Y-m-d'));
        $groupBy = in_array($request->query('groupBy'), ['none', 'month', 'quarter'], true)
            ? (string) $request->query('groupBy')
            : 'month';

        // VAT Output (Sales - from Invoices)
        $invoices = Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        $vatOutput = [
            'total_sales' => (float) $invoices->sum('subtotal'),
            'vat_collected' => (float) $invoices->sum('tax_amount'),
            'total_with_vat' => (float) $invoices->sum('total'),
        ];

        // VAT Input (Purchases - approved expenses, VAT assumed included at 15%)
        $expenses = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->get();

        $totalExpensesWithVat = (float) $expenses->sum('amount');
        $totalExpensesExcludingVat = $totalExpensesWithVat / (1 + self::VAT_RATE);

        $vatInput = [
            'total_purchases' => $totalExpensesExcludingVat,
            'vat_paid' => $totalExpensesWithVat - $totalExpensesExcludingVat,
            'total_with_vat' => $totalExpensesWithVat,
        ];

        // VAT Payable/Refundable
        $vatPayable = $vatOutput['vat_collected'] - $vatInput['vat_paid'];

        // Breakdown by month (only for "By Month", as before)
        $monthlyBreakdown = [];
        if ($groupBy === 'month') {
            $startDate = Carbon::parse($dateFrom);
            $endDate = Carbon::parse($dateTo);

            for ($date = $startDate->copy(); $date <= $endDate; $date->addMonth()) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();

                $monthInvoices = Invoice::whereBetween('invoice_date', [$monthStart, $monthEnd])
                    ->whereIn('status', ['paid', 'partial'])
                    ->get();

                $monthExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
                    ->where('status', 'approved')
                    ->get();

                $monthVatOutput = (float) $monthInvoices->sum('tax_amount');
                $monthExpensesWithVat = (float) $monthExpenses->sum('amount');
                $monthExpensesExcludingVat = $monthExpensesWithVat / (1 + self::VAT_RATE);
                $monthVatInput = $monthExpensesWithVat - $monthExpensesExcludingVat;

                $monthlyBreakdown[] = [
                    'period' => $date->format('F Y'),
                    'sales' => (float) $monthInvoices->sum('subtotal'),
                    'vat_output' => $monthVatOutput,
                    'purchases' => $monthExpensesExcludingVat,
                    'vat_input' => $monthVatInput,
                    'vat_payable' => $monthVatOutput - $monthVatInput,
                ];
            }
        }

        return Inertia::render('reports/vat', [
            'filters' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'groupBy' => $groupBy],
            'vatOutput' => $vatOutput,
            'vatInput' => $vatInput,
            'vatPayable' => (float) $vatPayable,
            'monthlyBreakdown' => $monthlyBreakdown,
        ]);
    }
}
