<?php

use App\Models\Logbook;
use App\Models\MdcCalculation;
use App\Models\MdcRateCard;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function logbookRateCard(float $rate = 250.00): MdcRateCard
{
    return MdcRateCard::create([
        'category_name' => 'Heavy (16t+)',
        'min_gvm_tonnes' => 16,
        'max_gvm_tonnes' => null,
        'rate_per_100km' => $rate,
        'effective_from' => now()->subYear()->toDateString(),
        'is_active' => true,
    ]);
}

function logbookDriver(): User
{
    Role::findOrCreate('driver', 'web');
    $driver = User::factory()->create(['name' => 'Johannes Driver']);
    $driver->assignRole('driver');

    return $driver;
}

test('guests are redirected and users without permission are forbidden', function () {
    $this->get(route('logbook.index'))->assertRedirect(route('login'));

    $this->actingAs(userWithPermissions([]))
        ->get(route('logbook.index'))
        ->assertForbidden();

    $user = userWithPermissions(['view-logbook']);
    $this->actingAs($user)->get(route('logbook.create'))->assertForbidden();
    $this->actingAs($user)->post(route('logbook.store'))->assertForbidden();
});

test('index lists entries for the current month by default with summary totals', function () {
    $user = userWithPermissions(['view-logbook', 'edit-logbook', 'edit-vehicles']);
    $vehicle = Vehicle::factory()->create(['reg_number' => 'N 100 W']);
    Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'date' => now()->toDateString(), 'start_odometer' => 1000, 'end_odometer' => 1250]);
    Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'date' => now()->toDateString(), 'start_odometer' => 2000, 'end_odometer' => 2100]);
    Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'date' => now()->subMonths(2)->toDateString(), 'start_odometer' => 0, 'end_odometer' => 999]);

    $this->actingAs($user)
        ->get(route('logbook.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('logbook/index')
            ->has('logbooks.data', 2)
            ->where('totalTrips', 2)
            ->where('totalDistance', 350)
            ->where('filters.dateFrom', now()->startOfMonth()->format('Y-m-d'))
            ->where('filters.dateTo', now()->endOfMonth()->format('Y-m-d'))
            ->where('can.edit', true)
            ->where('can.create', false)
            ->where('can.delete', false)
            ->has('vehicles', 1)
        );
});

