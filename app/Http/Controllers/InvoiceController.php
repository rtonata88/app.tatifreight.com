<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    /**
     * View invoice as PDF in browser
     */
    public function viewPdf(Invoice $invoice)
    {
        // Check permission
        if (!auth()->user()->can('view-invoices')) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $invoice->load(['client', 'lineItems.vehicle.vehicleType', 'createdBy', 'payments']);

        // Get company settings
        $company = CompanySetting::get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'company'));

        // Return PDF for inline viewing
        return $pdf->stream($invoice->invoice_number . '.pdf');
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(Invoice $invoice)
    {
        // Check permission
        if (!auth()->user()->can('view-invoices')) {
            abort(403, 'Unauthorized action.');
        }

        // Load relationships
        $invoice->load(['client', 'lineItems.vehicle.vehicleType', 'createdBy', 'payments']);

        // Get company settings
        $company = CompanySetting::get();

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'company'));

        // Return PDF for download
        return $pdf->download($invoice->invoice_number . '.pdf');
    }
}
