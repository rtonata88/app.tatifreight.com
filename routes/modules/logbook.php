<?php

use App\Http\Controllers\Fleet\LogbookController;
use Illuminate\Support\Facades\Route;

// Logbook Management
Route::get('logbook', [LogbookController::class, 'index'])->name('logbook.index')->middleware('can:view-logbook');
Route::get('logbook/create', [LogbookController::class, 'create'])->name('logbook.create')->middleware('can:create-logbook');
Route::post('logbook', [LogbookController::class, 'store'])->name('logbook.store')->middleware('can:create-logbook');
Route::get('logbook/{logbook}/edit', [LogbookController::class, 'edit'])->name('logbook.edit')->middleware('can:edit-logbook');
Route::put('logbook/{logbook}', [LogbookController::class, 'update'])->name('logbook.update')->middleware('can:edit-logbook');
Route::delete('logbook/{logbook}', [LogbookController::class, 'destroy'])->name('logbook.destroy')->middleware('can:delete-logbook');
