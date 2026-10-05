<?php

use App\Http\Controllers\Financial\RateCardController;
use Illuminate\Support\Facades\Route;

// Rate Cards
Route::get('rate-cards', [RateCardController::class, 'index'])->name('rate-cards.index')->middleware('can:view-rate-cards');
Route::get('rate-cards/create', [RateCardController::class, 'create'])->name('rate-cards.create')->middleware('can:create-rate-cards');
Route::post('rate-cards', [RateCardController::class, 'store'])->name('rate-cards.store')->middleware('can:create-rate-cards');
Route::get('rate-cards/{rateCard}/edit', [RateCardController::class, 'edit'])->name('rate-cards.edit')->middleware('can:edit-rate-cards');
Route::put('rate-cards/{rateCard}', [RateCardController::class, 'update'])->name('rate-cards.update')->middleware('can:edit-rate-cards');
Route::patch('rate-cards/{rateCard}/toggle-active', [RateCardController::class, 'toggleActive'])->name('rate-cards.toggle-active')->middleware('can:edit-rate-cards');
Route::delete('rate-cards/{rateCard}', [RateCardController::class, 'destroy'])->name('rate-cards.destroy')->middleware('can:delete-rate-cards');
