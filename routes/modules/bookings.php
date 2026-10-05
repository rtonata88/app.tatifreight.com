<?php

use App\Http\Controllers\Operations\BookingCalendarController;
use App\Http\Controllers\Operations\BookingController;
use Illuminate\Support\Facades\Route;

// Booking Management (literal paths before {booking})
Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index')->middleware('can:view-bookings');
Route::get('bookings/calendar', BookingCalendarController::class)->name('bookings.calendar')->middleware('can:view-bookings');
Route::get('bookings/create', [BookingController::class, 'create'])->name('bookings.create')->middleware('can:create-bookings');
Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store')->middleware('can:create-bookings');
Route::get('bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit')->middleware('can:edit-bookings');
Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update')->middleware('can:edit-bookings');
Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status')->middleware('can:edit-bookings');
Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy')->middleware('can:delete-bookings');
