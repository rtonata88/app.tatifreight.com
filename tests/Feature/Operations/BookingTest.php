<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

function bookingClient(array $attributes = []): Client
{
    return Client::create([
        'name' => 'Jane Doe',
        'company_name' => 'Acme Freight',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '0811234567',
        'is_active' => true,
        ...$attributes,
    ]);
}

/**
 * A user with the driver role. Built directly because userWithRole() runs the
 * roles seeder, which needs a console command in tests.
 */
function bookingDriver(array $permissions = ['view-bookings']): User
{
    $user = userWithPermissions($permissions);
    $user->assignRole(Spatie\Permission\Models\Role::findOrCreate('driver', 'web'));

    return $user->fresh();
}

/** @return array<string, mixed> */
function bookingPayload(Client $client, Vehicle $vehicle, array $overrides = []): array
{
    return [
        'client_id' => $client->id,
        'vehicle_id' => $vehicle->id,
        'driver_id' => '',
        'start_date' => '2026-01-10T08:00',
        'end_date' => '2026-01-12T17:00',
        'pickup_location' => 'Windhoek',
        'delivery_location' => 'Walvis Bay',
        'distance_km' => '',
        'load_weight' => '',
        'cargo_description' => 'Cement',
        'special_instructions' => '',
        'notes' => '',
        'is_recurring' => false,
        'recurring_frequency' => 'weekly',
        ...$overrides,
    ];
}

test('guests cannot see bookings', function () {
    $this->get(route('bookings.index'))->assertRedirect(route('login'));
    $this->get(route('bookings.calendar'))->assertRedirect(route('login'));
});

test('users without permission are forbidden', function () {
    $user = userWithPermissions([]);
    $booking = Booking::factory()->create();

    $this->actingAs($user)->get(route('bookings.index'))->assertForbidden();
    $this->actingAs($user)->get(route('bookings.create'))->assertForbidden();
    $this->actingAs($user)->post(route('bookings.store'))->assertForbidden();
    $this->actingAs($user)->get(route('bookings.edit', $booking))->assertForbidden();
    $this->actingAs($user)->patch(route('bookings.status', $booking), ['status' => 'confirmed'])->assertForbidden();
    $this->actingAs($user)->delete(route('bookings.destroy', $booking))->assertForbidden();
});

test('index lists bookings with stats and filters', function () {
    $user = userWithPermissions(['view-bookings', 'edit-bookings']);
    $client = bookingClient(['name' => 'Searchable Client']);
    Booking::factory()->create(['client_id' => $client->id, 'status' => 'pending', 'booking_number' => 'BKG-000001', 'cargo_description' => str_repeat('x', 40)]);
    Booking::factory()->create(['status' => 'confirmed', 'booking_number' => 'BKG-000002']);
    Booking::factory()->create(['status' => 'confirmed', 'booking_number' => 'BKG-000003']);

    $this->actingAs($user)
        ->get(route('bookings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/index')
            ->has('bookings.data', 3)
            ->where('stats.pending', 1)
            ->where('stats.confirmed', 2)
            ->where('stats.completed', 0)
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
        );

    $this->actingAs($user)
        ->get(route('bookings.index', ['search' => 'Searchable', 'status' => 'pending']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_number', 'BKG-000001')
            ->where('bookings.data.0.client.company_name', 'Acme Freight')
            ->where('bookings.data.0.cargo_excerpt', str_repeat('x', 30).'...')
            ->where('filters.search', 'Searchable')
        );

    // Search ORs stay grouped: the status filter still applies.
    $this->actingAs($user)
        ->get(route('bookings.index', ['search' => 'BKG', 'status' => 'confirmed']))
        ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 2));
});

test('index date filter', function () {
    $user = userWithPermissions(['view-bookings']);
    Booking::factory()->create(['start_date' => now()->addWeek(), 'end_date' => now()->addWeeks(2)]);
    Booking::factory()->create(['start_date' => now()->subWeeks(2), 'end_date' => now()->subWeek()]);

    $this->actingAs($user)
        ->get(route('bookings.index', ['date' => 'upcoming']))
        ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 1));
    $this->actingAs($user)
        ->get(route('bookings.index', ['date' => 'past']))
        ->assertInertia(fn (Assert $page) => $page->has('bookings.data', 1)->where('filters.date', 'past'));
});

