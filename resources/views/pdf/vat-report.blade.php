@extends('pdf.layout')

@section('title', 'VAT Report')

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
                @if($company->vat_number)<strong>VAT #:</strong> {{ $company->vat_number }}<br>@endif
                @if($company->registration_number)<strong>Reg:</strong> {{ $company->registration_number }}@endif
            </div>
        </div>
    </div>

    <div class="document-title">VAT REPORT</div>

    <div style="margin-top: 8px; font-size: 7pt;">
        <strong>Period:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d F Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d F Y') }}
    </div>
</div>

<div class="summary-cards">
    <div class="summary-card green">
        <div class="label">VAT Output (Collected)</div>
        <div class="value">N${{ number_format($vatOutput['vat_collected'], 2) }}</div>
        <div class="sub-label">Sales: N${{ number_format($vatOutput['total_sales'], 2) }}</div>
    </div>
    <div class="summary-card blue">
        <div class="label">VAT Input (Paid)</div>
        <div class="value">N${{ number_format($vatInput['vat_paid'], 2) }}</div>
        <div class="sub-label">Purchases: N${{ number_format($vatInput['total_purchases'], 2) }}</div>
    </div>
    <div class="summary-card {{ $vatPayable >= 0 ? 'red' : 'orange' }}">
        <div class="label">{{ $vatPayable >= 0 ? 'VAT Payable' : 'VAT Refundable' }}</div>
        <div class="value">N${{ number_format(abs($vatPayable), 2) }}</div>
        <div class="sub-label">{{ $vatPayable >= 0 ? 'Due to Inland Revenue' : 'To be Refunded' }}</div>
    </div>
</div>

