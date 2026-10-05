<?php

use App\Models\Booking;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('calendar requires the view permission', function () {
    $this->actingAs(userWithPermissions([]))->get(route('bookings.calendar'))->assertForbidden();
});

test('calendar defaults to the current month', function () {
    $user = userWithPermissions(['view-bookings', 'edit-bookings']);

    $this->actingAs($user)
        ->get(route('bookings.calendar'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('bookings/calendar')
            ->where('date', now()->format('Y-m-d'))
            ->where('today', now()->format('Y-m-d'))
            ->where('filters.vehicle', '')
            ->where('can.edit', true)
            ->where('can.create', false)
        );
});

test('calendar shows bookings overlapping the requested month', function () {
    $user = userWithPermissions(['view-bookings']);
    $inside = Booking::factory()->create(['start_date' => '2026-03-05 08:00:00', 'end_date' => '2026-03-07 17:00:00', 'distance_km' => 120]);
    $spanning = Booking::factory()->create(['start_date' => '2026-02-20 08:00:00', 'end_date' => '2026-04-02 17:00:00']);
    $endsInMonth = Booking::factory()->create(['start_date' => '2026-02-25 08:00:00', 'end_date' => '2026-03-02 17:00:00']);
    Booking::factory()->create(['start_date' => '2026-05-01 08:00:00', 'end_date' => '2026-05-03 17:00:00']);

    $this->actingAs($user)
        ->get(route('bookings.calendar', ['date' => '2026-03-15']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('date', '2026-03-15')
            ->has('bookings', 3)
            // ordered by start_date
            ->where('bookings.0.id', $spanning->id)
            ->where('bookings.1.id', $endsInMonth->id)
            ->where('bookings.2.id', $inside->id)
            ->where('bookings.2.start_date', '2026-03-05')
            ->where('bookings.2.start_display', '05 Mar 2026, 08:00')
            ->where('bookings.2.duration_days', 3)
            ->where('bookings.2.distance_km', 120)
            ->where('bookings.2.invoice', null)
        );
});

test('calendar filters by vehicle', function () {
    $user = userWithPermissions(['view-bookings']);
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->create(['vehicle_id' => $vehicle->id, 'start_date' => '2026-03-05 08:00:00', 'end_date' => '2026-03-06 08:00:00']);
    Booking::factory()->create(['start_date' => '2026-03-05 08:00:00', 'end_date' => '2026-03-06 08:00:00']);
    // Spans the whole month on another vehicle: must not leak past the vehicle filter.
    Booking::factory()->create(['start_date' => '2026-02-01 08:00:00', 'end_date' => '2026-04-30 08:00:00']);

    $this->actingAs($user)
        ->get(route('bookings.calendar', ['date' => '2026-03-01', 'vehicle' => $vehicle->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('bookings', 1)
            ->where('filters.vehicle', (string) $vehicle->id)
            ->has('vehicles', 3)
        );
});
