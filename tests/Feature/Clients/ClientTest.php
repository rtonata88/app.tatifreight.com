<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see clients', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);

    $this->actingAs($user)->get(route('clients.index'))->assertForbidden();
    $this->actingAs($user)->get(route('clients.create'))->assertForbidden();
    $this->actingAs($user)->post(route('clients.store'), [])->assertForbidden();
});

test('clients index lists and filters clients', function () {
    $user = userWithPermissions(['view-clients', 'delete-clients', 'view-documents']);
    Client::factory()->create(['name' => 'Alpha Haulage', 'classification' => 'adhoc', 'is_active' => true]);
    Client::factory()->create(['name' => 'Beta Mining', 'classification' => 'contract', 'is_active' => false, 'credit_limit' => 1500.5]);
    Client::factory()->create(['name' => 'Gamma Contract', 'classification' => 'contract', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('clients.index', ['classification' => 'contract', 'status' => '0']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Beta Mining')
            ->where('clients.data.0.credit_limit', 1500.5)
            ->where('clients.data.0.is_active', false)
            ->where('filters.classification', 'contract')
            ->where('filters.status', '0')
            ->where('can.delete', true)
            ->where('can.viewDocuments', true)
            ->where('can.create', false)
            ->where('can.edit', false)
        );

    // Search combines with filters (OR clauses are grouped).
    $this->actingAs($user)
        ->get(route('clients.index', ['search' => 'a', 'classification' => 'adhoc']))
        ->assertInertia(fn (Assert $page) => $page->has('clients.data', 1)->where('clients.data.0.name', 'Alpha Haulage'));
});

test('a client can be created', function () {
    $user = userWithPermissions(['view-clients', 'create-clients']);

    $this->actingAs($user)->get(route('clients.create'))->assertInertia(fn (Assert $page) => $page->component('clients/create'));

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'name' => 'John Doe',
            'company_name' => 'ABC Construction',
            'email' => 'john@example.com',
            'region' => 'Khomas',
            'classification' => 'contract',
            'credit_limit' => '5000',
            'payment_terms_days' => '60',
            'is_active' => '1',
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Client created successfully!');

    $client = Client::where('email', 'john@example.com')->firstOrFail();
    expect($client)
        ->province->toBe('Khomas')
        ->classification->toBe('contract')
        ->payment_terms_days->toBe(60)
        ->is_active->toBeTrue()
        ->and((float) $client->credit_limit)->toBe(5000.0);
});

test('creating a client validates input and unique email', function () {
    $user = userWithPermissions(['create-clients']);
    $existing = Client::factory()->create();

    $this->actingAs($user)
        ->post(route('clients.store'), [
            'email' => $existing->email,
            'classification' => 'vip',
            'credit_limit' => '-1',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors(['name', 'email', 'classification', 'credit_limit', 'payment_terms_days']);
});

test('a client can be updated and deleted', function () {
    $user = userWithPermissions(['view-clients', 'edit-clients', 'delete-clients']);
    $client = Client::factory()->create(['province' => 'Erongo']);
    $other = Client::factory()->create();

    $this->actingAs($user)
        ->get(route('clients.edit', $client))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/edit')
            ->where('client.id', $client->id)
            ->where('client.region', 'Erongo')
        );

    // Edit has no unique rule on email (parity with the old screen).
    $this->actingAs($user)
        ->put(route('clients.update', $client), [
            'name' => 'Renamed',
            'email' => $client->email,
            'region' => 'Zambezi',
            'classification' => 'adhoc',
            'payment_terms_days' => 15,
            'is_active' => '0',
        ])
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Client updated successfully!');

    expect($client->fresh())
        ->name->toBe('Renamed')
        ->province->toBe('Zambezi')
        ->payment_terms_days->toBe(15)
        ->is_active->toBeFalse();

    $this->actingAs($user)
        ->delete(route('clients.destroy', $client))
        ->assertSessionHas('success', 'Client deleted successfully');

    expect(Client::find($client->id))->toBeNull()
        ->and(Client::withTrashed()->find($client->id))->not->toBeNull()
        ->and(Client::find($other->id))->not->toBeNull();
});

test('deleting a client requires permission', function () {
    $client = Client::factory()->create();

    $this->actingAs(userWithPermissions(['view-clients']))
        ->delete(route('clients.destroy', $client))
        ->assertForbidden();

    expect(Client::find($client->id))->not->toBeNull();
});

test('the statement combines invoices and payments with a running balance', function () {
    $user = userWithPermissions(['view-clients']);
    $creator = User::factory()->create();
    $client = Client::factory()->create(['credit_limit' => 2000]);

    $invoiceA = Invoice::create([
        'invoice_number' => 'INV-001', 'client_id' => $client->id, 'created_by' => $creator->id,
        'invoice_date' => '2026-02-01', 'due_date' => '2026-03-01', 'description' => 'Haulage', 'total' => 1000,
    ]);
    $invoiceB = Invoice::create([
        'invoice_number' => 'INV-002', 'client_id' => $client->id, 'created_by' => $creator->id,
        'invoice_date' => '2026-03-10', 'due_date' => '2026-04-10', 'description' => 'Transport', 'total' => 500.25,
    ]);
    Payment::create([
        'payment_reference' => 'PAY-001', 'invoice_id' => $invoiceA->id, 'client_id' => $client->id,
        'amount' => 400, 'payment_date' => '2026-02-15', 'payment_method' => 'bank_transfer',
    ]);
    // Outside the period: excluded from the invoices list and totals.
    Invoice::create([
        'invoice_number' => 'INV-OLD', 'client_id' => $client->id, 'created_by' => $creator->id,
        'invoice_date' => '2025-06-01', 'due_date' => '2025-07-01', 'total' => 999,
    ]);

    $this->actingAs($user)
        ->get(route('clients.statement', ['client' => $client, 'dateFrom' => '2026-01-01', 'dateTo' => '2026-12-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('clients/statement')
            ->where('filters.dateFrom', '2026-01-01')
            ->where('filters.dateTo', '2026-12-31')
            ->has('transactions', 3)
            ->where('transactions.0.reference', 'INV-001')
            ->where('transactions.0.description', 'Invoice - Haulage')
            ->where('transactions.0.balance', 1000)
            ->where('transactions.1.reference', 'PAY-001')
            ->where('transactions.1.description', 'Payment - Bank transfer')
            ->where('transactions.1.credit', 400)
            ->where('transactions.1.balance', 600)
            ->where('transactions.2.reference', 'INV-002')
            ->where('transactions.2.date', '2026-03-10')
            ->where('transactions.2.balance', 1100.25)
            ->where('totalInvoiced', 1500.25)
            ->where('totalPaid', 400)
            ->where('totalOutstanding', 1100.25)
            ->where('client.credit_limit', 2000)
        );
});

test('the statement defaults to the current year to date', function () {
    $client = Client::factory()->create();

    $this->actingAs(userWithPermissions(['view-clients']))
        ->get(route('clients.statement', $client))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.dateFrom', now()->startOfYear()->format('Y-m-d'))
            ->where('filters.dateTo', now()->format('Y-m-d'))
            ->has('transactions', 0)
            ->where('totalOutstanding', 0)
        );
});

test('the statement pdf route is registered with the date query params', function () {
    $client = Client::factory()->create();

    expect(route('clients.statement.pdf', ['client' => $client, 'dateFrom' => '2026-01-01', 'dateTo' => '2026-02-01']))
        ->toEndWith("/clients/{$client->id}/statement/pdf?dateFrom=2026-01-01&dateTo=2026-02-01");

    $this->actingAs(userWithPermissions([]))
        ->get(route('clients.statement.pdf', $client))
        ->assertForbidden();
});
