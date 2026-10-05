<?php

use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\Reports\DashboardController;
use App\Http\Controllers\Reports\ProfitLossReportController;
use App\Http\Controllers\Reports\VatReportPageController;
use App\Http\Controllers\VatReportController;
use Illuminate\Support\Facades\Route;

// Reports & Analytics (reports.mdc lives in routes/modules/mdc.php)
Route::get('reports/dashboard', DashboardController::class)->name('reports.dashboard')->middleware('can:view-reports');

Route::get('reports/profit-loss', ProfitLossReportController::class)->name('reports.profit-loss')->middleware('can:view-reports');
// Export Profit & Loss report as PDF (reads ?dateFrom=&dateTo=)
Route::get('reports/profit-loss/pdf', [ProfitLossController::class, 'exportPdf'])->name('reports.profit-loss.pdf')->middleware('can:view-reports');

Route::get('reports/vat', VatReportPageController::class)->name('reports.vat')->middleware('can:view-reports');
// Export VAT report as PDF (reads ?dateFrom=&dateTo=)
Route::get('reports/vat/pdf', [VatReportController::class, 'exportPdf'])->name('reports.vat.pdf')->middleware('can:view-reports');
