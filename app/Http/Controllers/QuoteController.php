<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class QuoteController extends Controller
{
    /**
     * View quote as PDF in browser
     */
    public function viewPdf(Quote $quote)
    {
        // Check permission
        if (!auth()->user()->can('view-quotes')) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $quote->load(['client', 'lineItems.vehicle.vehicleType', 'createdBy']);

        // Get company settings
        $company = CompanySetting::get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.quote', compact('quote', 'company'));

        // Return PDF for inline viewing
        return $pdf->stream($quote->quote_number . '.pdf');
    }

    /**
     * Download quote as PDF
     */
    public function downloadPdf(Quote $quote)
    {
        // Check permission
        if (!auth()->user()->can('view-quotes')) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $quote->load(['client', 'lineItems.vehicle.vehicleType', 'createdBy']);

        // Get company settings
        $company = CompanySetting::get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.quote', compact('quote', 'company'));

        // Return PDF for download
        return $pdf->download($quote->quote_number . '.pdf');
    }
}
