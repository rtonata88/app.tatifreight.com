<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation - {{ $quote->quote_number }}</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #333;
            line-height: 1.3;
        }
        .header-box {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 10px;
        }
        .header-content {
            display: table;
            width: 100%;
        }
        .logo-section {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }
        .logo-section img {
            max-height: 45px;
            max-width: 250px;
        }
        .title-section {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }
        .title-section h1 {
            font-size: 20pt;
            color: #D4A849;
            margin: 0;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 20px;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #D4A849;
            margin-bottom: 4px;
        }
        .section-content {
            font-size: 7.5pt;
            line-height: 1.4;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 7.5pt;
        }
        .items-table thead th {
            background-color: #f5f5f5;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 3px;
            text-align: left;
            font-weight: bold;
        }
        .items-table tbody td {
            padding: 4px 3px;
            border-bottom: 1px solid #ddd;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .total-row {
            margin-top: 8px;
            margin-bottom: 8px;
            float: right;
            font-size: 9pt;
            font-weight: bold;
        }
        .validity-notice {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 5px;
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 7pt;
            text-align: center;
            clear: both;
        }
        .payment-terms {
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 7pt;
            line-height: 1.4;
        }
        .signature-section {
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .signature-box {
            display: inline-block;
            width: 200px;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }
        .signature-line img {
            max-height: 35px;
            max-width: 180px;
            display: block;
        }
        .signature-label {
            font-size: 7pt;
            color: #666;
        }
        .footer-section {
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 6.5pt;
        }
        .footer-columns {
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding-right: 10px;
        }
        .footer-col h4 {
            font-size: 7pt;
            font-weight: bold;
            margin: 0 0 3px 0;
        }
    </style>
</head>
<body>
    {{-- Header Box with Logo and Title --}}
    <div class="header-box">
        <div class="header-content">
            <div class="logo-section">
                @if($company->logo_path)
                    @php
                        $logoPath = storage_path('app/public/' . $company->logo_path);
                    @endphp
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo">
                    @else
                        <div style="font-size: 12pt; font-weight: bold;">{{ $company->company_name }}</div>
                    @endif
                @else
                    <div style="font-size: 12pt; font-weight: bold;">{{ $company->company_name }}</div>
                @endif
            </div>
            <div class="title-section">
                <h1>QUOTATION</h1>
            </div>
        </div>
    </div>

    {{-- Company Info and Reference in Two Columns --}}
    <div class="info-section">
        <div class="info-left">
            <div class="section-title">Company Information</div>
            <div class="section-content">
                <strong>{{ $company->company_name }}</strong><br>
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->city){{ $company->city }}@if($company->postal_code), {{ $company->postal_code }}@endif<br>@endif
                @if($company->vat_number)VAT: {{ $company->vat_number }}@endif
            </div>

            <div class="section-title" style="margin-top: 8px;">Quote For:</div>
            <div class="section-content">
                @if($quote->client->company_name)
                    <strong>{{ $quote->client->company_name }}</strong><br>
                @endif
                @if($quote->client->address){{ $quote->client->address }}<br>@endif
                @if($quote->client->city)
                    {{ $quote->client->city }}@if($quote->client->postal_code), {{ $quote->client->postal_code }}@endif<br>
                @endif
                @if($quote->client->email)Email: {{ $quote->client->email }}@endif
            </div>
        </div>

        <div class="info-right">
            <div class="section-title">Reference</div>
            <table style="width: 100%; font-size: 7.5pt;">
                <tr>
                    <td style="padding: 2px 0;"><strong>Date of issue:</strong></td>
                    <td style="padding: 2px 0;">{{ $quote->created_at->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;"><strong>Quote No.:</strong></td>
                    <td style="padding: 2px 0;">{{ $quote->quote_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px 0;"><strong>Valid Until:</strong></td>
                    <td style="padding: 2px 0;">{{ $quote->valid_until->format('d.m.Y') }}</td>
                </tr>
            </table>

            @if($quote->description)
                <div class="section-title" style="margin-top: 8px;">Additional Information</div>
                <div class="section-content">{{ $quote->description }}</div>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Description</th>
                <th style="width: 8%;">Unit</th>
                <th style="width: 6%;" class="text-center">Qty</th>
                <th style="width: 12%;" class="text-right">Rate</th>
                <th style="width: 12%;" class="text-right">Amount Excl.</th>
                <th style="width: 10%;" class="text-right">VAT</th>
                <th style="width: 12%;" class="text-right">Amount Incl.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->lineItems as $item)
                <tr>
                    <td>
                        {{ $item->description }}
                        @if($item->vehicle)
                            <br><span style="font-size: 6.5pt; color: #666;">{{ $item->vehicle->reg_number }}</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($item->unit) }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount * 0.15, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount * 1.15, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total --}}
    <div class="total-row">
        <span style="margin-right: 20px;">TOTAL</span>
        <span>{{ number_format($quote->total, 2) }}</span>
    </div>

    {{-- Validity Notice --}}
    <div class="validity-notice">
        <strong>This quotation is valid until {{ $quote->valid_until->format('d F Y') }}</strong>
    </div>

    {{-- Payment Terms and Signature --}}
    <div style="margin-top: 8px;">
        <div class="payment-terms">
            <strong>Payment Terms</strong><br>
            *Payments are strictly EFT, unless otherwise arranged<br>
            *Please indicate Quote number as reference<br>
            *All prices are in Namibian Dollars (NAD) | *Prices are subject to change
        </div>

        @if($company->signature_path)
            <div class="signature-section" style="text-align: right; margin-top: 10px;">
                <div class="signature-box">
                    <div class="signature-line">
                        @php
                            $signaturePath = storage_path('app/public/' . $company->signature_path);
                        @endphp
                        @if(file_exists($signaturePath))
                            <img src="{{ $signaturePath }}" style="margin-left: auto;">
                        @endif
                    </div>
                    <div class="signature-label">
                        <strong>Authorized Signature</strong><br>
                        {{ $company->company_name }}
                    </div>
                </div>
            </div>
        @endif

        <div style="font-weight: bold; font-size: 8pt; margin-top: 10px; text-align: center;">
            Thank You For Your Business!
        </div>
    </div>

    {{-- Footer with Contact Information --}}
    <div class="footer-section">
        <div class="footer-columns">
            <div class="footer-col">
                <h4>Registered Address</h4>
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->city){{ $company->city }}<br>{{ $company->country ?? 'Namibia' }}<br>@endif
                @if($company->registration_number){{ $company->registration_number }}@endif
            </div>
            <div class="footer-col">
                <h4>Contact Information</h4>
                Manager<br>
                @if($company->phone)Cell: {{ $company->phone }}<br>@endif
                @if($company->secondary_phone)Cell: {{ $company->secondary_phone }}<br>@endif
                @if($company->email)Email: {{ $company->email }}@endif
            </div>
            <div class="footer-col">
                <h4>Payment Details</h4>
                @php
                    $bankAccount = $quote->companyBankAccount ?? \App\Models\CompanyBankAccount::primary();
                @endphp
                @if($bankAccount)
                    Account Name: {{ $bankAccount->account_name }}<br>
                    Bank Name: {{ $bankAccount->bank_name }}<br>
                    Account No.: {{ $bankAccount->account_number }}<br>
                    @if($bankAccount->branch_name || $bankAccount->branch_code)
                        Branch: {{ $bankAccount->branch_name ?? '' }}@if($bankAccount->branch_code), {{ $bankAccount->branch_code }}@endif<br>
                    @endif
                    @if($bankAccount->swift_code)
                        SWIFT: {{ $bankAccount->swift_code }}
                    @endif
                @else
                    {{-- Fallback to legacy company settings --}}
                    @if($company->account_name)Account Name: {{ $company->account_name }}<br>@endif
                    @if($company->bank_name)Bank Name: {{ $company->bank_name }}<br>@endif
                    @if($company->account_number)Account No.: {{ $company->account_number }}<br>@endif
                    @if($company->bank_branch || $company->branch_code)
                        Branch: {{ $company->bank_branch ?? '' }}@if($company->branch_code), {{ $company->branch_code }}@endif
                    @endif
                @endif
            </div>
        </div>
    </div>
</body>
</html>