test('index filters by vehicle and search, and cleared dates show all entries', function () {
    $user = userWithPermissions(['view-logbook']);
    $a = Vehicle::factory()->create(['reg_number' => 'N 111 W']);
    $b = Vehicle::factory()->create(['reg_number' => 'N 222 W']);
    Logbook::factory()->create(['vehicle_id' => $a->id, 'date' => now()->subYear()->toDateString(), 'origin_from' => 'Windhoek', 'origin_to' => 'Rundu']);
    Logbook::factory()->create(['vehicle_id' => $b->id, 'date' => now()->toDateString(), 'origin_from' => 'Oshakati', 'origin_to' => 'Rundu']);

    $this->actingAs($user)
        ->get(route('logbook.index', ['filtered' => 1, 'vehicle' => $a->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logbooks.data', 1)
            ->where('logbooks.data.0.vehicle_reg', 'N 111 W')
            ->where('filters.dateFrom', '')
            ->where('totalTrips', 1)
        );

    $this->actingAs($user)
        ->get(route('logbook.index', ['filtered' => 1, 'search' => 'Oshakati']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('logbooks.data', 1)
            ->where('logbooks.data.0.origin_from', 'Oshakati')
        );
});

test('creating an entry calculates the MDC charge and updates vehicle mileage', function () {
    $user = userWithPermissions(['view-logbook', 'create-logbook']);
    logbookRateCard(250.00);
    $vehicle = Vehicle::factory()->create(['gvm_tonnes' => 30, 'current_mileage' => 1000]);
    $driver = logbookDriver();

    $this->actingAs($user)
        ->get(route('logbook.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('logbook/create')
            ->where('defaults.date', now()->format('Y-m-d'))
            ->has('drivers', 1)
            ->where('drivers.0.label', 'Johannes Driver')
        );

    $this->actingAs($user)
        ->post(route('logbook.store'), [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'booking_id' => '',
            'date' => '2026-03-10',
            'start_odometer' => 1000,
            'end_odometer' => 1420,
            'origin_from' => 'Windhoek',
            'origin_to' => 'Walvis Bay',
        ])
        ->assertRedirect(route('logbook.index'))
        ->assertSessionHas('success', 'Logbook entry created successfully! MDC charge calculated: N$1,050.00');

    $logbook = Logbook::firstOrFail();
    expect($logbook->created_by)->toBe($user->id)
        ->and($logbook->booking_id)->toBeNull();

    // 420 km / 100 × N$250 = N$1,050.00
    $mdc = MdcCalculation::where('logbook_id', $logbook->id)->firstOrFail();
    expect((float) $mdc->mdc_amount)->toBe(1050.0)
        ->and((float) $mdc->distance_km)->toBe(420.0)
        ->and($mdc->payment_status)->toBe('unpaid');

    expect((float) $vehicle->fresh()->current_mileage)->toBe(1420.0);
});

test('creating an entry without GVM reports that no MDC charge was calculated', function () {
    $user = userWithPermissions(['create-logbook']);
    $vehicle = Vehicle::factory()->create(['gvm_tonnes' => null]);
    $driver = logbookDriver();

    $this->actingAs($user)
        ->post(route('logbook.store'), [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'date' => '2026-03-10',
            'start_odometer' => 100,
            'end_odometer' => 200,
            'origin_from' => 'Windhoek',
            'origin_to' => 'Gobabis',
        ])
        ->assertSessionHas('success', 'Logbook entry created successfully! Note: MDC charge could not be calculated (check vehicle GVM and rate cards).');

    expect(MdcCalculation::count())->toBe(0);
});

test('creating an entry validates its fields', function () {
    $user = userWithPermissions(['create-logbook']);

    $this->actingAs($user)
        ->post(route('logbook.store'), ['start_odometer' => 500, 'end_odometer' => 100, 'booking_id' => 999])
        ->assertSessionHasErrors(['vehicle_id', 'driver_id', 'date', 'end_odometer', 'origin_from', 'origin_to', 'booking_id']);

    expect(Logbook::count())->toBe(0);
});

test('updating an entry recalculates the MDC charge', function () {
    $user = userWithPermissions(['view-logbook', 'edit-logbook']);
    logbookRateCard(200.00);
    $vehicle = Vehicle::factory()->create(['gvm_tonnes' => 20, 'current_mileage' => 0]);
    $driver = logbookDriver();
    $logbook = Logbook::factory()->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'date' => '2026-03-01',
        'start_odometer' => 1000,
        'end_odometer' => 1100,
    ]);
    expect((float) $logbook->fresh()->mdcCalculation->mdc_amount)->toBe(200.0);

    $this->actingAs($user)
        ->get(route('logbook.edit', $logbook))
        ->assertInertia(fn (Assert $page) => $page
            ->component('logbook/edit')
            ->where('logbook.id', $logbook->id)
            ->where('logbook.date', '2026-03-01')
        );

    $this->actingAs($user)
        ->put(route('logbook.update', $logbook), [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'date' => '2026-03-01',
            'start_odometer' => 1000,
            'end_odometer' => 1350,
            'origin_from' => 'Windhoek',
            'origin_to' => 'Mariental',
            'purpose' => 'Delivery',
        ])
        ->assertRedirect(route('logbook.index'))
        ->assertSessionHas('success', 'Logbook entry updated successfully! MDC charge updated: N$700.00');

    expect($logbook->fresh())->origin_to->toBe('Mariental')->purpose->toBe('Delivery');
    expect(MdcCalculation::count())->toBe(1);
    expect((float) $logbook->fresh()->mdcCalculation->mdc_amount)->toBe(700.0);
});

test('deleting an entry needs delete-logbook and, as before, delete-vehicles', function () {
    $logbook = Logbook::factory()->create();

    $this->actingAs(userWithPermissions(['view-logbook']))
        ->delete(route('logbook.destroy', $logbook))
        ->assertForbidden();

    // Route allows it but the old action silently did nothing without delete-vehicles.
    $this->actingAs(userWithPermissions(['delete-logbook']))
        ->delete(route('logbook.destroy', $logbook))
        ->assertRedirect();
    expect(Logbook::find($logbook->id))->not->toBeNull();

    $this->actingAs(userWithPermissions(['delete-logbook', 'delete-vehicles']))
        ->delete(route('logbook.destroy', $logbook))
        ->assertSessionHas('success', 'Logbook entry deleted successfully');
    expect(Logbook::find($logbook->id))->toBeNull();
});
