<?php

use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Clients\ClientDocumentController;
use App\Http\Controllers\ClientStatementController;
use Illuminate\Support\Facades\Route;

// Client Management
Route::get('clients', [ClientController::class, 'index'])->name('clients.index')->middleware('can:view-clients');
Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create')->middleware('can:create-clients');
Route::post('clients', [ClientController::class, 'store'])->name('clients.store')->middleware('can:create-clients');
// Create a client without leaving a quote, invoice or booking form; answers with JSON.
Route::post('clients/quick', [ClientController::class, 'quickStore'])->name('clients.quick-store')->middleware('can:create-clients');
Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit')->middleware('can:edit-clients');
Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update')->middleware('can:edit-clients');
Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy')->middleware('can:delete-clients');

// Customer statement (+ PDF export, unchanged controller)
Route::get('clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement')->middleware('can:view-clients');
Route::get('clients/{client}/statement/pdf', [ClientStatementController::class, 'exportPdf'])->name('clients.statement.pdf')->middleware('can:view-clients');

// Client documents (stored on the local disk; downloaded via documents.download)
Route::get('clients/{client}/documents', [ClientDocumentController::class, 'index'])->name('clients.documents')->middleware('can:view-documents');
Route::post('clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store')->middleware('can:create-documents');
Route::delete('clients/{client}/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('clients.documents.destroy')->middleware('can:delete-documents');
