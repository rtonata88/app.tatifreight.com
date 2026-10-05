<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\CompanyBankAccount;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Inertia\Testing\AssertableInertia as Assert;

function invoiceClient(array $attributes = []): Client
{
    return Client::create([
        'name' => 'Acme Logistics',
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
        ...$attributes,
    ]);
}

test('guests cannot see invoices', function () {
    $this->get(route('invoices.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)->get(route('invoices.index'))->assertForbidden();
    $this->actingAs($user)->get(route('invoices.create'))->assertForbidden();
    $this->actingAs($user)->post(route('invoices.send', $invoice))->assertForbidden();
    $this->actingAs($user)->post(route('invoices.payments.store', $invoice))->assertForbidden();
    $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertForbidden();
});

test('invoices index lists, filters and summarises invoices', function () {
    $user = userWithPermissions(['view-invoices', 'edit-invoices']);
    $client = invoiceClient(['name' => 'Namib Freight', 'company_name' => 'Namib Freight CC']);
    Invoice::factory()->create(['client_id' => $client->id, 'invoice_number' => 'INV-000001', 'status' => 'unpaid', 'amount_due' => 1000]);
    Invoice::factory()->create(['invoice_number' => 'INV-000002', 'status' => 'overdue', 'amount_due' => 250.5, 'due_date' => now()->subDays(3)->toDateString()]);
    Invoice::factory()->create(['invoice_number' => 'INV-000003', 'status' => 'paid', 'amount_due' => 0]);

    $this->actingAs($user)
        ->get(route('invoices.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/index')
            ->has('invoices.data', 3)
            ->where('stats.unpaid', 1)
            ->where('stats.overdue', 1)
            ->where('stats.paid', 1)
            ->where('stats.total_unpaid', 1000)
            ->where('stats.total_overdue', 250.5)
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('invoices.index', ['status' => 'overdue']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-000002')
            ->where('invoices.data.0.is_overdue', true)
            ->where('filters.status', 'overdue')
        );

    $this->actingAs($user)
        ->get(route('invoices.index', ['search' => 'Freight CC']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.client_name', 'Namib Freight')
            ->where('invoices.data.0.client_company', 'Namib Freight CC')
        );
});

test('create page offers completed bookings with rental and MDC lines pre-filled', function () {
    $user = userWithPermissions(['create-invoices']);
    $client = invoiceClient();
    $type = VehicleType::factory()->create(['name' => 'Tipper', 'base_rate_daily' => 1500, 'base_rate_per_km' => 2]);
    $vehicle = Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'reg_number' => 'N 100 W', 'tare_weight' => 10]);
    $bank = CompanyBankAccount::create(['bank_name' => 'FNB', 'account_name' => 'Taati', 'account_number' => '123', 'is_primary' => true, 'is_active' => true]);

    $booking = Booking::create([
        'booking_number' => 'BK-0001',
        'client_id' => $client->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'completed',
        'start_date' => '2025-11-01 08:00:00',
        'end_date' => '2025-11-04 08:00:00',
        'distance_km' => 200,
        'load_weight' => 20,
    ]);
    // Already-invoiced and not-completed bookings are not offered.
    $invoiced = Booking::create([...$booking->only(['client_id', 'vehicle_id', 'start_date', 'end_date']), 'booking_number' => 'BK-0002', 'status' => 'completed']);
    Invoice::factory()->create(['booking_id' => $invoiced->id]);
    Booking::create([...$booking->only(['client_id', 'vehicle_id', 'start_date', 'end_date']), 'booking_number' => 'BK-0003', 'status' => 'pending']);

    $this->actingAs($user)
        ->get(route('invoices.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/create')
            ->where('defaults.company_bank_account_id', $bank->id)
            ->where('defaults.due_date', now()->addDays(30)->format('Y-m-d'))
            ->where('taxRate', 15)
            ->has('bookings', 1)
            ->where('bookings.0.id', $booking->id)
            ->where('bookings.0.client_id', $client->id)
            ->where('bookings.0.description', 'Invoice for booking BK-0001')
            ->where('bookings.0.items.0.description', 'Vehicle Rental - Tipper (N 100 W)')
            ->where('bookings.0.items.0.quantity', 3)
            ->where('bookings.0.items.0.unit_price', '1500.00')
            ->where('bookings.0.items.0.unit', 'day')
            // (10t tare + 20t load) × 200km × N$2 / 100 = N$120
            ->where('bookings.0.items.1.unit_price', '120.00')
            ->where('bookings.0.items.1.unit', 'trip')
        );
});

test('an invoice is created with server-side totals and the next invoice number', function () {
    $user = userWithPermissions(['view-invoices', 'create-invoices']);
    $client = invoiceClient();
    Invoice::factory()->create(['invoice_number' => 'INV-000041']);

    $this->actingAs($user)
        ->post(route('invoices.store'), [
            'client_id' => $client->id,
            'invoice_date' => '2025-11-05',
            'due_date' => '2025-12-05',
            'description' => 'November work',
            'items' => [
                ['vehicle_id' => '', 'description' => 'Transport', 'unit' => 'trip', 'quantity' => 2, 'unit_price' => 1000],
                ['vehicle_id' => '', 'description' => 'Loading', 'unit' => 'hour', 'quantity' => 3, 'unit_price' => 150.5],
            ],
            // Client totals are ignored.
            'subtotal' => 1,
            'total' => 1,
        ])
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('success', 'Invoice created successfully!');

    $invoice = Invoice::where('invoice_number', 'INV-000042')->firstOrFail();
    expect($invoice)
        ->status->toBe('draft')
        ->created_by->toBe($user->id)
        ->and((float) $invoice->subtotal)->toBe(2451.5)
        ->and((float) $invoice->tax_amount)->toBe(367.73)
        ->and((float) $invoice->total)->toBe(2819.23)
        ->and((float) $invoice->amount_due)->toBe(2819.23)
        ->and((float) $invoice->amount_paid)->toBe(0.0)
        ->and($invoice->lineItems)->toHaveCount(2)
        ->and((float) $invoice->lineItems->firstWhere('description', 'Loading')->amount)->toBe(451.5);
});

test('the first invoice is numbered INV-000001', function () {
    $user = userWithPermissions(['create-invoices']);

    $this->actingAs($user)->post(route('invoices.store'), [
        'client_id' => invoiceClient()->id,
        'invoice_date' => '2025-11-05',
        'due_date' => '2025-11-05',
        'items' => [['description' => 'Trip', 'unit' => 'trip', 'quantity' => 1, 'unit_price' => 100]],
    ])->assertRedirect(route('invoices.index'));

    expect(Invoice::first()->invoice_number)->toBe('INV-000001');
});

test('creating an invoice validates fields and requires a line item', function () {
    $user = userWithPermissions(['create-invoices']);

    $this->actingAs($user)
        ->post(route('invoices.store'), ['invoice_date' => '2025-11-05', 'due_date' => '2025-11-01'])
        ->assertSessionHasErrors(['client_id', 'due_date']);

    $this->actingAs($user)
        ->from(route('invoices.create'))
        ->post(route('invoices.store'), [
            'client_id' => invoiceClient()->id,
            'invoice_date' => '2025-11-05',
            'due_date' => '2025-11-30',
            'items' => [],
        ])
        ->assertRedirect(route('invoices.create'))
        ->assertSessionHas('error', 'Please add at least one line item');

    expect(Invoice::count())->toBe(0);
});

test('edit page shows the invoice, its lines and payment history', function () {
    $user = userWithPermissions(['edit-invoices']);
    $invoice = Invoice::factory()->create(['status' => 'partial']);
    InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Haul']);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_reference' => 'PAY-000001', 'payment_method' => 'bank_transfer']);

    $this->actingAs($user)
        ->get(route('invoices.edit', $invoice))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/edit')
            ->where('invoice.id', $invoice->id)
            ->where('invoice.status', 'partial')
            ->has('invoice.line_items', 1)
            ->where('invoice.line_items.0.description', 'Haul')
            ->has('payments', 1)
            ->where('payments.0.payment_reference', 'PAY-000001')
        );
});

