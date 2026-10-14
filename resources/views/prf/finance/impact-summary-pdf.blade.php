@extends('prf.reports.pdf-template')

@section('title', 'Impact Summary ' . $impact->periodLabel())

@section('content')
    @php
        $logoPath = public_path('landscape-logo.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : asset('landscape-logo.png');
    @endphp

    <style>
        .impact-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #065f46; padding-bottom: 12px; margin-bottom: 18px; }
        .impact-head img { height: 52px; }
        .impact-head h1 { font-size: 18pt; color: #065f46; text-align: right; }
        .tiles { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 18px; }
        .tile { flex: 1 1 45%; background: #f0fdf4; border-radius: 8px; padding: 14px; }
        .tile .value { font-size: 22pt; font-weight: 700; color: #065f46; }
        .tile .label { color: #374151; }
        table.decisions { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.decisions td, table.decisions th { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        .note { margin-top: 20px; line-height: 1.6; }
    </style>

    <div class="impact-head">
        <img src="{{ $logoSrc }}" alt="{{ $organisation }}">
        <h1>Impact Summary<br><small>{{ $impact->periodLabel() }}</small></h1>
    </div>

    <div class="tiles">
        <div class="tile"><div class="value">{{ number_format($impact->missionsServiced) }}</div><div class="label">Missions served</div></div>
        <div class="tile"><div class="value">{{ number_format($impact->studentsReached) }}</div><div class="label">Students reached</div></div>
        <div class="tile"><div class="value">{{ number_format($impact->souls) }}</div><div class="label">Souls won for Christ</div></div>
        <div class="tile"><div class="value">{{ number_format($impact->inDiscipleship) }}</div><div class="label">Believers in discipleship</div></div>
        <div class="tile"><div class="value">KES {{ number_format($impact->incomeReceived) }}</div><div class="label">Given by our partners</div></div>
    </div>

    @if ($impact->decisions !== [])
        <h2>Decisions made</h2>
        <table class="decisions">
            <tr><th>Decision</th><th>Souls</th></tr>
            @foreach ($impact->decisions as $decision => $souls)
                <tr><td>{{ $decision }}</td><td>{{ number_format($souls) }}</td></tr>
            @endforeach
        </table>
    @endif

    <p class="note">
        Thank you to every member, friend and partner of {{ $organisation }}. Your giving makes this ministry possible.
    </p>
@endsection
