<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/reports/profit-loss.
 * The PDF export stays in App\Http\Controllers\ProfitLossController (reads dateFrom/dateTo).
 */
class ProfitLossReportController extends Controller
{
    private const REVENUE_UNITS = [
        'vehicle_rentals' => ['day', 'hour', 'week', 'month'], // Time-based rentals
        'distance_based' => ['trip', 'km'], // Distance/trip-based
        'cargo_services' => ['tonne', 'load', 'pallet', 'container', 'cbm', 'item'], // Cargo-based
    ];

    private const EXPENSE_CATEGORIES = [
        'fuel' => ['fuel'],
        'maintenance' => ['maintenance', 'repairs'],
        'mdc_payment' => ['mdc_payment'],
        'insurance' => ['insurance'],
        'licenses' => ['licenses'],
        'wages' => ['wages'],
        'tolls' => ['tolls'],
        'other' => ['other'],
    ];

    public function __invoke(Request $request): Response
    {
        $dateFrom = (string) ($request->query('dateFrom') ?: now()->startOfYear()->format('Y-m-d'));
        $dateTo = (string) ($request->query('dateTo') ?: now()->endOfYear()->format('Y-m-d'));
        $groupBy = in_array($request->query('groupBy'), ['month', 'quarter', 'year'], true)
            ? (string) $request->query('groupBy')
            : 'month';

        // Revenue breakdown by line-item unit
        $revenue = [];
        foreach (self::REVENUE_UNITS as $key => $units) {
            $revenue[$key] = (float) Invoice::join('invoice_line_items', 'invoices.id', '=', 'invoice_line_items.invoice_id')
                ->whereBetween('invoices.invoice_date', [$dateFrom, $dateTo])
                ->whereIn('invoices.status', ['paid', 'partial'])
                ->whereIn('invoice_line_items.unit', $units)
                ->sum('invoice_line_items.amount');
        }

        $totalRevenue = array_sum($revenue);

        // Expense breakdown
        $expenses = [];
        foreach (self::EXPENSE_CATEGORIES as $key => $categories) {
            $expenses[$key] = (float) Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
                ->where('status', 'approved')
                ->whereIn('category', $categories)
                ->sum('amount');
        }

        $totalExpenses = array_sum($expenses);

        // Calculations
        $grossProfit = $totalRevenue;
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        return Inertia::render('reports/profit-loss', [
            'filters' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'groupBy' => $groupBy],
            'revenue' => $revenue,
            'totalRevenue' => (float) $totalRevenue,
            'expenses' => $expenses,
            'totalExpenses' => (float) $totalExpenses,
            'grossProfit' => (float) $grossProfit,
            'netProfit' => (float) $netProfit,
            'profitMargin' => (float) $profitMargin,
            'trendingData' => $this->trendingData($dateFrom, $dateTo, $groupBy),
        ]);
    }

    /**
     * Collected revenue (amount_paid) per period. The old code grouped with MySQL
     * DATE_FORMAT ('%Y-%m', '%Y-Q%q', '%Y'); this groups in PHP so it works on any driver.
     *
     * @return list<array{period: string, revenue: float}>
     */
    private function trendingData(string $dateFrom, string $dateTo, string $groupBy): array
    {
        return Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->get(['invoice_date', 'amount_paid'])
            ->groupBy(fn (Invoice $invoice) => match ($groupBy) {
                'quarter' => $invoice->invoice_date->format('Y').'-Q'.$invoice->invoice_date->quarter,
                'year' => $invoice->invoice_date->format('Y'),
                default => $invoice->invoice_date->format('Y-m'),
            })
            ->map(fn ($invoices, $period) => [
                'period' => (string) $period,
                'revenue' => (float) $invoices->sum('amount_paid'),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }
}