test('updating an invoice recalculates totals, amount due and line items', function () {
    $user = userWithPermissions(['edit-invoices']);
    $invoice = Invoice::factory()->create(['status' => 'draft']);
    $keep = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Old', 'quantity' => 1, 'unit_price' => 50]);
    $remove = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 500]);

    $this->actingAs($user)
        ->put(route('invoices.update', $invoice), [
            'client_id' => $invoice->client_id,
            'invoice_date' => '2025-11-05',
            'due_date' => '2025-11-01', // the edit screen had no after_or_equal rule
            'status' => 'sent',
            'items' => [
                ['id' => $keep->id, 'vehicle_id' => '', 'description' => 'Updated', 'unit' => 'day', 'quantity' => 2, 'unit_price' => 1000],
                ['id' => null, 'vehicle_id' => '', 'description' => 'New', 'unit' => 'trip', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ])
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('success', 'Invoice updated successfully!');

    $invoice->refresh();
    expect($invoice->status)->toBe('sent')
        ->and((float) $invoice->subtotal)->toBe(3000.0)
        ->and((float) $invoice->tax_amount)->toBe(450.0)
        ->and((float) $invoice->total)->toBe(3450.0)
        ->and((float) $invoice->amount_paid)->toBe(500.0)
        ->and((float) $invoice->amount_due)->toBe(2950.0)
        ->and($invoice->lineItems()->count())->toBe(2)
        ->and(InvoiceLineItem::find($remove->id))->toBeNull()
        ->and($keep->fresh()->description)->toBe('Updated')
        ->and($keep->fresh()->unit)->toBe('day');
});

