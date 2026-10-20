<?php

use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\FuelAlert;
use App\Models\Logbook;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FuelChecks;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

require_once __DIR__.'/../Admin/helpers.php';

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 15)->setTime(12, 0));
    Storage::fake('public');
});

/** A fuel fill-up saved straight to the database, then checked. */
function fuelFill(Vehicle $vehicle, string $date, float $odometer, float $litres, array $attributes = []): Expense
{
    $expense = Expense::factory()->create([
        'vehicle_id' => $vehicle->id,
        'category' => 'fuel',
        'expense_date' => $date,
        'odometer' => $odometer,
        'litres' => $litres,
        'price_per_litre' => 20,
        'amount' => $litres * 20,
        'full_tank' => true,
        'station' => 'Engen Windhoek',
        'receipt_path' => 'expenses/receipts/slip.jpg',
        ...$attributes,
    ]);

    app(FuelChecks::class)->check($expense);

    return $expense->fresh();
}

/** A logbook trip so the "fuel without trips" check stays quiet. */
function fuelTrip(Vehicle $vehicle, string $date, float $start, float $end): void
{
    Logbook::factory()->create(['vehicle_id' => $vehicle->id, 'date' => $date, 'start_odometer' => $start, 'end_odometer' => $end]);
}

function alertTypes(Expense $expense): array
{
    return $expense->fuelAlerts()->pluck('type')->sort()->values()->all();
}

