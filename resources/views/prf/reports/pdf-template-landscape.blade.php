<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Report') - {{ config('app.name') }}</title>

    <style>
        :root {
            --prf-navy: #1a1966;
            --prf-lime: #9bd400;
            --prf-navy-soft: #eef0ff;
            --prf-text: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 landscape;
            margin: 10mm 10mm 12mm 10mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 9pt;
            color: var(--prf-text);
            line-height: 1.45;
            background: #ffffff;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 12px;
            margin-bottom: 14px;
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--prf-lime);
            border-radius: 10px;
            background: linear-gradient(135deg, var(--prf-navy-soft) 0%, #ffffff 55%, #f7ffe8 100%);
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .brand-logo {
            height: 42px;
            width: auto;
            object-fit: contain;
        }

        .brand-copy h1 {
            font-size: 18pt;
            color: var(--prf-navy);
            line-height: 1.1;
            letter-spacing: -0.3px;
            margin-bottom: 3px;
        }

        .brand-copy .subtitle {
            color: #334155;
            font-size: 9pt;
        }

        .meta-block {
            text-align: right;
            font-size: 8pt;
            color: #475569;
            min-width: 220px;
        }

        .meta-block .meta-line {
            margin-bottom: 2px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            background: #ffffff;
        }

        .stat-label {
            color: #64748b;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .stat-value {
            color: var(--prf-navy);
            font-size: 14pt;
            font-weight: 700;
            line-height: 1;
        }

        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .schedule-section {
            page-break-inside: auto;
            break-inside: auto;
        }

        .schedule-section thead {
            display: table-header-group;
        }

        .schedule-section tfoot {
            display: table-footer-group;
        }

        .schedule-section tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .section-title {
            font-size: 10pt;
            font-weight: 700;
            color: var(--prf-navy);
            padding: 7px 9px;
            border-radius: 8px;
            border: 1px solid #d2dbff;
            background: linear-gradient(135deg, #edf2ff 0%, #f7ffe8 100%);
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        thead {
            background: var(--prf-navy);
        }

        th {
            color: #f8fafc;
            text-align: left;
            padding: 7px 6px;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        th:last-child {
            border-right: 0;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .muted {
            color: #64748b;
            font-size: 7pt;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 7pt;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .name-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .name-chip {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            background: #ecefff;
            color: var(--prf-navy);
            font-size: 7pt;
            max-width: 210px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .name-chip-offline {
            background: #eff8d9;
            color: #365314;
        }

        .info-note {
            margin-top: 10px;
            padding: 8px 10px;
            border: 1px solid #d6e6a8;
            border-left: 4px solid var(--prf-lime);
            border-radius: 8px;
            background: #f7ffe8;
            font-size: 8pt;
            color: #334155;
        }

        .remaining-pill {
            display: inline-block;
            min-width: 28px;
            text-align: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 8pt;
            font-weight: 700;
        }

        .remaining-ok {
            color: #14532d;
            background: #dcfce7;
        }

        .remaining-full {
            color: #7f1d1d;
            background: #fee2e2;
        }

        .compact-table td,
        .compact-table th {
            padding: 5px 6px;
        }

        .report-footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 7pt;
        }
    </style>
</head>

<body>
    @yield('content')
</body>

</html>
