<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MdcCalculation;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/reports/dashboard (Analytics Dashboard).
 */
class DashboardController extends Controller
{
    /** MDC reminders appear this many days before the due date. */
    private const MDC_REMINDER_DAYS = 14;

    public function __invoke(Request $request): Response
    {
        $dateFrom = (string) ($request->query('dateFrom') ?: now()->startOfMonth()->format('Y-m-d'));
        $dateTo = (string) ($request->query('dateTo') ?: now()->endOfMonth()->format('Y-m-d'));

        // Revenue metrics
        $totalRevenue = (float) Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $pendingRevenue = (float) Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('amount_due');

        $overdueRevenue = (float) Invoice::whereBetween('due_date', [$dateFrom, $dateTo])
            ->where('status', 'overdue')
            ->sum('amount_due');

        // Expense metrics
        $totalExpenses = (float) Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->sum('amount');

        $pendingExpenses = (float) Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'pending')
            ->sum('amount');

        // Profit calculation
        $profit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

        // Booking metrics
        $completedBookings = Booking::whereBetween('end_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->count();

        $activeBookings = Booking::where('status', 'in_progress')->count();

        // Fleet utilization
        $totalVehicles = Vehicle::whereNotIn('status', ['retired'])->count();
        $vehiclesInUse = Vehicle::where('status', 'in_use')->count();
        $utilizationRate = $totalVehicles > 0 ? ($vehiclesInUse / $totalVehicles) * 100 : 0;

        // Top clients by revenue
        $topClients = Invoice::select('client_id', DB::raw('SUM(amount_paid) as total_revenue'))
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->with('client')
            ->groupBy('client_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'client_id' => $item->client_id,
                'name' => $item->client?->name,
                'company_name' => $item->client?->company_name,
                'total_revenue' => (float) $item->total_revenue,
            ])
            ->values();

        // Revenue by month (last 6 months). The old query grouped with MySQL's
        // DATE_FORMAT(invoice_date, "%Y-%m"); grouping in PHP keeps it database-agnostic.
        $monthlyRevenue = Invoice::where('invoice_date', '>=', now()->subMonths(6))
            ->whereIn('status', ['paid', 'partial'])
            ->get(['invoice_date', 'amount_paid'])
            ->groupBy(fn (Invoice $invoice) => $invoice->invoice_date->format('Y-m'))
            ->map(fn ($invoices, $month) => [
                'month' => $month,
                'revenue' => (float) $invoices->sum('amount_paid'),
            ])
            ->sortKeys()
            ->values();

        // Expenses by category
        $expensesByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->groupBy('category')
            ->get()
            ->map(fn ($expense) => [
                'category' => $expense->category,
                'total' => (float) $expense->total,
            ])
            ->values();

