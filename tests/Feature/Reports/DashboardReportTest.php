<?php

use App\Models\Client;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/ReportFixtures.php';

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 15)->setTime(12, 0));
});

test('guests cannot see the analytics dashboard', function () {
    $this->get(route('reports.dashboard'))->assertRedirect(route('login'));
});

test('users without view-reports are forbidden', function () {
    $this->actingAs(userWithPermissions([]))
        ->get(route('reports.dashboard'))
        ->assertForbidden();
});

test('dashboard defaults to the current month', function () {
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/dashboard')
            ->where('filters.dateFrom', '2026-03-01')
            ->where('filters.dateTo', '2026-03-31')
            ->where('totalRevenue', 0)
            ->where('profitMargin', 0)
            ->where('utilizationRate', 0)
            ->has('topClients', 0)
            ->has('expensesByCategory', 0)
            ->has('maintenanceAlerts', 0)
        );
});

test('dashboard metrics match the old calculations', function () {
    $user = userWithPermissions(['view-reports']);
    $acme = Client::factory()->create(['name' => 'Acme', 'company_name' => 'Acme Ltd']);
    $beta = Client::factory()->create(['name' => 'Beta', 'company_name' => null]);

    // In range (March 2026)
    reportsInvoice(['client_id' => $acme->id, 'status' => 'paid', 'amount_paid' => 1000, 'amount_due' => 0]);
    reportsInvoice(['client_id' => $beta->id, 'status' => 'partial', 'amount_paid' => 400, 'amount_due' => 600]);
    reportsInvoice(['client_id' => $acme->id, 'status' => 'paid', 'amount_paid' => 250, 'invoice_date' => '2026-03-20']);
    reportsInvoice(['client_id' => $beta->id, 'status' => 'sent', 'amount_paid' => 0, 'amount_due' => 999]); // ignored
    reportsInvoice(['status' => 'overdue', 'amount_due' => 300, 'invoice_date' => '2026-01-10', 'due_date' => '2026-03-05']);
    // Out of range
    reportsInvoice(['client_id' => $acme->id, 'status' => 'paid', 'amount_paid' => 5000, 'invoice_date' => '2026-02-10']);

    reportsExpense(['category' => 'fuel', 'amount' => 500, 'status' => 'approved']);
    reportsExpense(['category' => 'fuel', 'amount' => 100, 'status' => 'approved']);
    reportsExpense(['category' => 'mdc_payment', 'amount' => 300, 'status' => 'approved']);
    reportsExpense(['category' => 'tolls', 'amount' => 70, 'status' => 'pending']);
    reportsExpense(['category' => 'fuel', 'amount' => 900, 'status' => 'approved', 'expense_date' => '2026-04-02']); // out of range

    $type = VehicleType::factory()->create(['name' => 'Tipper']);
    $inUse = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'in_use']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'in_use']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'available']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'retired']);

    reportsBooking(['vehicle_id' => $inUse->id, 'status' => 'completed']);
    reportsBooking(['vehicle_id' => $inUse->id, 'status' => 'completed', 'end_date' => '2026-03-28 10:00:00']);
    reportsBooking(['vehicle_id' => $inUse->id, 'status' => 'completed', 'end_date' => '2026-02-20 10:00:00']); // out of range
    reportsBooking(['vehicle_id' => $inUse->id, 'status' => 'in_progress']);

    $this->actingAs($user)
        ->get(route('reports.dashboard', ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/dashboard')
            ->where('totalRevenue', 1650)            // 1000 + 400 + 250
            ->where('pendingRevenue', 600)           // partial amount_due ('unpaid' is not a real status)
            ->where('overdueRevenue', 300)           // by due_date
            ->where('totalExpenses', 900)            // 500 + 100 + 300
            ->where('pendingExpenses', 70)
            ->where('profit', 750)
            ->where('profitMargin', fn ($v) => abs($v - (750 / 1650 * 100)) < 1e-9)
            ->where('completedBookings', 2)
            ->where('activeBookings', 1)
            ->where('totalVehicles', 3)
            ->where('vehiclesInUse', 2)
            ->where('utilizationRate', fn ($v) => abs($v - (2 / 3 * 100)) < 1e-9)
            ->has('topClients', 2)
            ->where('topClients.0.name', 'Acme')
            ->where('topClients.0.company_name', 'Acme Ltd')
            ->where('topClients.0.total_revenue', 1250)
            ->where('topClients.1.name', 'Beta')
            ->where('topClients.1.total_revenue', 400)
            ->has('expensesByCategory', 2)
            ->where('expensesByCategory', fn ($rows) => collect($rows)->pluck('total', 'category')->all() == ['fuel' => 600, 'mdc_payment' => 300])
            // Last 6 months of collected revenue: Feb (5000) and Mar (1650)
            ->where('monthlyRevenue', fn ($rows) => collect($rows)->values()->all() == [
                ['month' => '2026-02', 'revenue' => 5000],
                ['month' => '2026-03', 'revenue' => 1650],
            ])
        );
});

test('dashboard lists insurance and licence disc alerts within 30 days', function () {
    $type = VehicleType::factory()->create(['name' => 'Cooler']);

    $expired = Vehicle::factory()->create([
        'vehicle_type_id' => $type->id,
        'reg_number' => 'N 111 W',
        'status' => 'available',
        'insurance_expiry' => '2026-03-10', // 5.5 days ago
        'disc_expiry' => '2026-12-31',
    ]);
    Vehicle::factory()->create([
        'vehicle_type_id' => $type->id,
        'reg_number' => 'N 222 W',
        'status' => 'in_use',
        'insurance_expiry' => '2026-03-27', // in 12 days
        'disc_expiry' => '2026-04-04',      // in 20 days
    ]);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'retired', 'insurance_expiry' => '2026-03-01']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'status' => 'available', 'insurance_expiry' => '2026-06-01', 'disc_expiry' => null]);

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('maintenanceAlerts', 3)
            ->where('maintenanceAlerts.0.key', 'insurance-'.$expired->id)
            ->where('maintenanceAlerts.0.reg_number', 'N 111 W')
            ->where('maintenanceAlerts.0.type', 'Cooler')
            ->where('maintenanceAlerts.0.label', 'Insurance')
            ->where('maintenanceAlerts.0.expired', true)
            ->where('maintenanceAlerts.0.expiry_date', '2026-03-10')
            ->where('maintenanceAlerts.0.days', 6) // 5.5 days (midnight 10th -> noon 15th), rounded like the old view
            ->where('maintenanceAlerts.1.label', 'Insurance')
            ->where('maintenanceAlerts.1.expired', false)
            ->where('maintenanceAlerts.1.days', 12)
            ->where('maintenanceAlerts.2.label', 'License Disc')
            ->where('maintenanceAlerts.2.expired', false)
            ->where('maintenanceAlerts.2.days', 20)
        );
});
