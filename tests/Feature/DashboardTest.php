<?php

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('users with report access land on the analytics dashboard', function () {
    $this->actingAs(userWithPermissions(['view-reports']))
        ->get('/dashboard')
        ->assertRedirect(route('reports.dashboard'));
});

test('users with only booking access land on bookings', function () {
    $this->actingAs(userWithPermissions(['view-bookings']))
        ->get('/dashboard')
        ->assertRedirect(route('bookings.index'));
});

test('other users land on vehicles', function () {
    $this->actingAs(userWithPermissions([]))
        ->get('/dashboard')
        ->assertRedirect(route('vehicles.index'));
});
