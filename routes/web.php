<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->can('view-reports')) {
            return redirect()->route('reports.dashboard');
        }
        // Fallback to bookings if user doesn't have report access
        if (auth()->user()->can('view-bookings')) {
            return redirect()->route('bookings.index');
        }
        // Fallback to vehicles if no bookings access
        return redirect()->route('vehicles.index');
    }
    return redirect()->route('login');
})->name('home');

// Redirect old dashboard route to analytics
Route::get('dashboard', function () {
    if (auth()->user()->can('view-reports')) {
        return redirect()->route('reports.dashboard');
    }
    // Fallback to bookings if user doesn't have report access
    if (auth()->user()->can('view-bookings')) {
        return redirect()->route('bookings.index');
    }
    // Fallback to vehicles if no bookings access
    return redirect()->route('vehicles.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Fleet Management (Vehicles)
    Volt::route('vehicles', 'vehicles.index')
        ->name('vehicles.index')
        ->middleware('can:view-vehicles');

    Volt::route('vehicles/create', 'vehicles.create')
        ->name('vehicles.create')
        ->middleware('can:create-vehicles');

    Volt::route('vehicles/{vehicle}', 'vehicles.show')
        ->name('vehicles.show')
        ->middleware('can:view-vehicles');

    Volt::route('vehicles/{vehicle}/edit', 'vehicles.edit')
        ->name('vehicles.edit')
        ->middleware('can:edit-vehicles');

    // MDC Management
    Volt::route('mdc', 'mdc.index')
        ->name('mdc.index')
        ->middleware('can:view-mdc');

    Volt::route('mdc/record-payment', 'mdc.record-payment')
        ->name('mdc.record-payment')
        ->middleware('can:manage-mdc');

    Volt::route('mdc/payments', 'mdc.payments')
        ->name('mdc.payments')
        ->middleware('can:view-mdc');

    // Download MDC payment receipt
    Route::get('mdc/payment/{mdcPayment}/receipt', function (\App\Models\MdcPayment $mdcPayment) {
        if (!$mdcPayment->receipt_path || !Storage::disk('local')->exists($mdcPayment->receipt_path)) {
            abort(404, 'Receipt not found');
        }
        
        return Storage::disk('local')->download($mdcPayment->receipt_path);
    })->name('mdc.payment.receipt')->middleware('can:view-mdc');

    // MDC Rate Cards
    Volt::route('mdc-rates', 'mdc-rates.index')
        ->name('mdc-rates.index')
        ->middleware('can:manage-mdc-rates');

    Volt::route('mdc-rates/create', 'mdc-rates.create')
        ->name('mdc-rates.create')
        ->middleware('can:manage-mdc-rates');

    Volt::route('mdc-rates/{mdcRateCard}/edit', 'mdc-rates.edit')
        ->name('mdc-rates.edit')
        ->middleware('can:manage-mdc-rates');

    // Logbook Management
    Volt::route('logbook', 'logbook.index')
        ->name('logbook.index')
        ->middleware('can:view-logbook');

    Volt::route('logbook/create', 'logbook.create')
        ->name('logbook.create')
        ->middleware('can:create-logbook');

    Volt::route('logbook/{logbook}/edit', 'logbook.edit')
        ->name('logbook.edit')
        ->middleware('can:edit-logbook');

    // Client Management
    Volt::route('clients', 'clients.index')
        ->name('clients.index')
        ->middleware('can:view-clients');

    Volt::route('clients/create', 'clients.create')
        ->name('clients.create')
        ->middleware('can:create-clients');

    Volt::route('clients/{client}/edit', 'clients.edit')
        ->name('clients.edit')
        ->middleware('can:edit-clients');

    Volt::route('clients/{client}/statement', 'clients.statement')
        ->name('clients.statement')
        ->middleware('can:view-clients');

    // Export client statement as PDF
    Route::get('clients/{client}/statement/pdf', [App\Http\Controllers\ClientStatementController::class, 'exportPdf'])
        ->name('clients.statement.pdf')
        ->middleware('can:view-clients');

    Volt::route('clients/{client}/documents', 'clients.documents')
        ->name('clients.documents')
        ->middleware('can:view-documents');

    // Download client document
    Route::get('documents/{document}/download', function (\App\Models\Document $document) {
        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File not found');
        }
        
        return Storage::disk('local')->download($document->file_path, $document->file_name);
    })->name('documents.download')->middleware('can:view-documents');

    // Booking Management
    Volt::route('bookings', 'bookings.index')
        ->name('bookings.index')
        ->middleware('can:view-bookings');

    Volt::route('bookings/calendar', 'bookings.calendar')
        ->name('bookings.calendar')
        ->middleware('can:view-bookings');

    Volt::route('bookings/create', 'bookings.create')
        ->name('bookings.create')
        ->middleware('can:create-bookings');

    Volt::route('bookings/{booking}/edit', 'bookings.edit')
        ->name('bookings.edit')
        ->middleware('can:edit-bookings');

    // Quotations Management
    Volt::route('quotes', 'quotes.index')
        ->name('quotes.index')
        ->middleware('can:view-quotes');

    Volt::route('quotes/create', 'quotes.create')
        ->name('quotes.create')
        ->middleware('can:create-quotes');

    Volt::route('quotes/{quote}/edit', 'quotes.edit')
        ->name('quotes.edit')
        ->middleware('can:edit-quotes');

    // Quote PDF routes
    Route::get('quotes/{quote}/pdf/view', [App\Http\Controllers\QuoteController::class, 'viewPdf'])
        ->name('quotes.pdf.view')
        ->middleware('can:view-quotes');

    Route::get('quotes/{quote}/pdf/download', [App\Http\Controllers\QuoteController::class, 'downloadPdf'])
        ->name('quotes.pdf.download')
        ->middleware('can:view-quotes');

    // Invoicing Management
    Volt::route('invoices', 'invoices.index')
        ->name('invoices.index')
        ->middleware('can:view-invoices');

    Volt::route('invoices/create', 'invoices.create')
        ->name('invoices.create')
        ->middleware('can:create-invoices');

    Volt::route('invoices/{invoice}/edit', 'invoices.edit')
        ->name('invoices.edit')
        ->middleware('can:edit-invoices');

    // Invoice PDF routes
    Route::get('invoices/{invoice}/pdf/view', [App\Http\Controllers\InvoiceController::class, 'viewPdf'])
        ->name('invoices.pdf.view')
        ->middleware('can:view-invoices');

    Route::get('invoices/{invoice}/pdf/download', [App\Http\Controllers\InvoiceController::class, 'downloadPdf'])
        ->name('invoices.pdf.download')
        ->middleware('can:view-invoices');

    // Expense Management
    Volt::route('expenses', 'expenses.index')
        ->name('expenses.index')
        ->middleware('can:view-expenses');

    Volt::route('expenses/create', 'expenses.create')
        ->name('expenses.create')
        ->middleware('can:create-expenses');

    Volt::route('expenses/{expense}', 'expenses.show')
        ->name('expenses.show')
        ->middleware('can:view-expenses');

    Volt::route('expenses/{expense}/edit', 'expenses.edit')
        ->name('expenses.edit')
        ->middleware('can:edit-expenses');

    // Rate Card Management
    Volt::route('rate-cards', 'rate-cards.index')
        ->name('rate-cards.index')
        ->middleware('can:view-rate-cards');

    Volt::route('rate-cards/create', 'rate-cards.create')
        ->name('rate-cards.create')
        ->middleware('can:create-rate-cards');

    Volt::route('rate-cards/{rateCard}/edit', 'rate-cards.edit')
        ->name('rate-cards.edit')
        ->middleware('can:edit-rate-cards');

    // Document Management
    Volt::route('documents', 'documents.index')
        ->name('documents.index')
        ->middleware('can:view-documents');

    Volt::route('documents/upload', 'documents.upload')
        ->name('documents.upload')
        ->middleware('can:create-documents');

    Volt::route('documents/{document}/edit', 'documents.edit')
        ->name('documents.edit')
        ->middleware('can:edit-documents');

    // Reports & Analytics
    Volt::route('reports/dashboard', 'reports.dashboard')
        ->name('reports.dashboard')
        ->middleware('can:view-reports');

    Volt::route('reports/profit-loss', 'reports.profit-loss')
        ->name('reports.profit-loss')
        ->middleware('can:view-reports');

    // Export Profit & Loss report as PDF
    Route::get('reports/profit-loss/pdf', [App\Http\Controllers\ProfitLossController::class, 'exportPdf'])
        ->name('reports.profit-loss.pdf')
        ->middleware('can:view-reports');

    Volt::route('reports/mdc', 'reports.mdc')
        ->name('reports.mdc')
        ->middleware('can:view-reports');

    Volt::route('reports/vat', 'reports.vat')
        ->name('reports.vat')
        ->middleware('can:view-reports');

    // Export VAT report as PDF
    Route::get('reports/vat/pdf', [App\Http\Controllers\VatReportController::class, 'exportPdf'])
        ->name('reports.vat.pdf')
        ->middleware('can:view-reports');

    // User & Role Management (Admin only)
    Volt::route('users', 'users.index')
        ->name('users.index')
        ->middleware('role:admin');

    Volt::route('users/create', 'users.create')
        ->name('users.create')
        ->middleware('role:admin');

    Volt::route('users/{user}/edit', 'users.edit')
        ->name('users.edit')
        ->middleware('role:admin');

    Volt::route('roles', 'roles.index')
        ->name('roles.index')
        ->middleware('role:admin');

    // Company Settings (Admin only)
    Volt::route('settings/company', 'settings.company')
        ->name('settings.company')
        ->middleware('role:admin');

    Volt::route('settings/bank-accounts', 'settings.bank-accounts')
        ->name('settings.bank-accounts')
        ->middleware('role:admin');

    // Settings Routes
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
