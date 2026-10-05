@extends('pdf.layout')

@section('title', 'Profit & Loss Statement')

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
            </div>
        </div>
    </div>

    <div class="document-title">PROFIT & LOSS STATEMENT</div>

    <div style="margin-top: 8px; font-size: 7pt;">
        <strong>Period:</strong> {{ date('d M Y', strtotime($dateFrom)) }} to {{ date('d M Y', strtotime($dateTo)) }}
    </div>
</div>

{{-- Summary --}}
<div class="document-info" style="margin-bottom: 15px;">
    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 33.33%; padding: 5px;">
            <div style="font-size: 6.5pt; color: #6b7280; margin-bottom: 2px;">Total Revenue</div>
            <div style="font-size: 11pt; font-weight: bold; color: #059669;">N${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div style="display: table-cell; width: 33.33%; padding: 5px;">
            <div style="font-size: 6.5pt; color: #6b7280; margin-bottom: 2px;">Total Expenses</div>
            <div style="font-size: 11pt; font-weight: bold; color: #dc2626;">N${{ number_format($totalExpenses, 2) }}</div>
        </div>
        <div style="display: table-cell; width: 33.33%; padding: 5px;">
            <div style="font-size: 6.5pt; color: #6b7280; margin-bottom: 2px;">Net Profit</div>
            <div style="font-size: 11pt; font-weight: bold; color: {{ $netProfit >= 0 ? '#2563eb' : '#ea580c' }};">
                N${{ number_format($netProfit, 2) }}
            </div>
            <div style="font-size: 6pt; color: #6b7280; margin-top: 1px;">Margin: {{ number_format($profitMargin, 1) }}%</div>
        </div>
    </div>
</div>

{{-- Detailed Breakdown --}}
<div style="display: table; width: 100%; margin-top: 15px;">
    {{-- Revenue Column --}}
    <div style="display: table-cell; width: 48%; vertical-align: top;">
        <div style="background-color: #f0fdf4; padding: 8px; margin-bottom: 8px; border-left: 3px solid #059669;">
            <div style="font-size: 10pt; font-weight: bold; color: #059669; margin-bottom: 6px;">REVENUE</div>
            
            <div style="font-size: 7pt; line-height: 1.5;">
                <div style="display: table; width: 100%; margin-bottom: 4px;">
                    <div style="display: table-cell; width: 70%;">
                        <div style="font-weight: bold;">Time-Based Rentals</div>
                        <div style="font-size: 6pt; color: #6b7280;">Day, Hour, Week, Month</div>
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <strong style="color: #059669;">N${{ number_format($revenue['vehicle_rentals'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 4px;">
                    <div style="display: table-cell; width: 70%;">
                        <div style="font-weight: bold;">Distance-Based Services</div>
                        <div style="font-size: 6pt; color: #6b7280;">Trip, Km</div>
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <strong style="color: #059669;">N${{ number_format($revenue['distance_based'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 6px;">
                    <div style="display: table-cell; width: 70%;">
                        <div style="font-weight: bold;">Cargo Services</div>
                        <div style="font-size: 6pt; color: #6b7280;">Tonne, Load, Pallet, etc.</div>
                    </div>
                    <div style="display: table-cell; width: 30%; text-align: right; vertical-align: middle;">
                        <strong style="color: #059669;">N${{ number_format($revenue['cargo_services'], 2) }}</strong>
                    </div>
                </div>

                <div style="border-top: 2px solid #059669; padding-top: 4px; margin-top: 4px;">
                    <div style="display: table; width: 100%;">
                        <div style="display: table-cell; width: 70%; font-weight: bold;">TOTAL REVENUE</div>
                        <div style="display: table-cell; width: 30%; text-align: right;">
                            <strong style="font-size: 8pt; color: #059669;">N${{ number_format($totalRevenue, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div style="display: table-cell; width: 4%;"></div>

    {{-- Expenses Column --}}
    <div style="display: table-cell; width: 48%; vertical-align: top;">
        <div style="background-color: #fef2f2; padding: 8px; margin-bottom: 8px; border-left: 3px solid #dc2626;">
            <div style="font-size: 10pt; font-weight: bold; color: #dc2626; margin-bottom: 6px;">OPERATING EXPENSES</div>
            
            <div style="font-size: 7pt; line-height: 1.5;">
                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Fuel</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['fuel'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Maintenance & Repairs</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['maintenance'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">MDC Payments to RFANAM</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['mdc_payment'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Insurance</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['insurance'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Licenses & Permits</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['licenses'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Driver Wages</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['wages'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 3px;">
                    <div style="display: table-cell; width: 70%;">Tolls</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['tolls'], 2) }}</strong>
                    </div>
                </div>

                <div style="display: table; width: 100%; margin-bottom: 6px;">
                    <div style="display: table-cell; width: 70%;">Other Expenses</div>
                    <div style="display: table-cell; width: 30%; text-align: right;">
                        <strong style="color: #dc2626;">N${{ number_format($expenses['other'], 2) }}</strong>
                    </div>
                </div>

                <div style="border-top: 2px solid #dc2626; padding-top: 4px; margin-top: 4px;">
                    <div style="display: table; width: 100%;">
                        <div style="display: table-cell; width: 70%; font-weight: bold;">TOTAL EXPENSES</div>
                        <div style="display: table-cell; width: 30%; text-align: right;">
                            <strong style="font-size: 8pt; color: #dc2626;">N${{ number_format($totalExpenses, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Final Summary --}}
<div style="margin-top: 15px; background-color: {{ $netProfit >= 0 ? '#eff6ff' : '#fff7ed' }}; padding: 10px; border: 2px solid {{ $netProfit >= 0 ? '#2563eb' : '#ea580c' }};">
    <div style="font-size: 10pt; font-weight: bold; margin-bottom: 6px; text-align: center;">PROFIT SUMMARY</div>
    
    <div style="font-size: 7pt; max-width: 400px; margin: 0 auto;">
        <div style="display: table; width: 100%; margin-bottom: 4px;">
            <div style="display: table-cell; width: 60%;">Gross Profit</div>
            <div style="display: table-cell; width: 40%; text-align: right; font-weight: bold; color: #059669;">
                N${{ number_format($grossProfit, 2) }}
            </div>
        </div>

        <div style="display: table; width: 100%; margin-bottom: 6px;">
            <div style="display: table-cell; width: 60%;">Less: Operating Expenses</div>
            <div style="display: table-cell; width: 40%; text-align: right; font-weight: bold; color: #dc2626;">
                N${{ number_format($totalExpenses, 2) }}
            </div>
        </div>

        <div style="border-top: 2px solid {{ $netProfit >= 0 ? '#2563eb' : '#ea580c' }}; padding-top: 6px;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 60%; font-weight: bold; font-size: 8pt;">NET PROFIT</div>
                <div style="display: table-cell; width: 40%; text-align: right; font-weight: bold; font-size: 9pt; color: {{ $netProfit >= 0 ? '#2563eb' : '#ea580c' }};">
                    N${{ number_format($netProfit, 2) }}
                </div>
            </div>
            <div style="text-align: right; font-size: 6pt; color: #6b7280; margin-top: 2px;">
                Profit Margin: {{ number_format($profitMargin, 1) }}%
            </div>
        </div>
    </div>
</div>

<div class="footer" style="margin-top: 20px;">
    <div style="font-size: 6.5pt; color: #6b7280; text-align: center;">
        <p>Generated on {{ now()->format('d F Y \a\t H:i') }}</p>
        <p style="margin-top: 3px;"><strong>{{ $company->company_name }}</strong> - Professional Logistics Services</p>
    </div>
</div>
@endsection

