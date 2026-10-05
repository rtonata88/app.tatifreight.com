<?php

use App\Http\Controllers\Financial\InvoiceManagementController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// Invoicing Management
Route::get('invoices', [InvoiceManagementController::class, 'index'])->name('invoices.index')->middleware('can:view-invoices');
Route::get('invoices/create', [InvoiceManagementController::class, 'create'])->name('invoices.create')->middleware('can:create-invoices');
Route::post('invoices', [InvoiceManagementController::class, 'store'])->name('invoices.store')->middleware('can:create-invoices');
Route::get('invoices/{invoice}/edit', [InvoiceManagementController::class, 'edit'])->name('invoices.edit')->middleware('can:edit-invoices');
Route::put('invoices/{invoice}', [InvoiceManagementController::class, 'update'])->name('invoices.update')->middleware('can:edit-invoices');
Route::delete('invoices/{invoice}', [InvoiceManagementController::class, 'destroy'])->name('invoices.destroy')->middleware('can:delete-invoices');

// Index dropdown actions (old markAsSent / markAsPaid both checked edit-invoices)
Route::post('invoices/{invoice}/send', [InvoiceManagementController::class, 'markAsSent'])->name('invoices.send')->middleware('can:edit-invoices');
Route::post('invoices/{invoice}/mark-paid', [InvoiceManagementController::class, 'markAsPaid'])->name('invoices.mark-paid')->middleware('can:edit-invoices');

// Payment modal on the edit screen (old recordPayment had no check beyond the edit page's)
Route::post('invoices/{invoice}/payments', [InvoiceManagementController::class, 'recordPayment'])->name('invoices.payments.store')->middleware('can:edit-invoices');

// Invoice PDF routes (unchanged controller)
Route::get('invoices/{invoice}/pdf/view', [InvoiceController::class, 'viewPdf'])->name('invoices.pdf.view')->middleware('can:view-invoices');
Route::get('invoices/{invoice}/pdf/download', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf.download')->middleware('can:view-invoices');
