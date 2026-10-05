<?php

use App\Models\Expense;
use App\Models\MdcCalculation;
use App\Models\MdcPayment;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see mdc charges', function () {
    $this->get(route('mdc.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);

    $this->actingAs($user)->get(route('mdc.index'))->assertForbidden();
    $this->actingAs($user)->get(route('mdc.payments'))->assertForbidden();
    $this->actingAs($user)->get(route('mdc.record-payment'))->assertForbidden();
    $this->actingAs($user)->post(route('mdc.record-payment.store'))->assertForbidden();
});

test('view-mdc alone cannot record payments', function () {
    $this->actingAs(userWithPermissions(['view-mdc']))
        ->get(route('mdc.record-payment'))
        ->assertForbidden();
});

test('mdc index lists the current month by default with stats', function () {
    $user = userWithPermissions(['view-mdc']);
    MdcCalculation::factory()->create(['mdc_amount' => 1000, 'distance_km' => 400, 'calculation_date' => now()->startOfMonth()->toDateString()]);
    MdcCalculation::factory()->partiallyPaid(200)->create(['mdc_amount' => 500, 'distance_km' => 200, 'calculation_date' => now()->toDateString()]);
    // Outside the default period, but its payment still counts in the all-time paid total.
    MdcCalculation::factory()->partiallyPaid(50)->create(['mdc_amount' => 300, 'calculation_date' => now()->subMonths(2)->toDateString()]);

    $this->actingAs($user)
        ->get(route('mdc.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('mdc/index')
            ->has('mdcCalculations.data', 2)
            ->where('filters.date_from', now()->startOfMonth()->format('Y-m-d'))
            ->where('filters.date_to', now()->endOfMonth()->format('Y-m-d'))
            ->where('stats.total_accumulated', 1500)
            ->where('stats.total_paid', 250)
            ->where('stats.total_outstanding', 1250)
            ->where('stats.total_count', 2)
            ->where('stats.average_per_calculation', 750)
            ->where('stats.total_distance', 600)
            ->where('mdcCalculations.data.0.payment_status', 'partially_paid')
            ->where('mdcCalculations.data.0.outstanding_amount', 300)
        );
});

test('mdc index filters by vehicle and search, and clearing dates drops the period', function () {
    $user = userWithPermissions(['view-mdc']);
    $truck = Vehicle::factory()->create(['reg_number' => 'N 777 W']);
    MdcCalculation::factory()->create(['vehicle_id' => $truck->id, 'calculation_date' => now()->subYear()->toDateString()]);
    MdcCalculation::factory()->create(['calculation_date' => now()->toDateString()]);

    $this->actingAs($user)
        ->get(route('mdc.index', ['vehicle' => $truck->id, 'range' => 'custom']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('mdcCalculations.data', 1)
            ->where('mdcCalculations.data.0.vehicle.reg_number', 'N 777 W')
            ->where('filters.date_from', '')
        );

    $this->actingAs($user)
        ->get(route('mdc.index', ['search' => '777', 'range' => 'custom']))
        ->assertInertia(fn (Assert $page) => $page->has('mdcCalculations.data', 1));
});

test('record payment page shows unpaid charges oldest first', function () {
    $user = userWithPermissions(['manage-mdc']);
    MdcCalculation::factory()->create(['mdc_amount' => 100, 'calculation_date' => '2025-02-01']);
    MdcCalculation::factory()->partiallyPaid(40)->create(['mdc_amount' => 100, 'calculation_date' => '2025-01-01']);
    MdcCalculation::factory()->paid()->create(['mdc_amount' => 100]);

    $this->actingAs($user)
        ->get(route('mdc.record-payment'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('mdc/record-payment')
            ->has('unpaidCalculations', 2)
            ->where('unpaidCalculations.0.date', '2025-01-01')
            ->where('unpaidCalculations.0.outstanding', 60)
            ->where('totalUnpaid', 160)
            ->where('defaultPaymentDate', now()->format('Y-m-d'))
        );
});

test('recording a payment creates an expense and allocates oldest first', function () {
    Storage::fake('local');
    $user = userWithPermissions(['view-mdc', 'manage-mdc']);
    $oldest = MdcCalculation::factory()->partiallyPaid(40)->create(['mdc_amount' => 100, 'calculation_date' => '2025-01-01']);
    $middle = MdcCalculation::factory()->create(['mdc_amount' => 200, 'calculation_date' => '2025-02-01']);
    $newest = MdcCalculation::factory()->create(['mdc_amount' => 300, 'calculation_date' => '2025-03-01']);

    $this->actingAs($user)
        ->post(route('mdc.record-payment.store'), [
            'payment_date' => '2025-04-01',
            'amount' => '310',
            'payment_method' => 'eft',
            'bank_reference' => 'TXN-1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            'notes' => 'April payment',
        ])
        ->assertRedirect(route('mdc.index'))
        ->assertSessionHas('success', 'MDC Payment recorded successfully! Reference: MDC-000001');

    $payment = MdcPayment::firstOrFail();
    expect($payment->payment_reference)->toBe('MDC-000001')
        ->and((float) $payment->amount)->toBe(310.0)
        ->and($payment->payment_method)->toBe('eft')
        ->and($payment->created_by)->toBe($user->id);
    Storage::disk('local')->assertExists($payment->receipt_path);
    expect($payment->receipt_path)->toStartWith('mdc-receipts/');

    $expense = Expense::findOrFail($payment->expense_id);
    expect($expense->category)->toBe('mdc_payment')
        ->and((float) $expense->amount)->toBe(310.0)
        ->and($expense->status)->toBe('approved')
        ->and($expense->approved_by)->toBe($user->id)
        ->and($expense->description)->toBe('MDC Payment to RFANAM - MDC-000001')
        ->and($expense->receipt_path)->toBe($payment->receipt_path);

    // 60 clears the oldest, 200 clears the middle, 50 goes to the newest.
    expect($oldest->fresh())->payment_status->toBe('paid')->and((float) $oldest->fresh()->amount_paid)->toBe(100.0);
    expect($middle->fresh())->payment_status->toBe('paid')->and((float) $middle->fresh()->amount_paid)->toBe(200.0);
    expect($newest->fresh())->payment_status->toBe('partially_paid')->and((float) $newest->fresh()->amount_paid)->toBe(50.0);

    $allocations = $payment->mdcCalculations()->orderBy('calculation_date')->get()->pluck('pivot.amount_allocated')->map(fn ($v) => (float) $v)->all();
    expect($allocations)->toBe([60.0, 200.0, 50.0]);

    // The next payment gets the next reference.
    $this->actingAs($user)
        ->post(route('mdc.record-payment.store'), ['payment_date' => '2025-04-02', 'amount' => '10', 'payment_method' => 'cash'])
        ->assertSessionHas('success', 'MDC Payment recorded successfully! Reference: MDC-000002');
});

test('recording a payment validates input', function () {
    $user = userWithPermissions(['manage-mdc']);

    $this->actingAs($user)
        ->post(route('mdc.record-payment.store'), [
            'payment_date' => '',
            'amount' => '0',
            'payment_method' => 'bitcoin',
            'receipt' => UploadedFile::fake()->create('receipt.exe', 10),
        ])
        ->assertSessionHasErrors(['payment_date', 'amount', 'payment_method', 'receipt']);

    expect(MdcPayment::count())->toBe(0);
});

test('payment history lists payments and the receipt can be downloaded', function () {
    Storage::fake('local');
    Storage::disk('local')->put('mdc-receipts/r.pdf', 'pdf');
    $user = userWithPermissions(['view-mdc']);
    $withReceipt = MdcPayment::factory()->create(['amount' => 150, 'payment_date' => '2025-05-01', 'receipt_path' => 'mdc-receipts/r.pdf']);
    MdcPayment::factory()->create(['amount' => 50, 'payment_date' => '2025-04-01']);

    $this->actingAs($user)
        ->get(route('mdc.payments'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('mdc/payments')
            ->has('payments.data', 2)
            ->where('totalPaid', 200)
            ->where('payments.data.0.id', $withReceipt->id)
            ->where('payments.data.0.receipt_url', route('mdc.payment.receipt', $withReceipt->id))
            ->where('payments.data.1.receipt_url', null)
        );

    $this->actingAs($user)->get(route('mdc.payment.receipt', $withReceipt))->assertOk()->assertDownload('r.pdf');
});

test('downloading a missing receipt is a 404', function () {
    Storage::fake('local');
    $payment = MdcPayment::factory()->create(['receipt_path' => null]);

    $this->actingAs(userWithPermissions(['view-mdc']))
        ->get(route('mdc.payment.receipt', $payment))
        ->assertNotFound();
});