test('updating validates the status', function () {
    $user = userWithPermissions(['edit-invoices']);
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->put(route('invoices.update', $invoice), [
            'client_id' => $invoice->client_id,
            'invoice_date' => '2025-11-05',
            'due_date' => '2025-11-30',
            'status' => 'cancelled',
            'items' => [['description' => 'X', 'unit' => 'trip', 'quantity' => 1, 'unit_price' => 1]],
        ])
        ->assertSessionHasErrors('status');
});

test('an invoice can be marked as sent and as paid', function () {
    $user = userWithPermissions(['view-invoices', 'edit-invoices']);
    $invoice = Invoice::factory()->create(['status' => 'draft', 'total' => 1150, 'amount_due' => 1150]);

    $this->actingAs($user)->post(route('invoices.send', $invoice))->assertSessionHas('success', 'Invoice marked as sent');
    expect($invoice->fresh())->status->toBe('sent')->sent_at->not->toBeNull();

    $this->actingAs($user)->post(route('invoices.mark-paid', $invoice))->assertSessionHas('success', 'Invoice marked as paid');
    $invoice->refresh();
    expect($invoice->status)->toBe('paid')
        ->and((float) $invoice->amount_paid)->toBe(1150.0)
        ->and((float) $invoice->amount_due)->toBe(0.0)
        ->and($invoice->paid_at)->not->toBeNull();
});

test('an invoice can be deleted', function () {
    $user = userWithPermissions(['view-invoices', 'delete-invoices']);
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)->delete(route('invoices.destroy', $invoice))->assertSessionHas('success', 'Invoice deleted successfully');
    expect(Invoice::find($invoice->id))->toBeNull()
        ->and(Invoice::withTrashed()->find($invoice->id))->not->toBeNull();
});

test('invoice numbers keep counting past soft-deleted invoices', function () {
    $user = userWithPermissions(['create-invoices']);
    Invoice::factory()->create(['invoice_number' => 'INV-000007'])->delete();

    $this->actingAs($user)->post(route('invoices.store'), [
        'client_id' => invoiceClient()->id,
        'invoice_date' => '2025-11-05',
        'due_date' => '2025-11-30',
        'items' => [['description' => 'Trip', 'unit' => 'trip', 'quantity' => 1, 'unit_price' => 100]],
    ])->assertRedirect(route('invoices.index'));

    expect(Invoice::first()->invoice_number)->toBe('INV-000008');
});

test('recording a payment creates a payment and updates the invoice like the old modal', function () {
    $user = userWithPermissions(['edit-invoices']);
    $invoice = Invoice::factory()->create(['status' => 'sent', 'total' => 1000, 'amount_paid' => 0, 'amount_due' => 1000]);

    $this->actingAs($user)
        ->from(route('invoices.edit', $invoice))
        ->post(route('invoices.payments.store', $invoice), [
            'paymentAmount' => 300,
            'paymentDate' => '2025-11-06',
            'paymentMethod' => 'eft',
            'transactionReference' => 'REF-1',
            'paymentNotes' => 'First part',
        ])
        ->assertRedirect(route('invoices.edit', $invoice))
        ->assertSessionHas('success', 'Payment recorded successfully!');

    $payment = Payment::firstOrFail();
    expect($payment)
        ->payment_reference->toBe('PAY-000001')
        ->client_id->toBe($invoice->client_id)
        ->payment_method->toBe('eft')
        ->transaction_reference->toBe('REF-1')
        ->and((float) $payment->amount)->toBe(300.0);

    // Old recordPayment() added the new amount on top of a sum that already included it.
    $invoice->refresh();
    expect($invoice->status)->toBe('partial')
        ->and((float) $invoice->amount_paid)->toBe(600.0)
        ->and((float) $invoice->amount_due)->toBe(400.0)
        ->and($invoice->paid_at)->toBeNull();
});

test('a payment cannot exceed the amount due', function () {
    $user = userWithPermissions(['edit-invoices']);
    $invoice = Invoice::factory()->create(['total' => 1000, 'amount_due' => 200]);

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), [
            'paymentAmount' => 250,
            'paymentDate' => '2025-11-06',
            'paymentMethod' => 'cash',
        ])
        ->assertSessionHasErrors('paymentAmount');

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), ['paymentAmount' => 0])
        ->assertSessionHasErrors(['paymentAmount', 'paymentDate', 'paymentMethod']);

    expect(Payment::count())->toBe(0);
});
