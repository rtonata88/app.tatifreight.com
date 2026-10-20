@extends('pdf.layout')

@section('title', 'Fuel Report')

@php
    $num = fn ($value, $decimals = 1) => $value === null ? '—' : number_format($value, $decimals);
    $money = fn ($value) => $value === null ? '—' : 'N$' . number_format($value, 2);
    $over = fn ($value) => $value === null ? '—' : ($value > 0 ? '+' : '') . number_format($value, 1) . '%';
    $th = 'padding: 5px; border: 1px solid #d1d5db; font-weight: bold; text-align: right;';
    $td = 'padding: 5px; border: 1px solid #e5e7eb; text-align: right;';
@endphp

@section('content')
<div class="header">
    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; vertical-align: top;">
            <div style="font-size: 14pt; font-weight: bold; color: #1f2937;">{{ $company->company_name }}</div>
        </div>
        <div style="display: table-cell; width: 50%; vertical-align: top; text-align: right;" class="company-info">
            <div class="company-details">
                @if($company->email)Email: {{ $company->email }}<br>@endif
                @if($company->phone)Tel: {{ $company->phone }}<br>@endif
            </div>
        </div>
    </div>

    <div class="document-title">FUEL REPORT</div>

    <div style="margin-top: 8px; font-size: 7pt;">
        <strong>Period:</strong> {{ date('d M Y', strtotime($dateFrom)) }} to {{ date('d M Y', strtotime($dateTo)) }}
        &nbsp;·&nbsp; Fill-ups counted unless rejected. Distance from the logbook.
    </div>
</div>

<table style="width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 15px;">
    <tr>
        <td style="padding: 6px;"><strong>Litres:</strong> {{ $num($totals['litres'], 2) }} L</td>
        <td style="padding: 6px;"><strong>Spend:</strong> {{ $money($totals['spend']) }}</td>
        <td style="padding: 6px;"><strong>Distance:</strong> {{ $num($totals['distance'], 0) }} km</td>
        <td style="padding: 6px;"><strong>Fleet fuel use:</strong> {{ $num($totals['consumption']) }} L/100 km</td>
        <td style="padding: 6px;"><strong>Open alerts:</strong> {{ $totals['open_alerts'] }}</td>
    </tr>
</table>

@foreach (['vehicles' => ['By vehicle', 'Vehicle', 'Type'], 'drivers' => ['By driver', 'Driver', 'Vehicles']] as $key => [$heading, $whoLabel, $detailLabel])
    <div style="font-size: 9pt; font-weight: bold; margin: 10px 0 5px;">{{ $heading }}</div>
    <table style="width: 100%; border-collapse: collapse; font-size: 7pt; margin-bottom: 12px;">
        <thead>
            <tr style="background-color: #f3f4f6;">
                <th style="{{ $th }} text-align: left;">{{ $whoLabel }}</th>
                <th style="{{ $th }} text-align: left;">{{ $detailLabel }}</th>
                <th style="{{ $th }}">Litres</th>
                <th style="{{ $th }}">Spend</th>
                <th style="{{ $th }}">Distance (km)</th>
                <th style="{{ $th }}">L/100 km</th>
                <th style="{{ $th }}">Expected</th>
                <th style="{{ $th }}">Over expected</th>
                <th style="{{ $th }}">N$ per km</th>
                <th style="{{ $th }}">Alerts (open)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($$key as $row)
                <tr @if(($row['over_percent'] ?? 0) > $tolerance) style="background-color: #fef2f2;" @endif>
                    <td style="{{ $td }} text-align: left; font-weight: 600;">{{ $row['name'] }}</td>
                    <td style="{{ $td }} text-align: left;">{{ $row['detail'] }}</td>
                    <td style="{{ $td }}">{{ $num($row['litres'], 2) }}</td>
                    <td style="{{ $td }}">{{ $money($row['spend']) }}</td>
                    <td style="{{ $td }}">{{ $num($row['distance'], 0) }}</td>
                    <td style="{{ $td }}">{{ $num($row['consumption']) }}</td>
                    <td style="{{ $td }}">{{ $num($row['expected']) }}</td>
                    <td style="{{ $td }} font-weight: 600;">{{ $over($row['over_percent']) }}</td>
                    <td style="{{ $td }}">{{ $money($row['cost_per_km']) }}</td>
                    <td style="{{ $td }}">{{ $row['alerts'] }} ({{ $row['open_alerts'] }})</td>
                </tr>
            @empty
                <tr><td colspan="10" style="{{ $td }} text-align: center;">No fill-ups in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
@endforeach
@endsection
