<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {

    public Client $client;
    public $dateFrom;
    public $dateTo;

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function with(): array
    {
        // Get all invoices for this client
        $invoices = Invoice::with(['payments', 'lineItems'])
            ->where('client_id', $this->client->id)
            ->whereBetween('invoice_date', [$this->dateFrom, $this->dateTo])
            ->orderBy('invoice_date', 'asc')
            ->get();

        // Get all payments for this client
        $payments = Payment::where('client_id', $this->client->id)
            ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
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
                'invoice' => $invoice,
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
                    'payment' => $payment,
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

        return [
            'transactions' => $transactions,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
        ];
    }

    public function exportPdf()
    {
        return redirect()->route('clients.statement.pdf', [
            'client' => $this->client,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);
    }
}; ?>

<div>
    <flux:header>
        <div>
            <flux:heading size="xl">Customer Statement</flux:heading>
            <flux:subheading>{{ $client->company_name ?: $client->name }}</flux:subheading>
        </div>

        <div class="flex gap-3">
            <flux:button wire:navigate href="{{ route('clients.index') }}" variant="ghost" icon="arrow-left">
                Back to Clients
            </flux:button>
            <flux:button wire:click="exportPdf" icon="arrow-down-tray" variant="primary">
                Export to PDF
            </flux:button>
        </div>
    </flux:header>

    {{-- Client Information --}}
    <flux:card class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Client Information</h3>
                <div class="text-sm space-y-1">
                    @if($client->company_name)
                        <div><strong>Company:</strong> {{ $client->company_name }}</div>
                    @endif
                    <div><strong>Contact:</strong> {{ $client->name }}</div>
                    @if($client->email)
                        <div><strong>Email:</strong> {{ $client->email }}</div>
                    @endif
                    @if($client->phone)
                        <div><strong>Phone:</strong> {{ $client->phone }}</div>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Address</h3>
                <div class="text-sm space-y-1">
                    @if($client->address)
                        <div>{{ $client->address }}</div>
                    @endif
                    @if($client->city || $client->postal_code)
                        <div>{{ $client->city }}@if($client->postal_code), {{ $client->postal_code }}@endif</div>
                    @endif
                    @if($client->country)
                        <div>{{ $client->country }}</div>
                    @endif
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Account Details</h3>
                <div class="text-sm space-y-1">
                    <div><strong>Type:</strong> {{ ucfirst($client->classification) }}</div>
                    <div><strong>Credit Limit:</strong> N${{ number_format($client->credit_limit, 2) }}</div>
                    <div><strong>Status:</strong> 
                        <flux:badge :color="$client->is_active ? 'green' : 'red'" size="sm">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- Date Filter --}}
    <flux:card class="mt-6">
        <div class="flex flex-wrap items-end gap-4">
            <flux:field>
                <flux:label>Date From</flux:label>
                <flux:input type="date" wire:model.live="dateFrom" />
            </flux:field>

            <flux:field>
                <flux:label>Date To</flux:label>
                <flux:input type="date" wire:model.live="dateTo" />
            </flux:field>
        </div>
    </flux:card>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:card class="bg-blue-50">
            <div class="text-sm text-gray-600 mb-1">Total Invoiced</div>
            <div class="text-3xl font-bold text-blue-700">N${{ number_format($totalInvoiced, 2) }}</div>
        </flux:card>

        <flux:card class="bg-green-50">
            <div class="text-sm text-gray-600 mb-1">Total Paid</div>
            <div class="text-3xl font-bold text-green-700">N${{ number_format($totalPaid, 2) }}</div>
        </flux:card>

        <flux:card class="{{ $totalOutstanding > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
            <div class="text-sm text-gray-600 mb-1">Outstanding Balance</div>
            <div class="text-3xl font-bold {{ $totalOutstanding > 0 ? 'text-red-700' : 'text-gray-700' }}">
                N${{ number_format($totalOutstanding, 2) }}
            </div>
        </flux:card>
    </div>

    {{-- Transactions Table --}}
    <flux:card class="mt-6">
        <flux:heading size="lg" class="mb-4">Transaction History</flux:heading>

        @if($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Credit</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                            <tr class="{{ $transaction['type'] === 'payment' ? 'bg-green-50' : '' }}">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $transaction['date']->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $transaction['reference'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $transaction['description'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right {{ $transaction['debit'] > 0 ? 'font-semibold text-red-600' : 'text-gray-400' }}">
                                    {{ $transaction['debit'] > 0 ? 'N$' . number_format($transaction['debit'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right {{ $transaction['credit'] > 0 ? 'font-semibold text-green-600' : 'text-gray-400' }}">
                                    {{ $transaction['credit'] > 0 ? 'N$' . number_format($transaction['credit'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-bold {{ $transaction['balance'] > 0 ? 'text-red-700' : 'text-gray-900' }}">
                                    N${{ number_format($transaction['balance'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-900">TOTAL</td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-red-600">N${{ number_format($totalInvoiced, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-green-600">N${{ number_format($totalPaid, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-bold {{ $totalOutstanding > 0 ? 'text-red-700' : 'text-gray-900' }}">
                                N${{ number_format($totalOutstanding, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center text-gray-500 py-8">
                No transactions found for the selected period.
            </div>
        @endif
    </flux:card>
</div>

