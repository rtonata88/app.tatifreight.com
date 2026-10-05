<?php

use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/ReportFixtures.php';

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 15)->setTime(12, 0));
});

test('guests cannot see the profit and loss report', function () {
    $this->get(route('reports.profit-loss'))->assertRedirect(route('login'));
});

test('users without view-reports are forbidden from the profit and loss report and pdf', function () {
    $user = userWithPermissions([]);

    $this->actingAs($user)->get(route('reports.profit-loss'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.profit-loss.pdf'))->assertForbidden();
});

test('profit and loss defaults to the current year, grouped by month', function () {
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.profit-loss'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/profit-loss')
            ->where('filters.dateFrom', '2026-01-01')
            ->where('filters.dateTo', '2026-12-31')
            ->where('filters.groupBy', 'month')
            ->where('totalRevenue', 0)
            ->where('netProfit', 0)
            ->where('profitMargin', 0)
        );
});

test('profit and loss totals match the old calculations', function () {
    // Paid / partial invoices in range: revenue comes from line items by unit.
    reportsInvoice(['status' => 'paid', 'amount_paid' => 1500, 'invoice_date' => '2026-01-15'], [
        ['unit' => 'day', 'amount' => 1000],
        ['unit' => 'week', 'amount' => 500],
    ]);
    reportsInvoice(['status' => 'partial', 'amount_paid' => 300, 'invoice_date' => '2026-02-15'], [
        ['unit' => 'km', 'amount' => 700],
        ['unit' => 'trip', 'amount' => 300],
        ['unit' => 'tonne', 'amount' => 250],
        ['unit' => 'container', 'amount' => 750],
    ]);
    reportsInvoice(['status' => 'paid', 'amount_paid' => 200, 'invoice_date' => '2026-05-02'], [
        ['unit' => 'hour', 'amount' => 200],
    ]);
    // Ignored: wrong status / out of range
    reportsInvoice(['status' => 'sent', 'invoice_date' => '2026-02-01'], [['unit' => 'day', 'amount' => 9999]]);
    reportsInvoice(['status' => 'paid', 'amount_paid' => 9999, 'invoice_date' => '2025-12-31'], [['unit' => 'day', 'amount' => 9999]]);

    reportsExpense(['category' => 'fuel', 'amount' => 400]);
    reportsExpense(['category' => 'maintenance', 'amount' => 150]);
    reportsExpense(['category' => 'repairs', 'amount' => 50]);
    reportsExpense(['category' => 'mdc_payment', 'amount' => 120]);
    reportsExpense(['category' => 'insurance', 'amount' => 80]);
    reportsExpense(['category' => 'tolls', 'amount' => 30]);
    reportsExpense(['category' => 'other', 'amount' => 20]);
    reportsExpense(['category' => 'driver_wages', 'amount' => 999]); // not one of the P&L lines (old code reads 'wages')
    reportsExpense(['category' => 'fuel', 'amount' => 999, 'status' => 'pending']);
    reportsExpense(['category' => 'fuel', 'amount' => 999, 'expense_date' => '2027-01-01']);

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.profit-loss', ['dateFrom' => '2026-01-01', 'dateTo' => '2026-12-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/profit-loss')
            ->where('revenue.vehicle_rentals', 1700)  // 1000 + 500 + 200
            ->where('revenue.distance_based', 1000)   // 700 + 300
            ->where('revenue.cargo_services', 1000)   // 250 + 750
            ->where('totalRevenue', 3700)
            ->where('expenses.fuel', 400)
            ->where('expenses.maintenance', 200)
            ->where('expenses.mdc_payment', 120)
            ->where('expenses.insurance', 80)
            ->where('expenses.licenses', 0)
            ->where('expenses.wages', 0)
            ->where('expenses.tolls', 30)
            ->where('expenses.other', 20)
            ->where('totalExpenses', 850)
            ->where('grossProfit', 3700)
            ->where('netProfit', 2850)
            ->where('profitMargin', fn ($v) => abs($v - (2850 / 3700 * 100)) < 1e-9)
            ->where('trendingData', fn ($rows) => collect($rows)->values()->all() == [
                ['period' => '2026-01', 'revenue' => 1500],
                ['period' => '2026-02', 'revenue' => 300],
                ['period' => '2026-05', 'revenue' => 200],
            ])
        );
});

test('profit and loss groups the trend by quarter and year', function () {
    reportsInvoice(['amount_paid' => 100, 'invoice_date' => '2026-01-15']);
    reportsInvoice(['amount_paid' => 50, 'invoice_date' => '2026-03-31']);
    reportsInvoice(['amount_paid' => 25, 'invoice_date' => '2026-04-01']);

    $user = userWithPermissions(['view-reports']);

    $this->actingAs($user)
        ->get(route('reports.profit-loss', ['groupBy' => 'quarter']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.groupBy', 'quarter')
            ->where('trendingData', fn ($rows) => collect($rows)->values()->all() == [
                ['period' => '2026-Q1', 'revenue' => 150],
                ['period' => '2026-Q2', 'revenue' => 25],
            ])
        );

    $this->actingAs($user)
        ->get(route('reports.profit-loss', ['groupBy' => 'year']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('trendingData', fn ($rows) => collect($rows)->values()->all() == [
                ['period' => '2026', 'revenue' => 175],
            ])
        );
});

test('profit and loss shows a negative margin when expenses exceed revenue', function () {
    reportsInvoice(['invoice_date' => '2026-02-01'], [['unit' => 'day', 'amount' => 200]]);
    reportsExpense(['category' => 'fuel', 'amount' => 500, 'expense_date' => '2026-02-02']);

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.profit-loss'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('netProfit', -300)
            ->where('profitMargin', -150)
        );
});