        return Inertia::render('reports/dashboard', [
            'filters' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo],
            'totalRevenue' => $totalRevenue,
            'pendingRevenue' => $pendingRevenue,
            'overdueRevenue' => $overdueRevenue,
            'totalExpenses' => $totalExpenses,
            'pendingExpenses' => $pendingExpenses,
            'profit' => $profit,
            'profitMargin' => (float) $profitMargin,
            'completedBookings' => $completedBookings,
            'activeBookings' => $activeBookings,
            'utilizationRate' => (float) $utilizationRate,
            'totalVehicles' => $totalVehicles,
            'vehiclesInUse' => $vehiclesInUse,
            'topClients' => $topClients,
            'monthlyRevenue' => $monthlyRevenue,
            'expensesByCategory' => $expensesByCategory,
            'maintenanceAlerts' => $this->maintenanceAlerts(),
            'mdcReminders' => $request->user()->can('view-mdc') ? $this->mdcReminders() : null,
            'canRecordMdcPayment' => $request->user()->can('manage-mdc'),
        ]);
    }

    /**
     * One row per expiring/expired insurance or licence disc (within 30 days),
     * exactly as the old table rendered them.
     *
     * @return list<array<string, mixed>>
     */
    private function maintenanceAlerts(): array
    {
        $vehicles = Vehicle::where(function ($query) {
            $query->whereDate('insurance_expiry', '<=', now()->addDays(30))
                ->orWhereDate('disc_expiry', '<=', now()->addDays(30));
        })
            ->whereNotIn('status', ['retired'])
            ->with('vehicleType')
            ->get();

        $rows = [];

        foreach ($vehicles as $vehicle) {
            foreach (['insurance' => 'insurance_expiry', 'disc' => 'disc_expiry'] as $kind => $field) {
                /** @var Carbon|null $expiry */
                $expiry = $vehicle->{$field};

                if (! $expiry || ! $expiry->lte(now()->addDays(30))) {
                    continue;
                }

                $rows[] = [
                    'key' => $kind.'-'.$vehicle->id,
                    'vehicle_id' => $vehicle->id,
                    'reg_number' => $vehicle->reg_number,
                    'type' => $vehicle->vehicleType?->name,
                    'kind' => $kind,
                    'label' => $kind === 'insurance' ? 'Insurance' : 'License Disc',
                    'expiry_date' => $expiry->format('Y-m-d'),
                    'expired' => $expiry->isPast(),
                    // Carbon 3's diffInDays() is signed; the old view meant a plain count of days.
                    'days' => (int) abs(round($expiry->diffInDays(now()))),
                ];
            }
        }

        return $rows;
    }

    /**
     * Outstanding MDC per vehicle and month, for months whose charges are overdue or fall due
     * within MDC_REMINDER_DAYS. A month's MDC is due on the company's due day of the next month.
     *
     * @return array{due_day: int|null, has_outstanding: bool, rows: list<array<string, mixed>>}
     */
    private function mdcReminders(): array
    {
        $dueDay = CompanySetting::get()->mdc_payment_due_day;

        $outstanding = MdcCalculation::where('payment_status', '!=', 'paid')
            ->with('vehicle')
            ->get()
            ->filter(fn (MdcCalculation $calculation) => $calculation->outstanding_amount > 0 && $calculation->calculation_date);

        if (! $dueDay) {
            // Without a due day there are no dates to remind about; the page asks for one instead.
            return ['due_day' => null, 'has_outstanding' => $outstanding->isNotEmpty(), 'rows' => []];
        }

        $today = now()->startOfDay();

        $rows = $outstanding
            ->groupBy(fn (MdcCalculation $calculation) => $calculation->vehicle_id.'|'.$calculation->calculation_date->format('Y-m'))
            ->map(function ($calculations) use ($dueDay, $today) {
                /** @var MdcCalculation $first */
                $first = $calculations->first();
                $nextMonth = $first->calculation_date->copy()->startOfMonth()->addMonthNoOverflow();
                $dueDate = $nextMonth->day(min($dueDay, $nextMonth->daysInMonth));

                return [
                    'key' => $first->vehicle_id.'-'.$first->calculation_date->format('Y-m'),
                    'vehicle_id' => $first->vehicle_id,
                    'reg_number' => $first->vehicle?->reg_number,
                    'month' => $first->calculation_date->format('Y-m'),
                    'charges' => $calculations->count(),
                    'outstanding' => round($calculations->sum(fn (MdcCalculation $calculation) => $calculation->outstanding_amount), 2),
                    'due_date' => $dueDate->format('Y-m-d'),
                    'overdue' => $dueDate->lt($today),
                    'days' => (int) abs($today->diffInDays($dueDate)),
                ];
            })
            ->filter(fn (array $row) => $row['due_date'] <= $today->copy()->addDays(self::MDC_REMINDER_DAYS)->format('Y-m-d'))
            ->sortBy([['due_date', 'asc'], ['reg_number', 'asc']])
            ->values()
            ->all();

        return ['due_day' => $dueDay, 'has_outstanding' => $outstanding->isNotEmpty(), 'rows' => $rows];
    }
}
