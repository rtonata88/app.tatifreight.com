<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ProfitLossController extends Controller
{
    /**
     * Export Profit & Loss report as PDF
     */
    public function exportPdf(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('view-reports')) {
            abort(403, 'Unauthorized action.');
        }

        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');

        // Revenue breakdown by unit
        $revenue = [
            'vehicle_rentals' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['day', 'hour', 'week', 'month'])
                ->sum('invoice_line_items.amount'),

            'distance_based' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['trip', 'km'])
                ->sum('invoice_line_items.amount'),

            'cargo_services' => Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', ['tonne', 'load', 'pallet', 'container', 'cbm', 'item'])
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

        // Get company settings
        $company = CompanySetting::get();

        $data = compact(
            'dateFrom',
            'dateTo',
            'revenue',
            'totalRevenue',
            'expenses',
            'totalExpenses',
            'grossProfit',
            'netProfit',
            'profitMargin',
            'company'
        );

        // Generate PDF
        $pdf = Pdf::loadView('pdf.profit-loss', $data);

        // Return PDF for download
        $filename = 'Profit-Loss-' . date('Y-m-d', strtotime($dateFrom)) . '-to-' . date('Y-m-d', strtotime($dateTo)) . '.pdf';
        return $pdf->download($filename);
    }
}
