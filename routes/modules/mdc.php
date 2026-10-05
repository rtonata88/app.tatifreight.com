<?php

use App\Http\Controllers\Mdc\MdcController;
use App\Http\Controllers\Mdc\MdcPaymentController;
use App\Http\Controllers\Mdc\MdcRateCardController;
use App\Http\Controllers\Mdc\MdcReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// MDC Management
Route::get('mdc', [MdcController::class, 'index'])->name('mdc.index')->middleware('can:view-mdc');
Route::get('mdc/record-payment', [MdcPaymentController::class, 'create'])->name('mdc.record-payment')->middleware('can:manage-mdc');
Route::post('mdc/record-payment', [MdcPaymentController::class, 'store'])->name('mdc.record-payment.store')->middleware('can:manage-mdc');
Route::get('mdc/payments', [MdcPaymentController::class, 'index'])->name('mdc.payments')->middleware('can:view-mdc');

// Download MDC payment receipt
Route::get('mdc/payment/{mdcPayment}/receipt', function (\App\Models\MdcPayment $mdcPayment) {
    if (! $mdcPayment->receipt_path || ! Storage::disk('local')->exists($mdcPayment->receipt_path)) {
        abort(404, 'Receipt not found');
    }

    return Storage::disk('local')->download($mdcPayment->receipt_path);
})->name('mdc.payment.receipt')->middleware('can:view-mdc');

// MDC Rate Cards
Route::get('mdc-rates', [MdcRateCardController::class, 'index'])->name('mdc-rates.index')->middleware('can:manage-mdc-rates');
Route::get('mdc-rates/create', [MdcRateCardController::class, 'create'])->name('mdc-rates.create')->middleware('can:manage-mdc-rates');
Route::post('mdc-rates', [MdcRateCardController::class, 'store'])->name('mdc-rates.store')->middleware('can:manage-mdc-rates');
Route::get('mdc-rates/{mdcRateCard}/edit', [MdcRateCardController::class, 'edit'])->name('mdc-rates.edit')->middleware('can:manage-mdc-rates');
Route::put('mdc-rates/{mdcRateCard}', [MdcRateCardController::class, 'update'])->name('mdc-rates.update')->middleware('can:manage-mdc-rates');
Route::patch('mdc-rates/{mdcRateCard}/toggle-status', [MdcRateCardController::class, 'toggleStatus'])->name('mdc-rates.toggle-status')->middleware('can:manage-mdc-rates');
Route::delete('mdc-rates/{mdcRateCard}', [MdcRateCardController::class, 'destroy'])->name('mdc-rates.destroy')->middleware('can:manage-mdc-rates');

// MDC Report
Route::get('reports/mdc', MdcReportController::class)->name('reports.mdc')->middleware('can:view-reports');
