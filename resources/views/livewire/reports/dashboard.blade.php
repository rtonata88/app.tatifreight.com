<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\Vehicle;
use App\Models\Client;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')] class extends Component {

    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        // Revenue metrics
        $totalRevenue = Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $pendingRevenue = Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('amount_due');

        $overdueRevenue = Invoice::whereBetween('due_date', [$dateFrom, $dateTo])
            ->where('status', 'overdue')
            ->sum('amount_due');

        // Expense metrics
        $totalExpenses = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->sum('amount');

        $pendingExpenses = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
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
            ->get();

        // Revenue by month (last 6 months)
        $monthlyRevenue = Invoice::select(
                DB::raw('DATE_FORMAT(invoice_date, "%Y-%m") as month'),
                DB::raw('SUM(amount_paid) as revenue')
            )
            ->where('invoice_date', '>=', now()->subMonths(6))
            ->whereIn('status', ['paid', 'partial'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Expenses by category
        $expensesByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->where('status', 'approved')
            ->groupBy('category')
            ->get();

        // Vehicle maintenance alerts
        $maintenanceAlerts = Vehicle::where(function($query) {
                $query->whereDate('insurance_expiry', '<=', now()->addDays(30))
                      ->orWhereDate('disc_expiry', '<=', now()->addDays(30));
            })
            ->whereNotIn('status', ['retired'])
            ->with('vehicleType')
            ->get();

        return [
            'totalRevenue' => $totalRevenue,
            'pendingRevenue' => $pendingRevenue,
            'overdueRevenue' => $overdueRevenue,
            'totalExpenses' => $totalExpenses,
            'pendingExpenses' => $pendingExpenses,
            'profit' => $profit,
            'profitMargin' => $profitMargin,
            'completedBookings' => $completedBookings,
            'activeBookings' => $activeBookings,
            'utilizationRate' => $utilizationRate,
            'totalVehicles' => $totalVehicles,
            'vehiclesInUse' => $vehiclesInUse,
            'topClients' => $topClients,
            'monthlyRevenue' => $monthlyRevenue,
            'expensesByCategory' => $expensesByCategory,
            'maintenanceAlerts' => $maintenanceAlerts,
        ];
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Analytics Dashboard</flux:heading>

        <div class="flex gap-3">
            <flux:input wire:model.live="dateFrom" type="date" />
            <flux:input wire:model.live="dateTo" type="date" />
        </div>
    </flux:header>

    {{-- Key Metrics --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card class="bg-green-50">
            <div>
                <p class="text-sm text-gray-600">Total Revenue</p>
                <p class="text-2xl font-bold text-green-700">N${{  number_format($totalRevenue, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Paid invoices</p>
            </div>
        </flux:card>

        <flux:card class="bg-red-50">
            <div>
                <p class="text-sm text-gray-600">Total Expenses</p>
                <p class="text-2xl font-bold text-red-700">N${{  number_format($totalExpenses, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Approved expenses</p>
            </div>
        </flux:card>

        <flux:card class="{{ $profit >= 0 ? 'bg-blue-50' : 'bg-orange-50' }}">
            <div>
                <p class="text-sm text-gray-600">Net Profit</p>
                <p class="text-2xl font-bold {{ $profit >= 0 ? 'text-blue-700' : 'text-orange-700' }}">
                    N${{  number_format($profit, 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">{{ number_format($profitMargin, 1) }}% margin</p>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50">
            <div>
                <p class="text-sm text-gray-600">Fleet Utilization</p>
                <p class="text-2xl font-bold text-purple-700">{{ number_format($utilizationRate, 1) }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ $vehiclesInUse }}/{{ $totalVehicles }} in use</p>
            </div>
        </flux:card>
    </div>

    {{-- Revenue & Bookings --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <flux:card>
            <flux:heading size="lg">Revenue Status</flux:heading>
            <div class="mt-6 space-y-4">
                <div class="flex justify-between items-center p-4 bg-green-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Collected</p>
                        <p class="text-lg font-bold text-green-700">N${{  number_format($totalRevenue, 2) }}</p>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-yellow-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-lg font-bold text-yellow-700">N${{  number_format($pendingRevenue, 2) }}</p>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-red-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Overdue</p>
                        <p class="text-lg font-bold text-red-700">N${{  number_format($overdueRevenue, 2) }}</p>
                    </div>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Booking Activity</flux:heading>
            <div class="mt-6 space-y-4">
                <div class="flex justify-between items-center p-4 bg-blue-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Completed Bookings</p>
                        <p class="text-lg font-bold text-blue-700">{{ $completedBookings }}</p>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-purple-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Active Bookings</p>
                        <p class="text-lg font-bold text-purple-700">{{ $activeBookings }}</p>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded">
                    <div>
                        <p class="text-sm text-gray-600">Avg Revenue per Booking</p>
                        <p class="text-lg font-bold text-gray-700">
                            N${{  $completedBookings > 0 ? number_format($totalRevenue / $completedBookings, 2) : '0.00' }}
                        </p>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- Top Clients --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">Top Clients by Revenue</flux:heading>
        <div class="mt-6">
            @if($topClients->count() > 0)
                <flux:table>
                    <flux:columns>
                        <flux:column>Rank</flux:column>
                        <flux:column>Client</flux:column>
                        <flux:column>Company</flux:column>
                        <flux:column>Revenue</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach($topClients as $index => $item)
                            <flux:row :key="$item->client_id">
                                <flux:cell>
                                    <flux:badge color="{{ $index === 0 ? 'yellow' : 'gray' }}" size="sm">
                                        #{{ $index + 1 }}
                                    </flux:badge>
                                </flux:cell>
                                <flux:cell class="font-medium">{{ $item->client->name }}</flux:cell>
                                <flux:cell class="text-gray-600">{{ $item->client->company_name ?: '-' }}</flux:cell>
                                <flux:cell class="font-bold text-green-600">N${{  number_format($item->total_revenue, 2) }}</flux:cell>
                            </flux:row>
                        @endforeach
                    </flux:rows>
                </flux:table>
            @else
                <p class="text-center text-gray-500 py-8">No revenue data for selected period</p>
            @endif
        </div>
    </flux:card>

    {{-- Expenses Breakdown --}}
    <flux:card class="mt-6">
        <flux:heading size="lg">Expenses by Category</flux:heading>
        <div class="mt-6">
            @if($expensesByCategory->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($expensesByCategory as $expense)
                        <div class="p-4 bg-gray-50 rounded border border-gray-200">
                            <p class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $expense->category) }}</p>
                            <p class="text-lg font-bold text-gray-900">N${{  number_format($expense->total, 2) }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $totalExpenses > 0 ? number_format(($expense->total / $totalExpenses) * 100, 1) : 0 }}% of total
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-500 py-8">No expense data for selected period</p>
            @endif
        </div>
    </flux:card>

    {{-- Maintenance Alerts --}}
    @if($maintenanceAlerts->count() > 0)
        <flux:card class="mt-6 bg-red-50 border-red-200">
            <flux:heading size="lg" class="text-red-900">Maintenance Alerts</flux:heading>
            <div class="mt-6">
                <flux:table>
                    <flux:columns>
                        <flux:column>Vehicle</flux:column>
                        <flux:column>Type</flux:column>
                        <flux:column>Alert</flux:column>
                        <flux:column>Expiry Date</flux:column>
                        <flux:column>Days Remaining</flux:column>
                    </flux:columns>
                    <flux:rows>
                        @foreach($maintenanceAlerts as $vehicle)
                            @if($vehicle->insurance_expiry && $vehicle->insurance_expiry->lte(now()->addDays(30)))
                                <flux:row :key="'ins-'.$vehicle->id">
                                    <flux:cell class="font-medium">{{ $vehicle->reg_number }}</flux:cell>
                                    <flux:cell>{{ $vehicle->vehicleType->name }}</flux:cell>
                                    <flux:cell>
                                        <flux:badge color="{{ $vehicle->insurance_expiry->isPast() ? 'red' : 'yellow' }}" size="sm">
                                            Insurance {{ $vehicle->insurance_expiry->isPast() ? 'Expired' : 'Expiring' }}
                                        </flux:badge>
                                    </flux:cell>
                                    <flux:cell class="font-medium">
                                        {{ $vehicle->insurance_expiry->format('d M Y') }}
                                    </flux:cell>
                                    <flux:cell class="{{ $vehicle->insurance_expiry->isPast() ? 'text-red-600' : 'text-yellow-600' }} font-medium">
                                        @if($vehicle->insurance_expiry->isPast())
                                            Expired {{ abs(round($vehicle->insurance_expiry->diffInDays(now()))) }} days ago
                                        @else
                                            {{ round($vehicle->insurance_expiry->diffInDays(now())) }} days
                                        @endif
                                    </flux:cell>
                                </flux:row>
                            @endif
                            @if($vehicle->disc_expiry && $vehicle->disc_expiry->lte(now()->addDays(30)))
                                <flux:row :key="'disc-'.$vehicle->id">
                                    <flux:cell class="font-medium">{{ $vehicle->reg_number }}</flux:cell>
                                    <flux:cell>{{ $vehicle->vehicleType->name }}</flux:cell>
                                    <flux:cell>
                                        <flux:badge color="{{ $vehicle->disc_expiry->isPast() ? 'red' : 'yellow' }}" size="sm">
                                            License Disc {{ $vehicle->disc_expiry->isPast() ? 'Expired' : 'Expiring' }}
                                        </flux:badge>
                                    </flux:cell>
                                    <flux:cell class="font-medium">
                                        {{ $vehicle->disc_expiry->format('d M Y') }}
                                    </flux:cell>
                                    <flux:cell class="{{ $vehicle->disc_expiry->isPast() ? 'text-red-600' : 'text-yellow-600' }} font-medium">
                                        @if($vehicle->disc_expiry->isPast())
                                            Expired {{ abs(round($vehicle->disc_expiry->diffInDays(now()))) }} days ago
                                        @else
                                            {{ round($vehicle->disc_expiry->diffInDays(now())) }} days
                                        @endif
                                    </flux:cell>
                                </flux:row>
                            @endif
                        @endforeach
                    </flux:rows>
                </flux:table>
            </div>
        </flux:card>
    @endif
</div>