test('drivers only see their own bookings and stats', function () {
    $driver = bookingDriver();
    Booking::factory()->create(['driver_id' => $driver->id, 'status' => 'confirmed']);
    Booking::factory()->create(['status' => 'confirmed']);

    $this->actingAs($driver)
        ->get(route('bookings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('bookings.data', 1)
            ->where('stats.confirmed', 1)
        );
});

test('create page lists active clients, available vehicles and drivers', function () {
    $user = userWithPermissions(['create-bookings']);
    bookingClient(['name' => 'Active']);
    bookingClient(['name' => 'Inactive', 'is_active' => false]);
    Vehicle::factory()->create(['status' => 'available']);
    Vehicle::factory()->create(['status' => 'maintenance']);
    $driver = bookingDriver();

    $this->actingAs($user)
        ->get(route('bookings.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/create')
            ->has('clients', 1)
            ->where('clients.0.label', 'Active (Acme Freight)')
            ->has('vehicles', 1)
            ->has('drivers', 1)
            ->where('drivers.0.value', $driver->id)
        );
});

test('a booking can be created with a generated number and pending status', function () {
    $user = userWithPermissions(['view-bookings', 'create-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->create(['booking_number' => 'BKG-000041']);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($client, $vehicle, ['distance_km' => '350', 'load_weight' => '12.5']))
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking created successfully!');

    $booking = Booking::where('booking_number', 'BKG-000042')->firstOrFail();
    expect($booking)
        ->status->toBe('pending')
        ->client_id->toBe($client->id)
        ->driver_id->toBeNull()
        ->created_by->toBe($user->id)
        ->is_recurring->toBeFalse()
        ->recurring_frequency->toBeNull()
        ->and((float) $booking->distance_km)->toBe(350.0)
        ->and($booking->start_date->format('Y-m-d H:i'))->toBe('2026-01-10 08:00');

    // Vehicle status is not touched on create.
    expect($vehicle->fresh()->status)->toBe('available');
});

test('creating a booking validates fields', function () {
    $user = userWithPermissions(['create-bookings']);

    $this->actingAs($user)
        ->post(route('bookings.store'), [
            'client_id' => 999,
            'start_date' => '2026-01-10T08:00',
            'end_date' => '2026-01-09T08:00',
            'distance_km' => '-5',
            'pickup_location' => str_repeat('a', 256),
        ])
        ->assertSessionHasErrors(['client_id', 'vehicle_id', 'end_date', 'distance_km', 'pickup_location']);
});

test('a vehicle cannot be double booked', function () {
    $user = userWithPermissions(['create-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'status' => 'confirmed',
        'start_date' => '2026-01-11 00:00:00',
        'end_date' => '2026-01-15 00:00:00',
    ]);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($client, $vehicle))
        ->assertSessionHasErrors(['vehicle_id' => 'This vehicle is not available for the selected date range. Please choose different dates or another vehicle.']);

    expect(Booking::count())->toBe(1);
});

test('pending and cancelled bookings do not block a vehicle', function () {
    $user = userWithPermissions(['create-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    foreach (['pending', 'cancelled'] as $status) {
        Booking::factory()->create(['vehicle_id' => $vehicle->id, 'status' => $status, 'start_date' => '2026-01-11 00:00:00', 'end_date' => '2026-01-15 00:00:00']);
    }

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($client, $vehicle))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('bookings.index'));
});

test('edit page shows the booking and includes its current vehicle', function () {
    $user = userWithPermissions(['edit-bookings']);
    // The form lists User::role('driver'), which needs the role to exist.
    Spatie\Permission\Models\Role::findOrCreate('driver', 'web');
    $vehicle = Vehicle::factory()->create(['status' => 'in_use']);
    Vehicle::factory()->create(['status' => 'maintenance']);
    $booking = Booking::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-01-10 08:00:00',
        'confirmed_at' => '2026-01-01 09:30:00',
    ]);

    $this->actingAs($user)
        ->get(route('bookings.edit', $booking))
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/edit')
            ->where('booking.id', $booking->id)
            ->where('booking.start_date', '2026-01-10T08:00')
            ->where('booking.confirmed_at', '01 Jan 2026, 09:30')
            ->has('vehicles', 1)
            ->where('vehicles.0.value', $vehicle->id)
        );
});

test('updating a booking to in progress marks the vehicle in use', function () {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    $booking = Booking::factory()->create(['client_id' => $client->id, 'vehicle_id' => $vehicle->id, 'status' => 'confirmed']);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, ['status' => 'in_progress', 'is_recurring' => true]))
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking updated successfully!');

    expect($booking->fresh())->status->toBe('in_progress')->recurring_frequency->toBe('weekly')
        ->and($vehicle->fresh()->status)->toBe('in_use');
});

