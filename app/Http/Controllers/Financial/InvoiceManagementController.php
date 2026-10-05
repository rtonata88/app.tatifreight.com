<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\CompanyBankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/invoices/{index,create,edit}.
 * PDF view/download stay in App\Http\Controllers\InvoiceController.
 */
class InvoiceManagementController extends Controller
{
    /** VAT rate hard-coded in the old create/edit components. */
    public const TAX_RATE = 15;

    /** Status options offered by the old edit screen and its validation rule. */
    public const STATUSES = ['draft', 'sent', 'unpaid', 'partial', 'paid', 'overdue'];

    public const UNITS = ['trip', 'day', 'hour', 'km', 'tonne', 'load', 'pallet', 'container', 'cbm', 'item', 'week', 'month'];

    public const PAYMENT_METHODS = ['bank_transfer', 'cash', 'cheque', 'eft', 'card'];

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $user = $request->user();

        $invoices = Invoice::with(['client', 'booking', 'createdBy'])
            ->when($search, function ($q) use ($search) {
                // Same (un-grouped) where/orWhereHas as the old component.
                $q->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%');
                    });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'booking_number' => $invoice->booking_id ? $invoice->booking?->booking_number : null,
                'client_name' => $invoice->client?->name,
                'client_company' => $invoice->client?->company_name,
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'is_overdue' => $invoice->due_date !== null && $invoice->due_date->isPast() && $invoice->status !== 'paid',
                'total' => (float) $invoice->total,
                'amount_paid' => (float) $invoice->amount_paid,
                'amount_due' => (float) $invoice->amount_due,
                'status' => $invoice->status,
            ]);

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'draft' => Invoice::where('status', 'draft')->count(),
                'sent' => Invoice::where('status', 'sent')->count(),
                'unpaid' => Invoice::where('status', 'unpaid')->count(),
                'partial' => Invoice::where('status', 'partial')->count(),
                'paid' => Invoice::where('status', 'paid')->count(),
                'overdue' => Invoice::where('status', 'overdue')->count(),
                'total_unpaid' => (float) Invoice::where('status', 'unpaid')->sum('amount_due'),
                'total_overdue' => (float) Invoice::where('status', 'overdue')->sum('amount_due'),
            ],
            'can' => [
                'view' => $user->can('view-invoices'),
                'create' => $user->can('create-invoices'),
                'edit' => $user->can('edit-invoices'),
                'delete' => $user->can('delete-invoices'),
            ],
        ]);
    }

    public function create(): Response
    {
        $primaryAccount = CompanyBankAccount::primary();

        $bookings = Booking::with(['client', 'vehicle.vehicleType'])
            ->where('status', 'completed')
            ->whereDoesntHave('invoice')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('invoices/create', [
            'clients' => $this->clientOptions(),
            'bookings' => $bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'label' => $booking->booking_number.' - '.$booking->client?->name.' ('.$booking->start_date?->format('d M Y').')',
                ...self::bookingPrefill($booking),
            ])->values(),
            'vehicles' => $this->vehicleOptions(),
            'bankAccounts' => $this->bankAccountOptions(),
            'defaults' => [
                'invoice_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'company_bank_account_id' => $primaryAccount?->id,
            ],
            // updatedClientId() counted payment terms from today, not from the invoice date.
            'today' => now()->format('Y-m-d'),
            'taxRate' => self::TAX_RATE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_bank_account_id' => 'nullable|exists:company_bank_accounts,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            ...$this->itemRules(),
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items)) {
            return back()->with('error', 'Please add at least one line item');
        }

        [$lines, $totals] = self::calculate($items);

        DB::transaction(function () use ($validated, $lines, $totals, $request) {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'client_id' => $validated['client_id'],
                'company_bank_account_id' => ($validated['company_bank_account_id'] ?? null) ?: null,
                'booking_id' => ($validated['booking_id'] ?? null) ?: null,
                'created_by' => $request->user()->id,
                'status' => 'draft',
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'description' => $validated['description'] ?? null,
                ...$totals,
                'amount_paid' => 0,
                'amount_due' => $totals['total'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $invoice->lineItems()->create($line);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully!');
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $invoice->load('lineItems');

        return Inertia::render('invoices/edit', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'company_bank_account_id' => $invoice->company_bank_account_id,
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'description' => $invoice->description,
                'notes' => $invoice->notes,
                'status' => $invoice->status,
                'subtotal' => (float) $invoice->subtotal,
                'tax_amount' => (float) $invoice->tax_amount,
                'total' => (float) $invoice->total,
                'amount_paid' => (float) $invoice->amount_paid,
                'amount_due' => (float) $invoice->amount_due,
                'is_overdue' => $invoice->due_date !== null && $invoice->due_date->isPast() && (float) $invoice->amount_due > 0,
                'line_items' => $invoice->lineItems->map(fn ($item) => [
                    'id' => $item->id,
                    'vehicle_id' => $item->vehicle_id ?? '',
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'unit' => $item->unit,
                ])->values(),
            ],
            'payments' => $invoice->payments()->orderBy('payment_date', 'desc')->get()->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'payment_date' => $payment->payment_date?->format('Y-m-d'),
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_reference' => $payment->transaction_reference,
            ])->values(),
            'clients' => $this->clientOptions(),
            'vehicles' => $this->vehicleOptions(),
            'bankAccounts' => $this->bankAccountOptions(),
            'today' => now()->format('Y-m-d'),
            'taxRate' => self::TAX_RATE,
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_bank_account_id' => 'nullable|exists:company_bank_accounts,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:'.implode(',', self::STATUSES),
            ...$this->itemRules(),
            'items.*.id' => 'nullable|integer',
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items)) {
            return back()->with('error', 'Please add at least one line item');
        }

        [$lines, $totals] = self::calculate($items);

        DB::transaction(function () use ($invoice, $validated, $items, $lines, $totals) {
            // Recalculate amount_due from the recorded payments.
            $totalPaid = (float) $invoice->payments()->sum('amount');

            $invoice->update([
                'client_id' => $validated['client_id'],
                'company_bank_account_id' => ($validated['company_bank_account_id'] ?? null) ?: null,
                'status' => $validated['status'],
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'],
                'description' => $validated['description'] ?? null,
                ...$totals,
                'amount_paid' => $totalPaid,
                'amount_due' => $totals['total'] - $totalPaid,
                'notes' => $validated['notes'] ?? null,
            ]);

            // The old screen deleted a line as soon as its row was removed; here rows removed in
            // the form are deleted on save. Existing lines are updated, new ones created.
            $keepIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $invoice->lineItems()->whereNotIn('id', $keepIds)->delete();

            foreach (array_values($items) as $i => $item) {
                $existing = ! empty($item['id']) ? $invoice->lineItems()->whereKey($item['id'])->first() : null;

                if ($existing) {
                    $existing->update($lines[$i]);
                } else {
                    $invoice->lineItems()->create($lines[$i]);
                }
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully!');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return back()->with('success', 'Invoice deleted successfully');
    }

    /** Old index markAsSent() (edit-invoices). */
    public function markAsSent(Invoice $invoice): RedirectResponse
    {
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Invoice marked as sent');
    }

    /** Old index markAsPaid() (edit-invoices). Settles the invoice without a payment record, as before. */
    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        $invoice->update([
            'status' => 'paid',
            'amount_paid' => $invoice->total,
            'amount_due' => 0,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Invoice marked as paid');
    }

    /** Old edit recordPayment() — the payment modal. */
    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        // Same field names as the Livewire properties so validation messages read the same.
        $validated = $request->validate([
            'paymentAmount' => 'required|numeric|min:0.01|max:'.$invoice->amount_due,
            'paymentDate' => 'required|date',
            'paymentMethod' => 'required|string',
            'transactionReference' => 'nullable|string|max:255',
            'paymentNotes' => 'nullable|string',
        ]);

        $amount = (float) $validated['paymentAmount'];

        DB::transaction(function () use ($invoice, $validated, $amount) {
            $lastPayment = Payment::latest('id')->first();
            $nextNumber = $lastPayment ? (int) substr($lastPayment->payment_reference, 4) + 1 : 1;

            Payment::create([
                'payment_reference' => 'PAY-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'amount' => $amount,
                'payment_date' => $validated['paymentDate'],
                'payment_method' => $validated['paymentMethod'],
                'transaction_reference' => $validated['transactionReference'] ?? null,
                'notes' => $validated['paymentNotes'] ?? null,
            ]);

            // NOTE: kept exactly as the old component: the sum already includes the payment just
            // created, and the amount is added again, so a new payment is counted twice here.
            // (The next invoice save recomputes amount_paid from the payments correctly.)
            $totalPaid = (float) $invoice->payments()->sum('amount') + $amount;
            $amountDue = (float) $invoice->total - $totalPaid;

            $invoice->update([
                'amount_paid' => $totalPaid,
                'amount_due' => $amountDue,
                'status' => $amountDue > 0 ? 'partial' : 'paid',
                'paid_at' => $amountDue <= 0 ? now() : null,
            ]);
        });

        return back()->with('success', 'Payment recorded successfully!');
    }

    /**
     * loadBookingDetails(): client, description and line items for a completed booking.
     *
     * @return array{client_id: int, description: string, items: list<array<string, mixed>>}
     */
    public static function bookingPrefill(Booking $booking): array
    {
        $vehicle = $booking->vehicle;
        $type = $vehicle?->vehicleType;
        $items = [];

        // Carbon 3 returns a fractional day count; the old code (written for Carbon 2) expected whole days.
        $days = (int) $booking->start_date->diffInDays($booking->end_date) ?: 1;
        $unitPrice = round((float) ($type->base_rate_daily ?? 0), 2);

        $items[] = [
            'id' => null,
            'vehicle_id' => $booking->vehicle_id,
            'description' => 'Vehicle Rental - '.($type->name ?? '').' ('.($vehicle->reg_number ?? '').')',
            'quantity' => $days,
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'unit' => 'day',
        ];

        // Mass Distance Charge when distance, load and tare weight are known.
        if ($booking->distance_km && $booking->load_weight && $vehicle?->tare_weight) {
            $totalMass = round((float) $vehicle->tare_weight + (float) $booking->load_weight, 2);
            $mdcAmount = round(($totalMass * (float) $booking->distance_km * ((float) ($type->base_rate_per_km ?? 0))) / 100, 2);

            $items[] = [
                'id' => null,
                'vehicle_id' => $booking->vehicle_id,
                'description' => "Mass Distance Charge - {$booking->distance_km}km × ".number_format($totalMass, 2).'t',
                'quantity' => 1,
                'unit_price' => number_format($mdcAmount, 2, '.', ''),
                'unit' => 'trip',
            ];
        }

        return [
            'client_id' => $booking->client_id,
            'description' => "Invoice for booking {$booking->booking_number}",
            'items' => $items,
        ];
    }

    /**
     * Server-side calculateLineAmount() / calculateTotals():
     * amount = qty × unit price, VAT = subtotal × 15%, total = subtotal + VAT.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: array{subtotal: float, tax_amount: float, total: float}}
     */
    public static function calculate(array $items): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach (array_values($items) as $i => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $amount = $quantity * $unitPrice;
            $subtotal += $amount;

            $lines[$i] = [
                'vehicle_id' => ($item['vehicle_id'] ?? null) ?: null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'unit' => $item['unit'] ?? 'trip',
            ];
        }

        $taxAmount = $subtotal * (self::TAX_RATE / 100);

        return [$lines, [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]];
    }

    /**
     * INV-000001, INV-000002… from the latest invoice. Soft-deleted invoices are included:
     * the old code skipped them and then collided with the unique invoice_number index.
     */
    private function nextInvoiceNumber(): string
    {
        $lastInvoice = Invoice::withTrashed()->latest('id')->first();
        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, 4) + 1 : 1;

        return 'INV-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Line item rules. The old components had no rules for line items (only error slots
     * for lineItems.*.vehicle_id / description); these keep bad rows from hitting the DB.
     *
     * @return array<string, string>
     */
    private function itemRules(): array
    {
        return [
            'items' => 'nullable|array',
            'items.*.vehicle_id' => 'nullable|exists:vehicles,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit' => 'required|in:'.implode(',', self::UNITS),
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
        ];
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function clientOptions()
    {
        return Client::where('is_active', true)->orderBy('name')->get()->map(fn (Client $client) => [
            'value' => $client->id,
            'label' => $client->name.($client->company_name ? ' ('.$client->company_name.')' : ''),
            'payment_terms_days' => $client->payment_terms_days,
        ])->values();
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function vehicleOptions()
    {
        return Vehicle::with('vehicleType')->orderBy('reg_number')->get()->map(fn (Vehicle $vehicle) => [
            'value' => $vehicle->id,
            'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name,
        ])->values();
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function bankAccountOptions()
    {
        return CompanyBankAccount::active()->map(fn (CompanyBankAccount $account) => [
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_number' => $account->account_number,
            'is_primary' => (bool) $account->is_primary,
        ])->values();
    }
}
