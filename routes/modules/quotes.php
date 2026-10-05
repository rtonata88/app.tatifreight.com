<?php

use App\Http\Controllers\Operations\QuoteManagementController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

// Quote Management
Route::get('quotes', [QuoteManagementController::class, 'index'])->name('quotes.index')->middleware('can:view-quotes');
Route::get('quotes/create', [QuoteManagementController::class, 'create'])->name('quotes.create')->middleware('can:create-quotes');
Route::post('quotes', [QuoteManagementController::class, 'store'])->name('quotes.store')->middleware('can:create-quotes');
Route::get('quotes/{quote}/edit', [QuoteManagementController::class, 'edit'])->name('quotes.edit')->middleware('can:edit-quotes');
Route::put('quotes/{quote}', [QuoteManagementController::class, 'update'])->name('quotes.update')->middleware('can:edit-quotes');
Route::delete('quotes/{quote}', [QuoteManagementController::class, 'destroy'])->name('quotes.destroy')->middleware('can:delete-quotes');

// Status actions from the index dropdown (old markAsSent/markAsApproved/markAsRejected/markAsExpired)
Route::post('quotes/{quote}/send', [QuoteManagementController::class, 'markAsSent'])->name('quotes.send')->middleware('can:edit-quotes');
Route::post('quotes/{quote}/approve', [QuoteManagementController::class, 'markAsApproved'])->name('quotes.approve')->middleware('can:edit-quotes');
Route::post('quotes/{quote}/reject', [QuoteManagementController::class, 'markAsRejected'])->name('quotes.reject')->middleware('can:edit-quotes');
Route::post('quotes/{quote}/expire', [QuoteManagementController::class, 'markAsExpired'])->name('quotes.expire')->middleware('can:edit-quotes');
Route::post('quotes/{quote}/duplicate', [QuoteManagementController::class, 'duplicate'])->name('quotes.duplicate')->middleware('can:create-quotes');
// create-bookings is checked inside the action (it flashed an error rather than a 403 before).
Route::post('quotes/{quote}/convert-to-booking', [QuoteManagementController::class, 'convertToBooking'])->name('quotes.convert-to-booking')->middleware('can:view-quotes');

// Quote PDF routes (unchanged controller)
Route::get('quotes/{quote}/pdf/view', [QuoteController::class, 'viewPdf'])->name('quotes.pdf.view')->middleware('can:view-quotes');
Route::get('quotes/{quote}/pdf/download', [QuoteController::class, 'downloadPdf'])->name('quotes.pdf.download')->middleware('can:view-quotes');
