<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientStatementController extends Controller
{
    public function exportPdf(Request $request, Client $client)
    {
        if (!auth()->user()->can('view-clients')) {
            abort(403, 'Unauthorized action.');
        }

        $dateFrom = $request->input('dateFrom', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('dateTo', now()->format('Y-m-d'));

        // Get all invoices for this client
        $invoices = Invoice::with(['payments', 'lineItems'])
            ->where('client_id', $client->id)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->orderBy('invoice_date', 'asc')
            ->get();

        // Get all payments for this client
        $payments = Payment::where('client_id', $client->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date', 'asc')
            ->get();

        // Combine and sort by date
        $transactions = collect();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->invoice_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'description' => 'Invoice - ' . $invoice->description,
                'debit' => $invoice->total,
                'credit' => 0,
            ]);

            // Add payments for this invoice
            foreach ($invoice->payments as $payment) {
                $transactions->push([
                    'date' => $payment->payment_date,
                    'type' => 'payment',
                    'reference' => $payment->payment_reference,
                    'description' => 'Payment - ' . ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'debit' => 0,
                    'credit' => $payment->amount,
                ]);
            }
        }

        // Sort by date
        $transactions = $transactions->sortBy('date')->values();

        // Calculate running balance
        $balance = 0;
        $transactions = $transactions->map(function ($transaction) use (&$balance) {
            $balance += ($transaction['debit'] - $transaction['credit']);
            $transaction['balance'] = $balance;
            return $transaction;
        });

        // Calculate totals
        $totalInvoiced = $invoices->sum('total');
        $totalPaid = $payments->sum('amount');
        $totalOutstanding = $totalInvoiced - $totalPaid;

        $company = CompanySetting::get();

        $pdf = Pdf::loadView('pdf.client-statement', compact(
            'client',
            'transactions',
            'totalInvoiced',
            'totalPaid',
            'totalOutstanding',
            'dateFrom',
            'dateTo',
            'company'
        ));

        $filename = 'Statement-' . ($client->company_name ?: $client->name) . '-' . $dateFrom . '-to-' . $dateTo . '.pdf';
        return $pdf->download($filename);
    }
}
