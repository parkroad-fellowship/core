<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Report') - {{ config('app.name') }}</title>

    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 15mm 12mm 20mm 12mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1f2937;
            background: #ffffff;
            /* background-image: url('{{ public_path('PDF_background_.png') }}'); */
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
        }

        /* Content wrapper for padding against background
        .content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 25px;
            border-radius: 8px;
            min-height: 100%;
        } */

        /* Page Break Controls */
        .page-break {
            page-break-before: always;
        }

        .avoid-break {
            page-break-inside: avoid;
        }

        .keep-together {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Header Styles */
        .report-header {
            text-align: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 3px solid #1e40af;
        }

        .report-header h1 {
            font-size: 22pt;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .report-header .subtitle {
            font-size: 11pt;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .report-header .meta {
            font-size: 9pt;
            color: #9ca3af;
        }

        /* Section Styles */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13pt;
            font-weight: 700;
            color: #1e40af;
            padding: 8px 12px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #1e40af;
            margin-bottom: 12px;
            border-radius: 0 4px 4px 0;
        }

        .subsection-title {
            font-size: 11pt;
            font-weight: 600;
            color: #374151;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 10px;
        }

        /* Executive Summary */
        .executive-summary {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .executive-summary h2 {
            font-size: 12pt;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
        }

        .executive-summary p {
            font-size: 10pt;
            color: #78350f;
            font-style: italic;
            line-height: 1.6;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 20px;
        }

        .info-item {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px dotted #e5e7eb;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            min-width: 120px;
            font-size: 9pt;
        }

        .info-value {
            color: #1f2937;
            font-size: 9pt;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 10px;
        }

        thead {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        }

        th {
            color: #ffffff;
            font-weight: 600;
            padding: 10px 8px;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tr:hover {
            background-color: #f3f4f6;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .stat-card .stat-value {
            font-size: 20pt;
            font-weight: 700;
            color: #1e40af;
        }

        .stat-card .stat-label {
            font-size: 8pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Notes and Quotes */
        .note-box {
            background: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 0 4px 4px 0;
        }

        .quote-box {
            background: #faf5ff;
            border-left: 4px solid #a855f7;
            padding: 10px 15px;
            margin: 10px 0;
            font-style: italic;
            border-radius: 0 4px 4px 0;
        }

        /* Footer */
        .report-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
        }

        .report-footer .confidential {
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 5px;
        }

        /* Weather specific */
        .weather-card {
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
            border: 1px solid #06b6d4;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 8px;
        }

        .weather-date {
            font-weight: 600;
            color: #0e7490;
            margin-bottom: 5px;
        }

        /* Two column layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Compact table for souls */
        .compact-table td,
        .compact-table th {
            padding: 4px 6px;
            font-size: 8pt;
        }

        /* Financial section */
        .financial-summary {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed #bbf7d0;
        }

        .financial-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 11pt;
            padding-top: 10px;
            margin-top: 5px;
            border-top: 2px solid #22c55e;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #9ca3af;
            font-style: italic;
        }

        /* Print optimizations */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .page-break {
                page-break-before: always;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>
    <div class="content-wrapper">
        @yield('content')
    </div>
</body>

</html>
