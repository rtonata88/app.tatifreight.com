<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VatReportController extends Controller
{
    public function exportPdf(Request $request)
    {
        if (!auth()->user()->can('view-reports')) {
            abort(403, 'Unauthorized action.');
        }

        $dateFrom = $request->input('dateFrom', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('dateTo', now()->endOfYear()->format('Y-m-d'));

        // VAT Output (Sales - from Invoices)
        $invoices = Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        $vatOutput = [
            'total_sales' => $invoices->sum('subtotal'),
            'vat_collected' => $invoices->sum('tax_amount'),
            'total_with_vat' => $invoices->sum('total'),
        ];

        // VAT Input (Purchases - from Expenses with VAT)
        $expenses = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->get();

        // Calculate VAT from expenses (assuming 15% VAT rate)
        $vatRate = 0.15;
        $totalExpensesWithVat = $expenses->sum('amount');
        $totalExpensesExcludingVat = $totalExpensesWithVat / (1 + $vatRate);
        $vatInputAmount = $totalExpensesWithVat - $totalExpensesExcludingVat;

        $vatInput = [
            'total_purchases' => $totalExpensesExcludingVat,
            'vat_paid' => $vatInputAmount,
            'total_with_vat' => $totalExpensesWithVat,
        ];

        // VAT Payable/Refundable
        $vatPayable = $vatOutput['vat_collected'] - $vatInput['vat_paid'];

        // Monthly breakdown
        $monthlyBreakdown = [];
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

        $company = CompanySetting::get();

        $pdf = Pdf::loadView('pdf.vat-report', compact(
            'vatOutput',
            'vatInput',
            'vatPayable',
            'monthlyBreakdown',
            'dateFrom',
            'dateTo',
            'company'
        ));

        $filename = 'VAT-Report-' . $dateFrom . '-to-' . $dateTo . '.pdf';
        return $pdf->download($filename);
    }
}
