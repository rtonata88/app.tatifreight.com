<?php

/*
 * Row builders for the report tests. Invoice / Expense / Booking factories belong to
 * other modules (and may be empty), so rows are created directly with Model::create().
 */

use App\Models\Booking;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\User;
use App\Models\Vehicle;

if (! function_exists('reportsInvoice')) {
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{unit: string, amount: float}>  $lines
     */
    function reportsInvoice(array $attributes = [], array $lines = []): Invoice
    {
        static $sequence = 0;
        $sequence++;

        $invoice = Invoice::create([
            'invoice_number' => 'INV-T'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'client_id' => $attributes['client_id'] ?? Client::factory()->create()->id,
            'created_by' => $attributes['created_by'] ?? User::factory()->create()->id,
            'status' => 'paid',
            'invoice_date' => '2026-03-10',
            'due_date' => '2026-04-10',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
            ...$attributes,
        ]);

        foreach ($lines as $line) {
            InvoiceLineItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Line '.$line['unit'],
                'quantity' => 1,
                'unit_price' => $line['amount'],
                'amount' => $line['amount'],
                'unit' => $line['unit'],
            ]);
        }

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    function reportsExpense(array $attributes = []): Expense
    {
        return Expense::create([
            'user_id' => $attributes['user_id'] ?? User::factory()->create()->id,
            'category' => 'fuel',
            'amount' => 0,
            'expense_date' => '2026-03-12',
            'status' => 'approved',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    function reportsBooking(array $attributes = []): Booking
    {
        static $sequence = 0;
        $sequence++;

        return Booking::create([
            'booking_number' => 'BK-T'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'client_id' => $attributes['client_id'] ?? Client::factory()->create()->id,
            'vehicle_id' => $attributes['vehicle_id'] ?? Vehicle::factory()->create()->id,
            'status' => 'completed',
            'start_date' => '2026-03-01 08:00:00',
            'end_date' => '2026-03-05 17:00:00',
            ...$attributes,
        ]);
    }
}
