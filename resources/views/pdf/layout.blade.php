<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Document')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #1f2937;
            line-height: 1.3;
        }

        .container {
            padding: 15px;
        }

        .header {
            margin-bottom: 15px;
            border-bottom: 1.5px solid #6b7280;
            padding-bottom: 10px;
        }

        .company-info {
            text-align: right;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 7pt;
            color: #6b7280;
            line-height: 1.2;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            color: #374151;
            margin-top: 12px;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .document-info {
            background-color: #f9fafb;
            padding: 8px 10px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 25%;
            color: #4b5563;
            font-size: 7pt;
        }

        .info-value {
            display: table-cell;
            width: 75%;
            font-size: 7pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table th {
            background-color: #4b5563;
            color: white;
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 7pt;
        }

        table td {
            padding: 4px 6px;
            border-bottom: 0.5px solid #e5e7eb;
            font-size: 7pt;
        }

        table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 10px;
            float: right;
            width: 35%;
        }

        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .totals-label {
            display: table-cell;
            text-align: right;
            padding-right: 12px;
            font-weight: bold;
            color: #4b5563;
            font-size: 7pt;
        }

        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            font-size: 7pt;
        }

        .total-final {
            border-top: 1.5px solid #4b5563;
            padding-top: 5px;
            margin-top: 5px;
            font-size: 9pt;
            color: #374151;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 0.5px solid #e5e7eb;
            clear: both;
        }

        .terms-section {
            margin-top: 15px;
            clear: both;
        }

        .terms-title {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 5px;
            color: #374151;
        }

        .terms-content {
            font-size: 6.5pt;
            color: #4b5563;
            white-space: pre-line;
            line-height: 1.4;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
        }

        .badge-draft {
            background-color: #f3f4f6;
            color: #4b5563;
        }

        .badge-sent {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-expired {
            background-color: #fed7aa;
            color: #9a3412;
        }

        .page-break {
            page-break-after: always;
        }

        @page {
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
