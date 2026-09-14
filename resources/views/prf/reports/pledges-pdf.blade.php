@extends('prf.reports.pdf-template-landscape')

@section('title', $title ?? 'Giving Commitments')

@section('content')
    @php
        $logoPath = public_path('landscape-logo.png');
        $logoDataUri = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;
        $logoSrc = $logoDataUri ?: asset('landscape-logo.png');
    @endphp

    <div class="report-header">
        <div class="brand-block">
            <img class="brand-logo" src="{{ $logoSrc }}" alt="Parkroad Fellowship Logo">
            <div class="brand-copy">
                <h1>{{ $title ?? 'Giving Commitments' }}</h1>
                <div class="subtitle">{{ $subtitle ?? 'Member giving commitments and follow-through.' }}</div>
            </div>
        </div>

        <div class="meta-block">
            <div class="meta-line">Generated {{ now()->format('F d, Y \a\t h:i A') }}</div>
            <div class="meta-line">Total Pledges: {{ $count }}</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Members Committed</div>
            <div class="stat-value">{{ number_format($count) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Projected Annual</div>
            <div class="stat-value">KES {{ number_format($projectedAnnual, 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Avg. Annual / Member</div>
            <div class="stat-value">KES {{ number_format($avgAnnual, 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Fulfilled {{ now()->year }}</div>
            <div class="stat-value">KES {{ number_format($fulfilledThisYear, 2) }}</div>
        </div>
    </div>

    <div class="section schedule-section">
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 18%;">Name</th>
                    <th style="width: 13%;">WhatsApp</th>
                    <th style="width: 12%;">Amount</th>
                    <th style="width: 12%;">Frequency</th>
                    <th style="width: 13%;">Annual Equivalent</th>
                    <th style="width: 10%;">Next Due</th>
                    <th style="width: 9%;">Status</th>
                    <th style="width: 10%;">Submitted</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pledges as $pledge)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $pledge->name }}</strong></td>
                        <td>{{ $pledge->phone ?? '—' }}</td>
                        <td>{{ number_format((float) $pledge->amount, 2) }}</td>
                        <td>{{ $pledge->frequency?->getLabel() ?? '—' }}</td>
                        <td>{{ number_format($pledge->annualizedAmount(), 2) }}</td>
                        <td>{{ $pledge->next_due_on?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $pledge->status?->getLabel() ?? '—' }}</td>
                        <td>{{ $pledge->created_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9"><span class="muted">No commitments yet.</span></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="report-footer">
        <div>CONFIDENTIAL - FOR INTERNAL USE ONLY</div>
        <div>{{ config('app.name') }} | Report generated at {{ now()->format('Y-m-d H:i:s') }} UTC</div>
    </div>
@endsection
