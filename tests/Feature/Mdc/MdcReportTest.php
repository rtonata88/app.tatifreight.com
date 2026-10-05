<?php

use App\Models\Booking;
use App\Models\Client;
use App\Models\Logbook;
use App\Models\MdcCalculation;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('guests and users without view-reports cannot see the mdc report', function () {
    $this->get(route('reports.mdc'))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions(['view-mdc']))->get(route('reports.mdc'))->assertForbidden();
});

test('report totals cover the selected period', function () {
    $user = userWithPermissions(['view-reports']);
    MdcCalculation::factory()->partiallyPaid(100)->create(['mdc_amount' => 400, 'distance_km' => 100, 'total_mass' => 30000, 'calculation_date' => '2025-03-05']);
    MdcCalculation::factory()->create(['mdc_amount' => 200, 'distance_km' => 50, 'total_mass' => 10000, 'calculation_date' => '2025-03-20']);
    MdcCalculation::factory()->create(['mdc_amount' => 999, 'calculation_date' => '2025-05-01']);

    $this->actingAs($user)
        ->get(route('reports.mdc', ['date_from' => '2025-03-01', 'date_to' => '2025-03-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/mdc')
            ->where('totalMdc', 600)
            ->where('totalPaid', 100)
            ->where('totalOutstanding', 500)
            ->where('totalCount', 2)
            ->where('totalDistance', 150)
            ->where('totalMass', 40000)
            ->where('avgPerCalculation', 300)
            ->has('groupedData', 2)
            ->where('groupedData.0.calculation_date', '2025-03-20')
        );
});

test('report defaults to the current month', function () {
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get(route('reports.mdc'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.date_from', now()->startOfMonth()->format('Y-m-d'))
            ->where('filters.date_to', now()->endOfMonth()->format('Y-m-d'))
            ->where('filters.group_by', 'none')
        );
});

test('report groups by vehicle, month and client', function () {
    $user = userWithPermissions(['view-reports']);
    $vehicle = Vehicle::factory()->create(['reg_number' => 'N 1 W']);
    MdcCalculation::factory()->create(['vehicle_id' => $vehicle->id, 'mdc_amount' => 100, 'distance_km' => 10, 'calculation_date' => '2025-01-10']);
    MdcCalculation::factory()->create(['vehicle_id' => $vehicle->id, 'mdc_amount' => 50, 'distance_km' => 5, 'calculation_date' => '2025-02-10']);

    $range = ['date_from' => '2025-01-01', 'date_to' => '2025-12-31'];

    $this->actingAs($user)
        ->get(route('reports.mdc', [...$range, 'group_by' => 'vehicle']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupedData', 1)
            ->where('groupedData.0.reg_number', 'N 1 W')
            ->where('groupedData.0.count', 2)
            ->where('groupedData.0.total_amount', 150)
            ->where('groupedData.0.total_distance', 15)
        );

    $this->actingAs($user)
        ->get(route('reports.mdc', [...$range, 'group_by' => 'month']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupedData', 2)
            ->where('groupedData.0.month_label', 'January 2025')
            ->where('groupedData.1.month_label', 'February 2025')
            ->where('groupedData.1.running_total', 150)
        );

    // Client grouping only includes calculations whose logbook is linked to a booking.
    $client = Client::create(['name' => 'Acme', 'company_name' => 'Acme Ltd', 'email' => 'acme@example.com']);
    $booking = Booking::create([
        'booking_number' => 'BK-1',
        'client_id' => $client->id,
        'vehicle_id' => $vehicle->id,
        'start_date' => '2025-03-01 08:00:00',
        'end_date' => '2025-03-02 08:00:00',
    ]);
    $logbook = Logbook::create([
        'vehicle_id' => $vehicle->id,
        'driver_id' => User::factory()->create()->id,
        'created_by' => User::factory()->create()->id,
        'booking_id' => $booking->id,
        'date' => '2025-03-01',
        'start_odometer' => 100,
        'origin_from' => 'Windhoek',
        'origin_to' => 'Walvis Bay',
    ]);
    MdcCalculation::factory()->create(['logbook_id' => $logbook->id, 'vehicle_id' => $vehicle->id, 'mdc_amount' => 70, 'distance_km' => 7, 'calculation_date' => '2025-03-01']);

    $this->actingAs($user)
        ->get(route('reports.mdc', [...$range, 'group_by' => 'client']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupedData', 1)
            ->where('groupedData.0.client.name', 'Acme')
            ->where('groupedData.0.total_amount', 70)
        );

    $this->actingAs($user)
        ->get(route('reports.mdc', ['date_from' => '2025-03-01', 'date_to' => '2025-03-31']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groupedData.0.client.company_name', 'Acme Ltd')
            ->where('groupedData.0.logbook.booking_number', 'BK-1')
        );
});