test('a driver records a fill-up: amount is litres × price and the description defaults to the station', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $driver = User::factory()->create();
    $driver->assignRole('driver');
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($driver)
        ->get(route('expenses.create'))
        ->assertInertia(fn (Assert $page) => $page->where('defaults.driver_id', $driver->id)->has('drivers', 1));

    $this->actingAs($driver)
        ->post(route('expenses.store'), [
            'category' => 'fuel',
            'expense_date' => '2026-03-14',
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'litres' => '210.5',
            'price_per_litre' => '21.45',
            'odometer' => '120400',
            'full_tank' => '1',
            'station' => 'Puma Okahandja',
            'receipt_upload' => UploadedFile::fake()->image('slip.jpg'),
            'odometer_photo_upload' => UploadedFile::fake()->image('dash.jpg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('expenses.index'));

    $expense = Expense::firstOrFail();
    expect((float) $expense->amount)->toBe(4515.23) // 210.5 × 21.45 = 4515.225
        ->and($expense->description)->toBe('Fuel: 210.50 L at Puma Okahandja')
        ->and($expense->driver_id)->toBe($driver->id)
        ->and($expense->full_tank)->toBeTrue();
    Storage::disk('public')->assertExists([$expense->receipt_path, $expense->odometer_photo_path]);
});

test('a fill-up needs a vehicle, litres, odometer, full-tank answer, station and receipt', function () {
    $this->actingAs(userWithPermissions(['create-expenses']))
        ->post(route('expenses.store'), ['category' => 'fuel', 'expense_date' => '2026-03-14'])
        // The price is checked once these pass (see "without a set price the driver must enter one").
        ->assertSessionHasErrors(['vehicle_id', 'litres', 'odometer', 'full_tank', 'station', 'receipt_upload'])
        ->assertSessionHasErrors(['full_tank' => 'Say whether you filled the tank until the pump clicked off.'])
        ->assertSessionDoesntHaveErrors(['amount', 'description']);
});

test('other expenses keep their typed amount and drop fuel fields', function () {
    $this->actingAs(userWithPermissions(['create-expenses']))
        ->post(route('expenses.store'), [
            'category' => 'tolls', 'amount' => 85, 'expense_date' => '2026-03-14', 'description' => 'Toll gate',
            'litres' => 50, 'station' => 'ignored',
        ])
        ->assertSessionHasNoErrors();

    $expense = Expense::firstOrFail();
    expect((float) $expense->amount)->toBe(85.0)->and($expense->litres)->toBeNull()->and($expense->station)->toBeNull();
    expect(FuelAlert::count())->toBe(0);
});

test('consumption is measured full to full, counting top-ups in between, and flags over 15% above expected', function () {
    $vehicle = Vehicle::factory()->create(['expected_consumption' => 35]);
    fuelTrip($vehicle, '2026-03-10', 10000, 10600);

    $first = fuelFill($vehicle, '2026-03-10', 10000, 300);
    expect($first->consumption_l_per_100km)->toBeNull(); // nothing to measure from yet

    fuelFill($vehicle, '2026-03-11', 10200, 50, ['full_tank' => false]); // top-up
    $full = fuelFill($vehicle, '2026-03-12', 10500, 170);

    // (50 + 170) litres over 500 km = 44 L/100 km, 26% above 35.
    expect((float) $full->consumption_l_per_100km)->toBe(44.0)
        ->and(alertTypes($full))->toBe(['high_consumption'])
        ->and($full->fuelAlerts()->first()->message)->toBe('Used 44.0 L/100 km against 35.0 expected (+26%).');

    fuelTrip($vehicle, '2026-03-14', 10600, 11000);
    $normal = fuelFill($vehicle, '2026-03-14', 11000, 180); // 36 L/100 km: within 15%
    expect(alertTypes($normal))->toBe([]);
});

test('without a set baseline, consumption is judged against the first five full-to-full fills', function () {
    $vehicle = Vehicle::factory()->create(['expected_consumption' => null]);
    fuelTrip($vehicle, '2026-03-01', 0, 20000);
    foreach (range(1, 14) as $day) {
        fuelTrip($vehicle, sprintf('2026-03-%02d', $day), 0, 20000);
    }

    fuelFill($vehicle, '2026-03-01', 10000, 300, ['status' => 'approved']);
    // Five approved measurements of 30 L/100 km each (150 L per 500 km); none is judged while learning.
    foreach (range(1, 5) as $i) {
        $fill = fuelFill($vehicle, sprintf('2026-03-%02d', $i + 1), 10000 + $i * 500, 150, ['status' => 'approved']);
        expect(alertTypes($fill))->toBe([]);
    }

    $high = fuelFill($vehicle, '2026-03-08', 13000, 200); // 40 L/100 km
    expect(alertTypes($high))->toBe(['high_consumption'])
        ->and($high->fuelAlerts()->first()->message)->toContain('against 30.0 expected (+33%)');
});

test('fills over tank capacity, too close together, or with a lower odometer are flagged', function () {
    $vehicle = Vehicle::factory()->create(['tank_capacity' => 400]);
    fuelTrip($vehicle, '2026-03-10', 0, 50000);

    fuelFill($vehicle, '2026-03-09', 20000, 100, ['full_tank' => false]);
    $big = fuelFill($vehicle, '2026-03-10', 20600, 450, ['full_tank' => false]);
    $close = fuelFill($vehicle, '2026-03-11', 20700, 60, ['full_tank' => false]);
    $backwards = fuelFill($vehicle, '2026-03-12', 20650, 60, ['full_tank' => false]);

    expect(alertTypes($big))->toBe(['over_tank_capacity'])
        ->and(alertTypes($close))->toBe(['fills_too_close'])
        ->and($close->fuelAlerts()->first()->message)->toBe('Only 100 km since the previous fill-up on 10 Mar 2026.')
        ->and(alertTypes($backwards))->toBe(['odometer_mismatch']);
});

test('a reading outside the logbook range for the day, and fuel with no trips nearby, are flagged', function () {
    $vehicle = Vehicle::factory()->create();
    fuelTrip($vehicle, '2026-03-10', 30000, 30400);

    $outside = fuelFill($vehicle, '2026-03-10', 31000, 100, ['full_tank' => false]);
    expect(alertTypes($outside))->toBe(['odometer_mismatch'])
        ->and($outside->fuelAlerts()->first()->message)->toBe('Odometer 31,000 km is outside the logbook range for that day (30,000–30,400 km).');

    $idle = fuelFill($vehicle, '2026-03-14', 31500, 100, ['full_tank' => false]); // 4 days after the only trip
    expect(alertTypes($idle))->toBe(['no_trips']);
});

test('a price well above the recent average and a missing receipt are flagged', function () {
    $vehicle = Vehicle::factory()->create();
    fuelTrip($vehicle, '2026-03-12', 0, 50000);

    foreach ([1, 2, 3] as $i) {
        fuelFill($vehicle, "2026-03-1{$i}", 40000 + $i * 1000, 100, ['full_tank' => false, 'status' => 'approved']);
    }
    $pricey = fuelFill($vehicle, '2026-03-14', 44000, 100, ['full_tank' => false, 'price_per_litre' => 23, 'receipt_path' => null]);

    expect(alertTypes($pricey))->toBe(['missing_receipt', 'price_out_of_range'])
        ->and($pricey->fuelAlerts()->where('type', 'price_out_of_range')->value('message'))
        ->toBe('N$23.0 per litre against a recent average of N$20.0 (+15%).');
});

test('thresholds come from company settings', function () {
    CompanySetting::get()->update(['fuel_min_km_between_fills' => 50, 'fuel_trip_window_days' => 0]);
    $vehicle = Vehicle::factory()->create();
    fuelTrip($vehicle, '2026-03-10', 0, 50000);

    fuelFill($vehicle, '2026-03-10', 20000, 60, ['full_tank' => false]);
    $close = fuelFill($vehicle, '2026-03-10', 20080, 60, ['full_tank' => false]); // 80 km: fine at 50
    $nextDay = fuelFill($vehicle, '2026-03-11', 20500, 60, ['full_tank' => false]); // no trip that same day

    expect(alertTypes($close))->toBe([])->and(alertTypes($nextDay))->toBe(['no_trips']);
});

test('reviewing an alert keeps it reviewed when the fill-up is saved again', function () {
    $reviewer = userWithPermissions(['approve-expenses', 'view-expenses']);
    $vehicle = Vehicle::factory()->create();
    $fill = fuelFill($vehicle, '2026-03-14', 1000, 50, ['full_tank' => false]);
    $alert = $fill->fuelAlerts()->where('type', 'no_trips')->firstOrFail();

    $this->actingAs($reviewer)
        ->post(route('fuel-alerts.review', $alert), ['review_note' => ''])
        ->assertSessionHasErrors('review_note');

    $this->actingAs($reviewer)
        ->post(route('fuel-alerts.review', $alert), ['review_note' => 'Truck was at the workshop; fuel for the test drive.'])
        ->assertSessionHas('success');

    app(FuelChecks::class)->check($fill->fresh());

    expect($fill->fuelAlerts()->where('type', 'no_trips')->count())->toBe(1)
        ->and($alert->fresh()->reviewed_by)->toBe($reviewer->id);

    $this->actingAs(userWithPermissions(['view-expenses']))
        ->post(route('fuel-alerts.review', $alert), ['review_note' => 'x'])
        ->assertForbidden();
});

test('changing a fill-up to another category or deleting it clears its open alerts', function () {
    $vehicle = Vehicle::factory()->create();
    $fill = fuelFill($vehicle, '2026-03-14', 1000, 50, ['full_tank' => false]);
    expect($fill->fuelAlerts()->count())->toBe(1);

    $fill->update(['category' => 'other']);
    app(FuelChecks::class)->check($fill);
    expect($fill->fuelAlerts()->count())->toBe(0);

    $again = fuelFill($vehicle, '2026-03-14', 1100, 50, ['full_tank' => false]);
    $this->actingAs(userWithPermissions(['delete-expenses']))->delete(route('expenses.destroy', $again));
    expect(FuelAlert::count())->toBe(0);
});

test('the dashboard lists open fuel alerts for reviewers only', function () {
    $vehicle = Vehicle::factory()->create(['reg_number' => 'N 77 W']);
    $fill = fuelFill($vehicle, '2026-03-14', 1000, 50, ['full_tank' => false, 'receipt_path' => null]);
    $fill->fuelAlerts()->where('type', 'missing_receipt')->update(['reviewed_at' => now(), 'review_note' => 'Slip found']);

    $this->actingAs(userWithPermissions(['view-reports', 'approve-expenses']))
        ->get(route('reports.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fuelAlerts.total', 1)
            ->where('fuelAlerts.rows.0.type', 'no_trips')
            ->where('fuelAlerts.rows.0.reg_number', 'N 77 W')
            ->where('fuelAlerts.rows.0.expense_id', $fill->id)
        );

    $rejected = fuelFill($vehicle, '2026-03-14', 1200, 50, ['full_tank' => false, 'status' => 'rejected']);
    expect($rejected->fuelAlerts()->count())->toBeGreaterThan(0);

    $this->actingAs(userWithPermissions(['view-reports', 'approve-expenses']))
        ->get(route('reports.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('fuelAlerts.total', 1));

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('fuelAlerts', null));
});

test('the expense page shows fuel details and alerts to reviewers', function () {
    $vehicle = Vehicle::factory()->create(['expected_consumption' => 35]);
    $fill = fuelFill($vehicle, '2026-03-14', 1000, 50, ['full_tank' => false]);

    $this->actingAs(userWithPermissions(['view-expenses', 'approve-expenses']))
        ->get(route('expenses.show', $fill))
        ->assertInertia(fn (Assert $page) => $page
            ->where('expense.fuel.litres', 50)
            ->where('expense.fuel.station', 'Engen Windhoek')
            ->where('expense.fuel.expected_consumption', 35)
            ->has('fuelAlerts', 1)
            ->where('fuelAlerts.0.label', 'Fuel without trips')
            ->where('can.reviewFuelAlerts', true)
        );
});

test('vehicles and company settings save the fuel fields', function () {
    $vehicle = Vehicle::factory()->create();

    $this->actingAs(userWithPermissions(['edit-vehicles']))
        ->put(route('vehicles.update', $vehicle), [
            'vehicle_type' => $vehicle->vehicleType->name,
            'reg_number' => $vehicle->reg_number,
            'make' => 'Scania',
            'model' => 'R500',
            'status' => 'available',
            'expected_consumption' => 34.5,
            'tank_capacity' => 600,
        ])
        ->assertSessionHasNoErrors();

    expect((float) $vehicle->fresh()->expected_consumption)->toBe(34.5)
        ->and((float) $vehicle->fresh()->tank_capacity)->toBe(600.0);

    $settings = CompanySetting::get();
    expect($settings->fuel_consumption_tolerance)->toBe(15)
        ->and($settings->fuel_min_km_between_fills)->toBe(150)
        ->and($settings->fuel_price_tolerance)->toBe(10)
        ->and($settings->fuel_trip_window_days)->toBe(2);
});

test('new fill-ups are charged at the price set in company settings, whatever is typed', function () {
    CompanySetting::get()->update(['fuel_price_per_litre' => 21.45]);
    $user = userWithPermissions(['create-expenses']);
    $vehicle = Vehicle::factory()->create();

    $this->actingAs($user)
        ->get(route('expenses.create'))
        ->assertInertia(fn (Assert $page) => $page->where('fuelPrice', 21.45));

    $this->actingAs($user)
        ->post(route('expenses.store'), [
            'category' => 'fuel', 'expense_date' => '2026-03-14', 'vehicle_id' => $vehicle->id,
            'litres' => 100, 'price_per_litre' => 35, // a padded price is ignored
            'odometer' => 5000, 'full_tank' => '1', 'station' => 'Engen',
            'receipt_upload' => UploadedFile::fake()->image('slip.jpg'),
        ])
        ->assertSessionHasNoErrors();

    $expense = Expense::firstOrFail();
    expect((float) $expense->price_per_litre)->toBe(21.45)->and((float) $expense->amount)->toBe(2145.0);
});

test('without a set price the driver must enter one', function () {
    $this->actingAs(userWithPermissions(['create-expenses']))
        ->post(route('expenses.store'), [
            'category' => 'fuel', 'expense_date' => '2026-03-14', 'vehicle_id' => Vehicle::factory()->create()->id,
            'litres' => 100, 'odometer' => 5000, 'full_tank' => '0', 'station' => 'Engen',
            'receipt_upload' => UploadedFile::fake()->image('slip.jpg'),
        ])
        ->assertSessionHasErrors(['price_per_litre' => 'Enter the price per litre, or ask a manager to set the fuel price in Company settings.']);
});

test('editing keeps a fill-up at its saved price unless a manager corrects it', function () {
    $manager = userWithPermissions(['edit-expenses']);
    $vehicle = Vehicle::factory()->create();
    $fill = fuelFill($vehicle, '2026-03-10', 5000, 100, ['price_per_litre' => 20]);
    CompanySetting::get()->update(['fuel_price_per_litre' => 23]); // the pump price changed since

    $payload = [
        'category' => 'fuel', 'expense_date' => '2026-03-10', 'vehicle_id' => $vehicle->id, 'status' => 'pending',
        'litres' => 110, 'odometer' => 5000, 'full_tank' => '1', 'station' => 'Engen',
    ];

    $this->actingAs($manager)->put(route('expenses.update', $fill), $payload)->assertSessionHasNoErrors();
    expect((float) $fill->fresh()->price_per_litre)->toBe(20.0)->and((float) $fill->fresh()->amount)->toBe(2200.0);

    $this->actingAs($manager)->put(route('expenses.update', $fill), [...$payload, 'price_per_litre' => 19.5])->assertSessionHasNoErrors();
    expect((float) $fill->fresh()->price_per_litre)->toBe(19.5);
});

test('the fuel price is saved and can be cleared in company settings', function () {
    $admin = adminTestUser('admin');

    $this->actingAs($admin)->put(route('settings.company.update'), ['company_name' => 'Tati', 'fuel_price_per_litre' => 21.45])->assertSessionHasNoErrors();
    expect((float) CompanySetting::first()->fuel_price_per_litre)->toBe(21.45);

    $this->actingAs($admin)->put(route('settings.company.update'), ['company_name' => 'Tati', 'fuel_price_per_litre' => ''])->assertSessionHasNoErrors();
    expect(CompanySetting::first()->fuel_price_per_litre)->toBeNull();
});

test('unapproved fill-ups do not teach the baseline or the usual price', function () {
    $vehicle = Vehicle::factory()->create(['expected_consumption' => null]);
    foreach (range(1, 14) as $day) {
        fuelTrip($vehicle, sprintf('2026-03-%02d', $day), 0, 50000);
    }

    // Padded early fill-ups (60 L/100 km at N$30) still waiting for approval.
    fuelFill($vehicle, '2026-03-01', 10000, 300, ['price_per_litre' => 30]);
    foreach (range(1, 5) as $i) {
        fuelFill($vehicle, sprintf('2026-03-%02d', $i + 1), 10000 + $i * 500, 300, ['price_per_litre' => 30]);
    }

    expect(app(FuelChecks::class)->vehicleBaseline($vehicle))->toBeNull();

    $later = fuelFill($vehicle, '2026-03-08', 13500, 150, ['full_tank' => false, 'price_per_litre' => 20]);
    expect(alertTypes($later))->not->toContain('price_out_of_range');
});

test('a driver is recorded as the driver of their own fill-up; managers may only name drivers', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $driver = User::factory()->create();
    $driver->assignRole('driver');
    $colleague = User::factory()->create();
    $colleague->assignRole('driver');
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $vehicle = Vehicle::factory()->create();
    $fill = fn (array $extra) => [
        'category' => 'fuel', 'expense_date' => '2026-03-14', 'vehicle_id' => $vehicle->id,
        'litres' => 100, 'price_per_litre' => 20, 'odometer' => 5000, 'full_tank' => '0', 'station' => 'Engen',
        'receipt_upload' => UploadedFile::fake()->image('slip.jpg'), ...$extra,
    ];

    // Naming a colleague does not move the fill-up onto them.
    $this->actingAs($driver)->post(route('expenses.store'), $fill(['driver_id' => $colleague->id]))->assertSessionHasNoErrors();
    expect(Expense::latest('id')->value('driver_id'))->toBe($driver->id);

    $this->actingAs($manager)->post(route('expenses.store'), $fill(['driver_id' => $colleague->id]))->assertSessionHasNoErrors();
    expect(Expense::latest('id')->value('driver_id'))->toBe($colleague->id);

    $this->actingAs($manager)->post(route('expenses.store'), $fill(['driver_id' => $manager->id]))
        ->assertSessionHasErrors(['driver_id' => 'Choose one of the drivers.']);
});
