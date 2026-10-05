<?php

use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/ReportFixtures.php';

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 15)->setTime(12, 0));
});

test('guests cannot see the vat report', function () {
    $this->get(route('reports.vat'))->assertRedirect(route('login'));
});

test('users without view-reports are forbidden from the vat report and pdf', function () {
    $user = userWithPermissions([]);

    $this->actingAs($user)->get(route('reports.vat'))->assertForbidden();
    $this->actingAs($user)->get(route('reports.vat.pdf'))->assertForbidden();
});

test('vat report defaults to the current year by month with twelve rows', function () {
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.vat'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/vat')
            ->where('filters.dateFrom', '2026-01-01')
            ->where('filters.dateTo', '2026-12-31')
            ->where('filters.groupBy', 'month')
            ->has('monthlyBreakdown', 12)
            ->where('monthlyBreakdown.0.period', 'January 2026')
            ->where('monthlyBreakdown.11.period', 'December 2026')
            ->where('vatPayable', 0)
        );
});

test('vat output, input and payable match the old calculations', function () {
    reportsInvoice(['status' => 'paid', 'invoice_date' => '2026-01-10', 'subtotal' => 1000, 'tax_amount' => 150, 'total' => 1150]);
    reportsInvoice(['status' => 'partial', 'invoice_date' => '2026-02-10', 'subtotal' => 2000, 'tax_amount' => 300, 'total' => 2300]);
    reportsInvoice(['status' => 'sent', 'invoice_date' => '2026-02-11', 'subtotal' => 9000, 'tax_amount' => 1350, 'total' => 10350]); // ignored

    reportsExpense(['amount' => 1150, 'expense_date' => '2026-01-20']);
    reportsExpense(['amount' => 230, 'expense_date' => '2026-02-20']);
    reportsExpense(['amount' => 5000, 'expense_date' => '2026-02-21', 'status' => 'pending']); // ignored

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.vat', ['dateFrom' => '2026-01-01', 'dateTo' => '2026-02-28', 'groupBy' => 'month']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('vatOutput.total_sales', 3000)
            ->where('vatOutput.vat_collected', 450)
            ->where('vatOutput.total_with_vat', 3450)
            // 1380 incl. VAT -> 1200 excl. VAT, 180 VAT
            ->where('vatInput.total_with_vat', 1380)
            ->where('vatInput.total_purchases', fn ($v) => abs($v - 1200) < 1e-9)
            ->where('vatInput.vat_paid', fn ($v) => abs($v - 180) < 1e-9)
            ->where('vatPayable', fn ($v) => abs($v - 270) < 1e-9)
            ->has('monthlyBreakdown', 2)
            ->where('monthlyBreakdown.0.period', 'January 2026')
            ->where('monthlyBreakdown.0.sales', 1000)
            ->where('monthlyBreakdown.0.vat_output', 150)
            ->where('monthlyBreakdown.0.purchases', fn ($v) => abs($v - 1000) < 1e-9)
            ->where('monthlyBreakdown.0.vat_input', fn ($v) => abs($v - 150) < 1e-9)
            ->where('monthlyBreakdown.0.vat_payable', fn ($v) => abs($v) < 1e-9)
            ->where('monthlyBreakdown.1.period', 'February 2026')
            ->where('monthlyBreakdown.1.sales', 2000)
            ->where('monthlyBreakdown.1.vat_output', 300)
            ->where('monthlyBreakdown.1.purchases', fn ($v) => abs($v - 200) < 1e-9)
            ->where('monthlyBreakdown.1.vat_input', fn ($v) => abs($v - 30) < 1e-9)
            ->where('monthlyBreakdown.1.vat_payable', fn ($v) => abs($v - 270) < 1e-9)
        );
});

test('vat report shows a refund when input exceeds output', function () {
    reportsInvoice(['invoice_date' => '2026-03-01', 'subtotal' => 100, 'tax_amount' => 15, 'total' => 115]);
    reportsExpense(['amount' => 2300, 'expense_date' => '2026-03-02']);

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.vat'))
        ->assertInertia(fn (Assert $page) => $page->where('vatPayable', fn ($v) => abs($v - (15 - 300)) < 1e-9));
});

test('vat report only builds the monthly breakdown when grouping by month', function () {
    $user = userWithPermissions(['view-reports']);

    foreach (['none', 'quarter'] as $groupBy) {
        $this->actingAs($user)
            ->get(route('reports.vat', ['groupBy' => $groupBy]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.groupBy', $groupBy)
                ->has('monthlyBreakdown', 0)
            );
    }
});

test('the pdf exports accept the same dateFrom / dateTo params the pages link with', function () {
    $user = userWithPermissions(['view-reports']);
    $query = ['dateFrom' => '2026-01-01', 'dateTo' => '2026-03-31'];

    $this->actingAs($user)
        ->get(route('reports.vat.pdf', $query))
        ->assertOk()
        ->assertDownload('VAT-Report-2026-01-01-to-2026-03-31.pdf');

    $this->actingAs($user)
        ->get(route('reports.profit-loss.pdf', $query))
        ->assertOk()
        ->assertDownload('Profit-Loss-2026-01-01-to-2026-03-31.pdf');
});
