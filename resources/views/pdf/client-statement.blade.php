@extends('pdf.layout')

@section('title', 'Customer Statement')

@section('content')
<div class="header">
    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; vertical-align: top;">
            @if($company->logo_path)
                @php
                    $logoPath = storage_path('app/public/' . $company->logo_path);
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" style="max-height: 80px; max-width: 250px; margin-bottom: 5px;">
                @endif
            @else
                <div style="font-size: 14pt; font-weight: bold; color: #1f2937;">{{ $company->company_name }}</div>
            @endif
        </div>
        <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;" class="company-info">
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->city){{ $company->city }}@if($company->postal_code), {{ $company->postal_code }}@endif<br>@endif
                @if($company->country){{ $company->country }}<br>@endif
                @if($company->email)Email: {{ $company->email }}<br>@endif
                @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                @if($company->vat_number)<strong>VAT:</strong> {{ $company->vat_number }}<br>@endif
                @if($company->registration_number)<strong>Reg:</strong> {{ $company->registration_number }}@endif
            </div>
        </div>
    </div>

    <div class="document-title">CUSTOMER STATEMENT</div>

    <div style="margin-top: 8px; font-size: 7pt;">
        <strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d F Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d F Y') }}
    </div>
</div>

{{-- Client Information --}}
<div class="document-info">
    <div class="info-row">
        <div class="info-label">Customer:</div>
        <div class="info-value">
            @if($client->company_name)
                <strong>{{ $client->company_name }}</strong><br>
            @endif
            {{ $client->name }}
        </div>
    </div>

    @if($client->address)
    <div class="info-row">
        <div class="info-label">Address:</div>
        <div class="info-value">
            {{ $client->address }}
            @if($client->city), {{ $client->city }}@endif
            @if($client->postal_code), {{ $client->postal_code }}@endif
        </div>
    </div>
    @endif

    @if($client->email)
    <div class="info-row">
        <div class="info-label">Email:</div>
        <div class="info-value">{{ $client->email }}</div>
    </div>
    @endif

    @if($client->phone)
    <div class="info-row">
        <div class="info-label">Phone:</div>
        <div class="info-value">{{ $client->phone }}</div>
    </div>
    @endif
</div>

{{-- Summary --}}
<div class="summary-cards">
    <div class="summary-card blue">
        <div class="label">Total Invoiced</div>
        <div class="value">N${{ number_format($totalInvoiced, 2) }}</div>
    </div>
    <div class="summary-card green">
        <div class="label">Total Paid</div>
        <div class="value">N${{ number_format($totalPaid, 2) }}</div>
    </div>
    <div class="summary-card {{ $totalOutstanding > 0 ? 'red' : 'gray' }}">
        <div class="label">Outstanding</div>
        <div class="value">N${{ number_format($totalOutstanding, 2) }}</div>
    </div>
</div>

{{-- Transactions --}}
@if($transactions->count() > 0)
<table style="margin-top: 20px;">
    <thead>
        <tr>
            <th style="width: 12%;">Date</th>
            <th style="width: 18%;">Reference</th>
            <th style="width: 30%;">Description</th>
            <th style="width: 13%;" class="text-right">Debit</th>
            <th style="width: 13%;" class="text-right">Credit</th>
            <th style="width: 14%;" class="text-right">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $transaction)
            <tr class="{{ $transaction['type'] === 'payment' ? 'bg-green-50' : '' }}">
                <td>{{ $transaction['date']->format('d M Y') }}</td>
                <td style="font-weight: 600;">{{ $transaction['reference'] }}</td>
                <td>{{ $transaction['description'] }}</td>
                <td class="text-right" style="color: {{ $transaction['debit'] > 0 ? '#dc2626' : '#9ca3af' }}; font-weight: {{ $transaction['debit'] > 0 ? '600' : 'normal' }};">
                    {{ $transaction['debit'] > 0 ? 'N$' . number_format($transaction['debit'], 2) : '-' }}
                </td>
                <td class="text-right" style="color: {{ $transaction['credit'] > 0 ? '#059669' : '#9ca3af' }}; font-weight: {{ $transaction['credit'] > 0 ? '600' : 'normal' }};">
                    {{ $transaction['credit'] > 0 ? 'N$' . number_format($transaction['credit'], 2) : '-' }}
                </td>
                <td class="text-right" style="font-weight: bold; color: {{ $transaction['balance'] > 0 ? '#dc2626' : '#1f2937' }};">
                    N${{ number_format($transaction['balance'], 2) }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals-section" style="margin-top: 15px;">
    <div class="totals-row">
        <div class="totals-label">Total Invoiced:</div>
        <div class="totals-value" style="color: #dc2626;">N${{ number_format($totalInvoiced, 2) }}</div>
    </div>
    <div class="totals-row">
        <div class="totals-label">Total Paid:</div>
        <div class="totals-value" style="color: #059669;">N${{ number_format($totalPaid, 2) }}</div>
    </div>
    <div class="totals-row total-final" style="background-color: {{ $totalOutstanding > 0 ? '#fee2e2' : '#f3f4f6' }};">
        <div class="totals-label">AMOUNT DUE:</div>
        <div class="totals-value" style="color: {{ $totalOutstanding > 0 ? '#dc2626' : '#1f2937' }};">N${{ number_format($totalOutstanding, 2) }}</div>
    </div>
</div>
@else
    <div style="text-align: center; padding: 30px; color: #6b7280; font-size: 8pt;">
        No transactions found for the selected period.
    </div>
@endif

{{-- Payment Terms --}}
@if($totalOutstanding > 0)
<div class="terms-section" style="margin-top: 20px;">
    <div class="terms-title">Payment Terms</div>
    <div class="terms-content">
        Payment is due within {{ $client->payment_terms ?? 30 }} days of invoice date. Please reference the invoice number when making payment.
    </div>
</div>
@endif

{{-- Banking Details --}}
@if($company->bank_name || $company->account_number)
<div class="terms-section">
    <div class="terms-title">Banking Details for Payment</div>
    <div style="font-size: 7pt;">
        <table style="width: 100%; border: none; margin: 0;">
            <tr>
                @if($company->bank_name)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>Bank:</strong> {{ $company->bank_name }}</td>
                @endif
                @if($company->bank_branch)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>Branch:</strong> {{ $company->bank_branch }}</td>
                @endif
            </tr>
            <tr>
                @if($company->account_name)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>Account Name:</strong> {{ $company->account_name }}</td>
                @endif
                @if($company->account_number)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>Account Number:</strong> {{ $company->account_number }}</td>
                @endif
            </tr>
            <tr>
                @if($company->branch_code)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>Branch Code:</strong> {{ $company->branch_code }}</td>
                @endif
                @if($company->swift_code)
                <td style="border: none; padding: 2px 0; width: 50%;"><strong>SWIFT Code:</strong> {{ $company->swift_code }}</td>
                @endif
            </tr>
        </table>
    </div>
</div>
@endif

<div class="footer">
    <div style="font-size: 6.5pt; color: #6b7280; text-align: center;">
        <p>This is a computer-generated statement and requires no signature</p>
        <p style="margin-top: 3px;">Statement generated on {{ now()->format('d F Y H:i') }}</p>
    </div>
</div>
@endsection

