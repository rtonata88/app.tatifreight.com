<?php

use Illuminate\Support\Facades\Route;

/**
 * Send a signed-in user to the first area they are allowed to see.
 */
$landing = function () {
    $user = auth()->user();

    if ($user->can('view-reports')) {
        return redirect()->route('reports.dashboard');
    }

    // Fallback to bookings if user doesn't have report access
    if ($user->can('view-bookings')) {
        return redirect()->route('bookings.index');
    }

    // Fallback to vehicles if no bookings access
    return redirect()->route('vehicles.index');
};

Route::get('/', function () use ($landing) {
    return auth()->check() ? $landing() : redirect()->route('login');
})->name('home');

// Redirect old dashboard route to analytics
Route::get('dashboard', $landing)->middleware(['auth', 'verified'])->name('dashboard');

// Each business module registers its own routes in routes/modules/*.php
Route::middleware(['auth'])->group(function () {
    foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
        require $moduleRoutes;
    }
});

require __DIR__.'/settings.php';
