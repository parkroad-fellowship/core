@extends('prf.reports.pdf-template')

@section('title', 'Receipt ' . $entry->receipt_number)

@section('content')
    @php
        $logoPath = public_path('landscape-logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : asset('landscape-logo.png');
    @endphp

    <style>
        .receipt { border: 2px solid #065f46; border-radius: 10px; padding: 22px 26px; }
        .receipt-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #d1d5db; padding-bottom: 12px; margin-bottom: 16px; }
        .receipt-head img { height: 48px; }
        .receipt-title { text-align: right; }
        .receipt-title h1 { font-size: 20pt; color: #065f46; letter-spacing: 2px; }
        .receipt-title .number { font-size: 11pt; font-weight: 600; }
        .receipt-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .receipt-grid td { padding: 7px 4px; border-bottom: 1px dotted #d1d5db; vertical-align: top; }
        .receipt-grid td.label { width: 32%; color: #6b7280; }
        .amount-box { background: #ecfdf5; border-radius: 8px; padding: 12px 16px; margin: 12px 0; }
        .amount-box .figure { font-size: 18pt; font-weight: 700; color: #065f46; }
        .amount-box .words { font-style: italic; color: #374151; }
        .thanks { margin: 14px 0; line-height: 1.6; }
        .impact { margin-top: 18px; border-top: 1px solid #d1d5db; padding-top: 12px; }
        .impact h2 { font-size: 11pt; color: #065f46; margin-bottom: 8px; }
        .impact-grid { display: flex; gap: 10px; }
        .impact-card { flex: 1; background: #f9fafb; border-radius: 6px; padding: 8px; text-align: center; }
        .impact-card .value { font-size: 14pt; font-weight: 700; }
        .impact-card .label { font-size: 8pt; color: #6b7280; }
        .signature { margin-top: 26px; font-size: 9pt; color: #6b7280; }
    </style>

    <div class="receipt">
        <div class="receipt-head">
            <img src="{{ $logoSrc }}" alt="{{ $organisation }}">
            <div class="receipt-title">
                <h1>RECEIPT</h1>
                <div class="number">No. {{ $entry->receipt_number }}</div>
                <div>{{ $entry->transacted_on->format('j F Y') }}</div>
            </div>
        </div>

        <table class="receipt-grid">
            <tr><td class="label">Received with thanks from</td><td><strong>{{ $giver }}</strong></td></tr>
            <tr><td class="label">Designated for</td><td>{{ $entry->ledgerCategory?->name }}</td></tr>
            <tr><td class="label">Means of giving</td><td>{{ $entry->channel?->getLabel() }}@if ($entry->reference) &middot; Ref {{ $entry->reference }}@endif</td></tr>
            @if ($entry->description)
                <tr><td class="label">Particulars</td><td>{{ $entry->description }}</td></tr>
            @endif
        </table>

        <div class="amount-box">
            <div class="figure">KES {{ number_format($entry->amount) }}</div>
            <div class="words">{{ $amountInWords }}</div>
        </div>

        <p class="thanks">
            {{ $organisation }} gratefully acknowledges your generous gift. Every shilling you give helps us take the
            Gospel to schools, disciple young believers and serve our community. Thank you for partnering with us.
        </p>

        <div class="impact">
            <h2>Your giving at work &middot; {{ $impact->periodLabel() }}</h2>
            <div class="impact-grid">
                <div class="impact-card"><div class="value">{{ number_format($impact->missionsServiced) }}</div><div class="label">Missions served</div></div>
                <div class="impact-card"><div class="value">{{ number_format($impact->studentsReached) }}</div><div class="label">Students reached</div></div>
                <div class="impact-card"><div class="value">{{ number_format($impact->souls) }}</div><div class="label">Souls won</div></div>
                <div class="impact-card"><div class="value">{{ number_format($impact->inDiscipleship) }}</div><div class="label">In discipleship</div></div>
            </div>
        </div>

        <div class="signature">
            Issued by the Treasurer, {{ $organisation }}. This receipt was generated electronically and is valid without a signature.
        </div>
    </div>
@endsection