{{-- Detailed Calculation --}}
<div style="margin-top: 20px;">
    <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 10px; border-bottom: 2px solid #1f2937; padding-bottom: 5px;">
        VAT Calculation Summary
    </div>

    <table style="width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 15px;">
        <tbody>
            <tr style="background-color: #d1fae5;">
                <td style="padding: 8px; font-weight: bold; color: #065f46;" colspan="2">OUTPUT VAT (VAT on Sales)</td>
            </tr>
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">Sales (Excluding VAT)</td>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">N${{ number_format($vatOutput['total_sales'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">VAT @ 15%</td>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">N${{ number_format($vatOutput['vat_collected'], 2) }}</td>
            </tr>
            <tr style="background-color: #f3f4f6;">
                <td style="padding: 6px; font-weight: bold;">Total Sales (Including VAT)</td>
                <td style="padding: 6px; text-align: right; font-weight: bold;">N${{ number_format($vatOutput['total_with_vat'], 2) }}</td>
            </tr>

            <tr style="height: 10px;"></tr>

            <tr style="background-color: #dbeafe;">
                <td style="padding: 8px; font-weight: bold; color: #1e40af;" colspan="2">INPUT VAT (VAT on Purchases)</td>
            </tr>
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">Purchases (Excluding VAT)</td>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">N${{ number_format($vatInput['total_purchases'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb;">VAT @ 15%</td>
                <td style="padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">N${{ number_format($vatInput['vat_paid'], 2) }}</td>
            </tr>
            <tr style="background-color: #f3f4f6;">
                <td style="padding: 6px; font-weight: bold;">Total Purchases (Including VAT)</td>
                <td style="padding: 6px; text-align: right; font-weight: bold;">N${{ number_format($vatInput['total_with_vat'], 2) }}</td>
            </tr>

            <tr style="height: 10px;"></tr>

            <tr style="background-color: {{ $vatPayable >= 0 ? '#fee2e2' : '#fef3c7' }};">
                <td style="padding: 10px; font-weight: bold; font-size: 8pt; color: #1f2937;">
                    {{ $vatPayable >= 0 ? 'VAT PAYABLE TO INLAND REVENUE' : 'VAT REFUNDABLE FROM INLAND REVENUE' }}
                </td>
                <td style="padding: 10px; text-align: right; font-weight: bold; font-size: 10pt; color: #1f2937;">
                    N${{ number_format(abs($vatPayable), 2) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Monthly Breakdown --}}
@if(count($monthlyBreakdown) > 0)
    <div style="margin-top: 20px; page-break-inside: avoid;">
        <div style="font-size: 10pt; font-weight: bold; color: #1f2937; margin-bottom: 10px; border-bottom: 2px solid #1f2937; padding-bottom: 5px;">
            Monthly VAT Breakdown
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 7pt;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 6px; text-align: left; border: 1px solid #d1d5db; font-weight: bold;">Period</th>
                    <th style="padding: 6px; text-align: right; border: 1px solid #d1d5db; font-weight: bold;">Sales</th>
                    <th style="padding: 6px; text-align: right; border: 1px solid #d1d5db; font-weight: bold;">VAT Output</th>
                    <th style="padding: 6px; text-align: right; border: 1px solid #d1d5db; font-weight: bold;">Purchases</th>
                    <th style="padding: 6px; text-align: right; border: 1px solid #d1d5db; font-weight: bold;">VAT Input</th>
                    <th style="padding: 6px; text-align: right; border: 1px solid #d1d5db; font-weight: bold;">VAT Payable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyBreakdown as $month)
                    <tr>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; font-weight: 600;">{{ $month['period'] }}</td>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; text-align: right;">N${{ number_format($month['sales'], 2) }}</td>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; text-align: right; color: #059669;">N${{ number_format($month['vat_output'], 2) }}</td>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; text-align: right;">N${{ number_format($month['purchases'], 2) }}</td>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; text-align: right; color: #2563eb;">N${{ number_format($month['vat_input'], 2) }}</td>
                        <td style="padding: 5px; border: 1px solid #e5e7eb; text-align: right; font-weight: 600; color: {{ $month['vat_payable'] >= 0 ? '#dc2626' : '#f59e0b' }};">
                            N${{ number_format(abs($month['vat_payable']), 2) }}
                            @if($month['vat_payable'] < 0) (Refund)@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td style="padding: 6px; border: 1px solid #d1d5db;">TOTAL</td>
                    <td style="padding: 6px; border: 1px solid #d1d5db; text-align: right;">N${{ number_format($vatOutput['total_sales'], 2) }}</td>
                    <td style="padding: 6px; border: 1px solid #d1d5db; text-align: right; color: #059669;">N${{ number_format($vatOutput['vat_collected'], 2) }}</td>
                    <td style="padding: 6px; border: 1px solid #d1d5db; text-align: right;">N${{ number_format($vatInput['total_purchases'], 2) }}</td>
                    <td style="padding: 6px; border: 1px solid #d1d5db; text-align: right; color: #2563eb;">N${{ number_format($vatInput['vat_paid'], 2) }}</td>
                    <td style="padding: 6px; border: 1px solid #d1d5db; text-align: right; color: {{ $vatPayable >= 0 ? '#dc2626' : '#f59e0b' }};">
                        N${{ number_format(abs($vatPayable), 2) }}
                        @if($vatPayable < 0) (Refund)@endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif

{{-- Notes --}}
<div style="margin-top: 20px; padding: 10px; background-color: #eff6ff; border-left: 3px solid #3b82f6;">
    <div style="font-size: 7.5pt; font-weight: bold; color: #1e40af; margin-bottom: 5px;">VAT Calculation Notes:</div>
    <div style="font-size: 6.5pt; color: #1e3a8a; line-height: 1.4;">
        • VAT Output is calculated from paid and partially paid invoices<br>
        • VAT Input is calculated from approved expenses (assuming VAT is included in expense amounts)<br>
        • Standard VAT rate of 15% is applied<br>
        • This report should be reviewed by your accountant before submission to Inland Revenue
    </div>
</div>

<div class="footer">
    <div style="font-size: 6.5pt; color: #6b7280; text-align: center;">
        <p>VAT Report generated on {{ now()->format('d F Y H:i') }}</p>
        <p style="margin-top: 3px;">This document is for internal use and should be verified by a qualified accountant before submission</p>
    </div>
</div>
@endsection

