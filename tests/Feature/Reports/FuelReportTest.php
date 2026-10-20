<?php

use App\Models\Expense;
use App\Models\FuelAlert;
use App\Models\Logbook;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 3, 15)->setTime(12, 0));
});

function reportFill(Vehicle $vehicle, User $driver, string $date, float $litres, array $attributes = []): Expense
{
    return Expense::factory()->create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => $driver->id,
        'category' => 'fuel',
        'expense_date' => $date,
        'litres' => $litres,
        'price_per_litre' => 20,
        'amount' => $litres * 20,
        'status' => 'approved',
        ...$attributes,
    ]);
}

function reportTrip(Vehicle $vehicle, User $driver, string $date, float $km): void
{
    Logbook::factory()->create([
        'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'date' => $date, 'start_odometer' => 1000, 'end_odometer' => 1000 + $km,
    ]);
}

test('guests and users without view-reports cannot open the fuel report', function () {
    $this->get(route('reports.fuel'))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions([]))->get(route('reports.fuel'))->assertForbidden();
});

test('the fuel report compares each vehicle and driver with the expected rate', function () {
    $thirsty = Vehicle::factory()->create(['reg_number' => 'N 1 W', 'expected_consumption' => 35]);
    $unknown = Vehicle::factory()->create(['reg_number' => 'N 2 W', 'expected_consumption' => null]);
    $ana = User::factory()->create(['name' => 'Ana']);
    $ben = User::factory()->create(['name' => 'Ben']);

    reportTrip($thirsty, $ana, '2026-02-10', 300);
    reportTrip($thirsty, $ana, '2026-03-05', 200);
    $flagged = reportFill($thirsty, $ana, '2026-02-11', 120);
    reportFill($thirsty, $ana, '2026-03-06', 100, ['status' => 'pending']); // pending still counts
    reportFill($thirsty, $ana, '2026-03-07', 999, ['status' => 'rejected']); // rejected does not

    reportTrip($unknown, $ben, '2026-03-01', 400);
    reportFill($unknown, $ben, '2026-03-02', 100);

    FuelAlert::create(['expense_id' => $flagged->id, 'vehicle_id' => $thirsty->id, 'driver_id' => $ana->id, 'type' => 'high_consumption', 'message' => 'x']);
    FuelAlert::create(['expense_id' => $flagged->id, 'vehicle_id' => $thirsty->id, 'driver_id' => $ana->id, 'type' => 'no_trips', 'message' => 'x', 'reviewed_at' => now()]);

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.fuel'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/fuel')
            ->where('filters.dateFrom', '2026-01-01') // last three months by default
            ->where('filters.dateTo', '2026-03-31')
            ->where('tolerance', 15)
            ->where('totals.litres', 320)
            ->where('totals.distance', 900)
            ->where('totals.consumption', 35.6) // 320 L over 900 km
            ->where('totals.open_alerts', 1)
            // 220 L over 500 km = 44 L/100 km, 25.7% over 35; first because it is furthest over.
            ->where('vehicles.0.name', 'N 1 W')
            ->where('vehicles.0.consumption', 44)
            ->where('vehicles.0.expected', 35)
            ->where('vehicles.0.over_percent', 25.7)
            ->where('vehicles.0.cost_per_km', 8.8)
            ->where('vehicles.0.alerts', 2)
            ->where('vehicles.0.open_alerts', 1)
            // No expected rate yet (fewer than five full tanks): shown, not compared, so it goes last.
            ->where('vehicles.1.name', 'N 2 W')
            ->where('vehicles.1.consumption', 25)
            ->where('vehicles.1.over_percent', null)
            ->where('drivers.0.name', 'Ana')
            ->where('drivers.0.detail', 'N 1 W')
            ->where('drivers.0.expected', 35)
            ->where('drivers.0.over_percent', 25.7)
            ->where('drivers.1.name', 'Ben')
            ->where('trend.months', ['2026-01', '2026-02', '2026-03'])
            ->where('trend.vehicles.0.reg_number', 'N 1 W')
            ->where('trend.vehicles.0.points', [null, 40, 50]) // Feb 120 L / 300 km; Mar 100 L / 200 km
        );
});

test('a driver who drove several vehicles is judged against their distance-weighted expected rate', function () {
    $a = Vehicle::factory()->create(['expected_consumption' => 30]);
    $b = Vehicle::factory()->create(['expected_consumption' => 40]);
    $driver = User::factory()->create();

    reportTrip($a, $driver, '2026-03-01', 300);
    reportTrip($b, $driver, '2026-03-02', 100);
    reportFill($a, $driver, '2026-03-03', 130);

    // (30 × 300 + 40 × 100) / 400 km = 32.5
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.fuel', ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('drivers.0.expected', 32.5)
            ->where('drivers.0.consumption', 32.5)
            ->where('drivers.0.over_percent', 0)
        );
});

test('the fuel report exports to PDF and rejects a backwards range', function () {
    $vehicle = Vehicle::factory()->create(['reg_number' => 'N 9 W']);
    reportFill($vehicle, User::factory()->create(), '2026-03-02', 100);

    $response = $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.fuel.pdf', ['dateFrom' => '2026-03-01', 'dateTo' => '2026-03-31']))
        ->assertOk();
    expect($response->headers->get('content-type'))->toContain('pdf')
        ->and($response->headers->get('content-disposition'))->toContain('Fuel-Report-2026-03-01-to-2026-03-31.pdf');

    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.fuel', ['dateFrom' => '2026-03-31', 'dateTo' => '2026-03-01']))
        ->assertSessionHasErrors('dateTo');
});
