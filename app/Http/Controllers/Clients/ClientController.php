<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/clients/{index,create,edit,statement}.
 */
class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $classification = (string) $request->query('classification', '');
        $status = (string) $request->query('status', '');

        $clients = Client::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('company_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($classification, fn ($q) => $q->where('classification', $classification))
            ->when($status !== '', fn ($q) => $q->where('is_active', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'classification' => $client->classification,
                'credit_limit' => (float) $client->credit_limit,
                'is_active' => (bool) $client->is_active,
            ]);

        $user = $request->user();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $search, 'classification' => $classification, 'status' => $status],
            'can' => [
                'create' => $user->can('create-clients'),
                'edit' => $user->can('edit-clients'),
                'delete' => $user->can('delete-clients'),
                'viewDocuments' => $user->can('view-documents'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clients/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'email' => 'required|email|unique:clients,email',
        ]);

        Client::create($this->attributes($validated));

        return redirect()->route('clients.index')->with('success', 'Client created successfully!');
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('clients/edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'secondary_phone' => $client->secondary_phone,
                'address' => $client->address,
                'city' => $client->city,
                'region' => $client->province,
                'postal_code' => $client->postal_code,
                'classification' => $client->classification,
                'tax_number' => $client->tax_number,
                'credit_limit' => $client->credit_limit,
                'payment_terms_days' => $client->payment_terms_days,
                'notes' => $client->notes,
                'is_active' => (bool) $client->is_active,
            ],
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        // Same rules the old edit screen used (no unique check on email).
        $validated = $request->validate($this->rules());

        $client->update($this->attributes($validated));

        return redirect()->route('clients.index')->with('success', 'Client updated successfully!');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        if ($request->user()->can('delete-clients')) {
            $client->delete();

            return back()->with('success', 'Client deleted successfully');
        }

        return back();
    }

    public function statement(Request $request, Client $client): Response
    {
        $dateFrom = (string) ($request->query('dateFrom') ?: now()->startOfYear()->format('Y-m-d'));
        $dateTo = (string) ($request->query('dateTo') ?: now()->format('Y-m-d'));

        $invoices = Invoice::with(['payments', 'lineItems'])
            ->where('client_id', $client->id)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->orderBy('invoice_date', 'asc')
            ->get();

        $payments = Payment::where('client_id', $client->id)
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->orderBy('payment_date', 'asc')
            ->get();

        // Combine invoices and the payments made against them, as the old screen did.
        $transactions = collect();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->invoice_date,
                'type' => 'invoice',
                'reference' => $invoice->invoice_number,
                'description' => 'Invoice - '.$invoice->description,
                'debit' => (float) $invoice->total,
                'credit' => 0.0,
            ]);

            foreach ($invoice->payments as $payment) {
                $transactions->push([
                    'date' => $payment->payment_date,
                    'type' => 'payment',
                    'reference' => $payment->payment_reference,
                    'description' => 'Payment - '.ucfirst(str_replace('_', ' ', $payment->payment_method)),
                    'debit' => 0.0,
                    'credit' => (float) $payment->amount,
                ]);
            }
        }

        $balance = 0.0;
        $transactions = $transactions->sortBy('date')->values()->map(function (array $transaction) use (&$balance) {
            $balance += ($transaction['debit'] - $transaction['credit']);

            return [
                ...$transaction,
                'date' => $transaction['date']?->format('Y-m-d'),
                'balance' => round($balance, 2),
            ];
        });

        $totalInvoiced = (float) $invoices->sum('total');
        $totalPaid = (float) $payments->sum('amount');

        return Inertia::render('clients/statement', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'postal_code' => $client->postal_code,
                // The old view printed $client->country, which is not a column (always empty).
                'country' => $client->country,
                'classification' => $client->classification,
                'credit_limit' => (float) $client->credit_limit,
                'is_active' => (bool) $client->is_active,
            ],
            'filters' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo],
            'transactions' => $transactions,
            'totalInvoiced' => round($totalInvoiced, 2),
            'totalPaid' => round($totalPaid, 2),
            'totalOutstanding' => round($totalInvoiced - $totalPaid, 2),
        ]);
    }

    /**
     * Validation shared by create and edit.
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'classification' => 'required|in:adhoc,contract',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ];
    }

    /**
     * Map form fields to columns ("region" is stored in "province").
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'company_name' => $validated['company_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'province' => $validated['region'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'classification' => $validated['classification'],
            'tax_number' => $validated['tax_number'] ?? null,
            // Column is NOT NULL default 0; the old form always sent a number.
            'credit_limit' => $validated['credit_limit'] ?? 0,
            'payment_terms_days' => $validated['payment_terms_days'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'],
        ];
    }
}
