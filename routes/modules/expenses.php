<?php

use App\Http\Controllers\Financial\ExpenseController;
use Illuminate\Support\Facades\Route;

// Expense Management
Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('can:view-expenses');
Route::get('expenses/create', [ExpenseController::class, 'create'])->name('expenses.create')->middleware('can:create-expenses');
Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware('can:create-expenses');
Route::get('expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show')->middleware('can:view-expenses');
Route::get('expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit')->middleware('can:edit-expenses');
// POST + _method=put so receipt uploads work (browsers cannot send multipart PUT).
Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware('can:edit-expenses');
Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware('can:delete-expenses');

// The old approveExpense()/rejectExpense() Livewire actions checked edit-expenses.
Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware('can:edit-expenses');
Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject')->middleware('can:edit-expenses');

// Receipts (stored on the public disk under expenses/receipts).
Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt'])->name('expenses.receipt.download')->middleware('can:view-expenses');
Route::delete('expenses/{expense}/receipt', [ExpenseController::class, 'destroyReceipt'])->name('expenses.receipt.destroy')->middleware('can:edit-expenses');
