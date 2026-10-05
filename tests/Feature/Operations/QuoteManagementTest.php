<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\CompanyBankAccount;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Inertia\Testing\AssertableInertia as Assert;

function quoteClient(array $attributes = []): Client
{
    return Client::create([
        'name' => 'Acme Logistics',
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
        ...$attributes,
    ]);
}

function quotePayload(Client $client, array $overrides = []): array
{
    return [
        'client_id' => $client->id,
        'valid_until' => now()->addDays(30)->toDateString(),
        'description' => 'Haul to Walvis Bay',
        'terms_conditions' => 'Terms',
        'notes' => 'Internal',
        'items' => [
            ['vehicle_id' => '', 'description' => 'Trip to Walvis', 'unit' => 'trip', 'quantity' => 2, 'unit_price' => 1500],
            ['vehicle_id' => '', 'description' => 'Standby', 'unit' => 'day', 'quantity' => 3, 'unit_price' => 250.5],
        ],
        ...$overrides,
    ];
}

test('guests are redirected to login', function () {
    $this->get(route('quotes.index'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $quote = Quote::factory()->create();

    $this->actingAs($user)->get(route('quotes.index'))->assertForbidden();
    $this->actingAs($user)->get(route('quotes.create'))->assertForbidden();
    $this->actingAs($user)->get(route('quotes.edit', $quote))->assertForbidden();
    $this->actingAs($user)->post(route('quotes.send', $quote))->assertForbidden();
    $this->actingAs($user)->delete(route('quotes.destroy', $quote))->assertForbidden();
});

test('index lists quotes with stats, filters and permissions', function () {
    $user = userWithPermissions(['view-quotes', 'edit-quotes']);
    $client = quoteClient(['name' => 'Namib Mills', 'company_name' => 'Namib Mills Ltd']);
    Quote::factory()->create(['quote_number' => 'QT-000001', 'status' => 'draft']);
    Quote::factory()->create(['quote_number' => 'QT-000002', 'status' => 'sent', 'client_id' => $client->id, 'valid_until' => now()->subDay()->toDateString()]);
    Quote::factory()->create(['quote_number' => 'QT-000003', 'status' => 'approved']);

    $this->actingAs($user)
        ->get(route('quotes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quotes/index')
            ->has('quotes.data', 3)
            ->where('stats.draft', 1)
            ->where('stats.sent', 1)
            ->where('stats.approved', 1)
            ->where('stats.rejected', 0)
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
            ->where('can.createBookings', false)
        );

    $this->actingAs($user)
        ->get(route('quotes.index', ['status' => 'sent']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quotes.data', 1)
            ->where('quotes.data.0.quote_number', 'QT-000002')
            ->where('quotes.data.0.client_company', 'Namib Mills Ltd')
            ->where('quotes.data.0.valid_until_past', true)
            ->where('filters.status', 'sent')
        );

    $this->actingAs($user)
        ->get(route('quotes.index', ['search' => 'Namib Mills']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quotes.data', 1)
            ->where('quotes.data.0.quote_number', 'QT-000002')
        );
});

test('create page provides defaults, clients, vehicles and bank accounts', function () {
    $user = userWithPermissions(['create-quotes']);
    quoteClient(['name' => 'Active Co']);
    quoteClient(['name' => 'Inactive Co', 'is_active' => false]);
    $primary = CompanyBankAccount::create(['bank_name' => 'FNB', 'account_name' => 'Taati', 'account_number' => '123', 'is_primary' => true, 'is_active' => true]);
    $type = VehicleType::factory()->create(['name' => 'Tipper', 'base_rate_daily' => 2500]);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'reg_number' => 'N 1 W', 'status' => 'maintenance']);
    Vehicle::factory()->create(['vehicle_type_id' => $type->id, 'reg_number' => 'N 2 W', 'status' => 'retired']);

    $this->actingAs($user)
        ->get(route('quotes.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quotes/create')
            ->has('clients', 1)
            ->where('clients.0.label', 'Active Co')
            ->has('vehicles', 1)
            ->where('vehicles.0.reg_number', 'N 1 W')
            ->where('vehicles.0.type_name', 'Tipper')
            ->where('vehicles.0.base_rate_daily', 2500)
            ->has('bankAccounts', 1)
            ->where('defaults.company_bank_account_id', $primary->id)
            ->where('defaults.valid_until', now()->addDays(30)->format('Y-m-d'))
            ->where('taxRate', 15)
        );
});

test('a quote is stored with server-computed totals and a generated number', function () {
    $user = userWithPermissions(['view-quotes', 'create-quotes']);
    $client = quoteClient();
    Quote::factory()->create(['quote_number' => 'QT-000041']);

    $this->actingAs($user)
        ->post(route('quotes.store'), quotePayload($client, [
            // Client-sent totals are ignored.
            'subtotal' => 1, 'tax_amount' => 1, 'total' => 1,
        ]))
        ->assertRedirect(route('quotes.index'))
        ->assertSessionHas('success', 'Quote created successfully!');

    $quote = Quote::where('quote_number', 'QT-000042')->firstOrFail();

    // 2 × 1500 + 3 × 250.50 = 3751.50; VAT 15% = 562.725 → 562.73; total 4314.225 → 4314.23
    expect((float) $quote->subtotal)->toBe(3751.5)
        ->and((float) $quote->tax_amount)->toBe(562.73)
        ->and((float) $quote->total)->toBe(4314.23)
        ->and($quote->status)->toBe('draft')
        ->and($quote->version)->toBe(1)
        ->and($quote->created_by)->toBe($user->id)
        ->and($quote->lineItems)->toHaveCount(2);

    $line = $quote->lineItems->firstWhere('unit', 'day');
    expect((float) $line->amount)->toBe(751.5)->and($line->vehicle_id)->toBeNull();
});

test('first quote number is QT-000001', function () {
    $user = userWithPermissions(['create-quotes']);

    $this->actingAs($user)->post(route('quotes.store'), quotePayload(quoteClient()));

    expect(Quote::first()->quote_number)->toBe('QT-000001');
});

test('storing validates fields and requires a line item', function () {
    $user = userWithPermissions(['create-quotes']);

    $this->actingAs($user)
        ->post(route('quotes.store'), ['items' => [['description' => '', 'unit' => 'bogus', 'quantity' => 0, 'unit_price' => 'x']]])
        ->assertSessionHasErrors(['client_id', 'valid_until', 'items.0.description', 'items.0.unit', 'items.0.quantity', 'items.0.unit_price']);

    $this->actingAs($user)
        ->from(route('quotes.create'))
        ->post(route('quotes.store'), quotePayload(quoteClient(), ['items' => []]))
        ->assertRedirect(route('quotes.create'))
        ->assertSessionHas('error', 'Please add at least one line item');

    expect(Quote::count())->toBe(0);
});

test('edit page shows the quote with its line items', function () {
    $user = userWithPermissions(['view-quotes', 'edit-quotes']);
    $quote = Quote::factory()->create(['status' => 'sent', 'sent_at' => now()]);
    QuoteLineItem::factory()->create(['quote_id' => $quote->id, 'unit' => 'km']);

    $this->actingAs($user)
        ->get(route('quotes.edit', $quote))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quotes/edit')
            ->where('quote.id', $quote->id)
            ->where('quote.status', 'sent')
            ->has('quote.line_items', 1)
            ->where('quote.line_items.0.unit', 'km')
            ->where('hasBooking', false)
            ->where('can.view', true)
        );
});

test('a quote is updated, lines added, changed and removed, totals recomputed', function () {
    $user = userWithPermissions(['edit-quotes']);
    $quote = Quote::factory()->create();
    $keep = QuoteLineItem::factory()->create(['quote_id' => $quote->id, 'quantity' => 1, 'unit_price' => 100, 'amount' => 100]);
    $remove = QuoteLineItem::factory()->create(['quote_id' => $quote->id]);
    $otherQuoteLine = QuoteLineItem::factory()->create();

    $this->actingAs($user)
        ->put(route('quotes.update', $quote), [
            'client_id' => $quote->client_id,
            'valid_until' => '2030-01-31',
            'status' => 'approved',
            'description' => 'Updated',
            'items' => [
                ['id' => $keep->id, 'vehicle_id' => '', 'description' => 'Changed', 'unit' => 'hour', 'quantity' => 4, 'unit_price' => 200],
                ['id' => null, 'vehicle_id' => '', 'description' => 'New line', 'unit' => 'tonne', 'quantity' => 10, 'unit_price' => 30],
            ],
        ])
        ->assertRedirect(route('quotes.index'))
        ->assertSessionHas('success', 'Quote updated successfully!');

    $quote->refresh();
    expect($quote->status)->toBe('approved')
        ->and($quote->valid_until->format('Y-m-d'))->toBe('2030-01-31')
        ->and((float) $quote->subtotal)->toBe(1100.0)
        ->and((float) $quote->tax_amount)->toBe(165.0)
        ->and((float) $quote->total)->toBe(1265.0)
        ->and($quote->lineItems()->count())->toBe(2);

    expect($keep->fresh()->description)->toBe('Changed')
        ->and((float) $keep->fresh()->amount)->toBe(800.0)
        ->and(QuoteLineItem::find($remove->id))->toBeNull()
        ->and(QuoteLineItem::find($otherQuoteLine->id))->not->toBeNull();
});

test('updating validates status', function () {
    $user = userWithPermissions(['edit-quotes']);
    $quote = Quote::factory()->create();

    $this->actingAs($user)
        ->put(route('quotes.update', $quote), ['status' => 'pending'])
        ->assertSessionHasErrors(['client_id', 'valid_until', 'status']);
});

test('status actions update the quote and flash', function () {
    $user = userWithPermissions(['view-quotes', 'edit-quotes']);
    $quote = Quote::factory()->create();

    $this->actingAs($user)->post(route('quotes.send', $quote))->assertSessionHas('success', 'Quote marked as sent');
    expect($quote->fresh()->status)->toBe('sent')->and($quote->fresh()->sent_at)->not->toBeNull();

    $this->actingAs($user)->post(route('quotes.approve', $quote))->assertSessionHas('success', 'Quote approved');
    expect($quote->fresh()->status)->toBe('approved')->and($quote->fresh()->approved_at)->not->toBeNull();

    $this->actingAs($user)->post(route('quotes.reject', $quote))->assertSessionHas('success', 'Quote rejected');
    expect($quote->fresh()->status)->toBe('rejected');

    $this->actingAs($user)->post(route('quotes.expire', $quote))->assertSessionHas('success', 'Quote marked as expired');
    expect($quote->fresh()->status)->toBe('expired');
});

test('a quote can be duplicated with its line items', function () {
    $user = userWithPermissions(['create-quotes']);
    $quote = Quote::factory()->create(['quote_number' => 'QT-000007', 'status' => 'approved', 'total' => 1150, 'subtotal' => 1000, 'tax_amount' => 150]);
    QuoteLineItem::factory()->count(2)->create(['quote_id' => $quote->id, 'unit' => 'load']);

    $this->actingAs($user)
        ->post(route('quotes.duplicate', $quote))
        ->assertSessionHas('success', 'Quote duplicated successfully');

    $copy = Quote::where('quote_number', 'QT-000008')->firstOrFail();
    expect($copy->status)->toBe('draft')
        ->and($copy->created_by)->toBe($user->id)
        ->and((float) $copy->total)->toBe(1150.0)
        ->and($copy->valid_until->format('Y-m-d'))->toBe(now()->addDays(30)->format('Y-m-d'))
        ->and($copy->lineItems()->where('unit', 'load')->count())->toBe(2);
});

test('converting to a booking checks permission, status, existing booking and vehicle', function () {
    $viewer = userWithPermissions(['view-quotes']);
    $booker = userWithPermissions(['view-quotes', 'create-bookings']);
    $vehicle = Vehicle::factory()->create();

    $draft = Quote::factory()->create(['status' => 'draft']);
    $noVehicle = Quote::factory()->create(['status' => 'approved']);
    QuoteLineItem::factory()->create(['quote_id' => $noVehicle->id]);

    $this->actingAs($viewer)->post(route('quotes.convert-to-booking', $draft))
        ->assertSessionHas('error', 'You do not have permission to create bookings');
    $this->actingAs($booker)->post(route('quotes.convert-to-booking', $draft))
        ->assertSessionHas('error', 'Only approved quotes can be converted to bookings');
    $this->actingAs($booker)->post(route('quotes.convert-to-booking', $noVehicle))
        ->assertSessionHas('error', 'Quote must have at least one line item with a vehicle to convert to booking');

    $quote = Quote::factory()->create(['status' => 'approved', 'description' => 'Move cement']);
    QuoteLineItem::factory()->create(['quote_id' => $quote->id]);
    QuoteLineItem::factory()->create(['quote_id' => $quote->id, 'vehicle_id' => $vehicle->id]);

    $this->actingAs($booker)->post(route('quotes.convert-to-booking', $quote))
        ->assertSessionHas('success', 'Booking created successfully from quote!');

    $booking = Booking::where('quote_id', $quote->id)->firstOrFail();
    expect($booking->booking_number)->toBe('BKG-000001')
        ->and($booking->vehicle_id)->toBe($vehicle->id)
        ->and($booking->client_id)->toBe($quote->client_id)
        ->and($booking->status)->toBe('pending')
        ->and($booking->notes)->toBe('Move cement');

    $this->actingAs($booker)->post(route('quotes.convert-to-booking', $quote))
        ->assertSessionHas('error', 'This quote has already been converted to a booking');
    expect(Booking::count())->toBe(1);
});

test('converting from the edit page redirects to the new booking', function () {
    $booker = userWithPermissions(['view-quotes', 'create-bookings']);
    $quote = Quote::factory()->create(['status' => 'approved']);
    QuoteLineItem::factory()->create(['quote_id' => $quote->id, 'vehicle_id' => Vehicle::factory()->create()->id]);

    $response = $this->actingAs($booker)->post(route('quotes.convert-to-booking', $quote), ['open_booking' => true]);

    $booking = Booking::firstOrFail();
    $response->assertRedirect(url('bookings/'.$booking->id.'/edit'));
});

test('a quote can be deleted', function () {
    $user = userWithPermissions(['view-quotes', 'delete-quotes']);
    $quote = Quote::factory()->create();

    $this->actingAs($user)
        ->from(route('quotes.index'))
        ->delete(route('quotes.destroy', $quote))
        ->assertRedirect(route('quotes.index'))
        ->assertSessionHas('success', 'Quote deleted successfully');

    $this->assertSoftDeleted($quote);
});