test('completing or cancelling a booking frees the vehicle', function (string $status) {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create(['status' => 'in_use']);
    $booking = Booking::factory()->create(['client_id' => $client->id, 'vehicle_id' => $vehicle->id, 'status' => 'in_progress']);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, ['status' => $status]))
        ->assertRedirect(route('bookings.index'));

    expect($vehicle->fresh()->status)->toBe('available');
})->with(['completed', 'cancelled']);

test('changing the vehicle of an in-progress booking swaps vehicle statuses', function () {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $old = Vehicle::factory()->create(['status' => 'in_use']);
    $new = Vehicle::factory()->create();
    $booking = Booking::factory()->create(['client_id' => $client->id, 'vehicle_id' => $old->id, 'status' => 'in_progress']);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $new, ['status' => 'in_progress']))
        ->assertRedirect(route('bookings.index'));

    expect($old->fresh()->status)->toBe('available')
        ->and($new->fresh()->status)->toBe('in_use')
        ->and($booking->fresh()->vehicle_id)->toBe($new->id);
});

test('update checks availability excluding the booking itself', function () {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    $booking = Booking::factory()->create([
        'client_id' => $client->id, 'vehicle_id' => $vehicle->id, 'status' => 'confirmed',
        'start_date' => '2026-01-10 08:00:00', 'end_date' => '2026-01-12 17:00:00',
    ]);

    // Its own dates do not conflict with itself.
    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, ['status' => 'confirmed']))
        ->assertSessionHasNoErrors();

    Booking::factory()->create([
        'vehicle_id' => $vehicle->id, 'status' => 'in_progress',
        'start_date' => '2026-01-20 00:00:00', 'end_date' => '2026-01-25 00:00:00',
    ]);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, [
            'status' => 'confirmed', 'start_date' => '2026-01-19T08:00', 'end_date' => '2026-01-21T08:00',
        ]))
        ->assertSessionHasErrors('vehicle_id');
});

test('update requires a valid status', function () {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create();
    $booking = Booking::factory()->create(['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, ['status' => 'bogus']))
        ->assertSessionHasErrors('status');
});

test('status actions stamp the matching timestamp', function (string $status, string $field) {
    $user = userWithPermissions(['view-bookings', 'edit-bookings']);
    $booking = Booking::factory()->create();

    $this->actingAs($user)
        ->from(route('bookings.index'))
        ->patch(route('bookings.status', $booking), ['status' => $status])
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking status updated successfully');

    $fresh = $booking->fresh();
    expect($fresh->status)->toBe($status)->and($fresh->{$field})->not->toBeNull();
})->with([
    ['confirmed', 'confirmed_at'],
    ['in_progress', 'started_at'],
    ['completed', 'completed_at'],
    ['cancelled', 'cancelled_at'],
]);

test('a booking can be deleted', function () {
    $user = userWithPermissions(['view-bookings', 'delete-bookings']);
    $booking = Booking::factory()->create();

    $this->actingAs($user)
        ->from(route('bookings.index'))
        ->delete(route('bookings.destroy', $booking))
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking deleted successfully');

    expect(Booking::find($booking->id))->toBeNull()
        ->and(Booking::withTrashed()->find($booking->id))->not->toBeNull();
});

test('completing a booking without an MDC rate card warns', function () {
    $user = userWithPermissions(['edit-bookings']);
    $client = bookingClient();
    $vehicle = Vehicle::factory()->create(['status' => 'in_use']);
    $booking = Booking::factory()->create(['client_id' => $client->id, 'vehicle_id' => $vehicle->id, 'status' => 'in_progress']);

    $this->actingAs($user)
        ->put(route('bookings.update', $booking), bookingPayload($client, $vehicle, ['status' => 'completed', 'distance_km' => '200']))
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking updated successfully!')
        ->assertSessionHas('error', 'MDC Rate Card not configured for this vehicle. Please set up MDC rate in vehicle settings.');
});

test('a failed MDC calculation does not block booking creation', function () {
    $user = userWithPermissions(['create-bookings']);
    $client = bookingClient();
    $card = App\Models\MdcRateCard::create([
        'category_name' => 'Heavy', 'min_gvm_tonnes' => 0, 'rate_per_100km' => 50,
        'effective_from' => now()->subYear(), 'is_active' => true,
    ]);
    $vehicle = Vehicle::factory()->create(['mdc_rate_card_id' => $card->id, 'gvm_tonnes' => 30]);

    $this->actingAs($user)
        ->post(route('bookings.store'), bookingPayload($client, $vehicle, ['distance_km' => '300']))
        ->assertRedirect(route('bookings.index'))
        ->assertSessionHas('success', 'Booking created successfully!');

    expect(Booking::count())->toBe(1);
});
