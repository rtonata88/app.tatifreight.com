<?php

use App\Http\Controllers\Fleet\VehicleController;
use Illuminate\Support\Facades\Route;

// Fleet Management (Vehicles)
Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index')->middleware('can:view-vehicles');
Route::get('vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create')->middleware('can:create-vehicles');
Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store')->middleware('can:create-vehicles');
Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show')->middleware('can:view-vehicles');
Route::get('vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit')->middleware('can:edit-vehicles');
// POST + _method=put so file uploads work (browsers cannot send multipart PUT).
Route::put('vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update')->middleware('can:edit-vehicles');
Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy')->middleware('can:delete-vehicles');
