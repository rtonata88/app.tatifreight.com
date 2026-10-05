<?php

use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\CompanySettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// User & Role Management, Company Settings (Admin only)
Route::middleware('role:admin')->group(function () {
    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Roles & permissions (read-only overview, as before)
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

    // Company settings
    Route::get('settings/company', [CompanySettingController::class, 'edit'])->name('settings.company');
    // POST + _method=put from the client so logo/signature uploads work.
    Route::put('settings/company', [CompanySettingController::class, 'update'])->name('settings.company.update');
    Route::delete('settings/company/logo', [CompanySettingController::class, 'destroyLogo'])->name('settings.company.logo.destroy');
    Route::delete('settings/company/signature', [CompanySettingController::class, 'destroySignature'])->name('settings.company.signature.destroy');

    // Company bank accounts
    Route::get('settings/bank-accounts', [BankAccountController::class, 'index'])->name('settings.bank-accounts');
    Route::post('settings/bank-accounts', [BankAccountController::class, 'store'])->name('settings.bank-accounts.store');
    Route::put('settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('settings.bank-accounts.update');
    Route::patch('settings/bank-accounts/{bankAccount}/primary', [BankAccountController::class, 'setPrimary'])->name('settings.bank-accounts.primary');
    Route::patch('settings/bank-accounts/{bankAccount}/toggle-active', [BankAccountController::class, 'toggleActive'])->name('settings.bank-accounts.toggle-active');
    Route::delete('settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('settings.bank-accounts.destroy');
});
