@extends('prf.reports.pdf-template')

@section('title', 'Mission Executive Summaries Report')

@section('content')
    {{-- Cover Page --}}
    <div class="cover-page keep-together">
        <h1>Mission Executive Summaries Report</h1>
        <p class="subtitle">Generated: {{ now()->format('F d, Y') }}</p>
        @if ($dateRange)
            <p class="meta">Period: {{ $dateRange }}</p>
        @endif
        <p class="meta">Total Missions: {{ $missions->count() }}</p>
    </div>

    <div class="page-break"></div>

    {{-- Table of Contents --}}
    <div class="section keep-together">
        <h2 class="section-title">Table of Contents</h2>
        <ul class="toc-list">
            @foreach ($missions as $index => $mission)
                <li class="toc-item">
                    <span class="toc-title">{{ $mission->school?->name ?? 'Unknown School' }}</span>
                    <span class="toc-date">{{ $mission->start_date?->format('M d, Y') }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="page-break"></div>

    {{-- Mission Summaries --}}
    @foreach ($missions as $mission)
        <div class="mission-summary avoid-break">
            {{-- Mission Header --}}
            <div class="mission-header">
                <h2>{{ $mission->school?->name ?? 'Unknown School' }}</h2>
                <div class="mission-meta">
                    <span class="badge badge-{{ $mission->status ? \App\Enums\PRFMissionStatus::from($mission->status)->getColor() : 'gray' }}">
                        {{ $mission->status ? \App\Enums\PRFMissionStatus::from($mission->status)->getLabel() : 'Unknown' }}
                    </span>
                    <span>{{ $mission->missionType?->name ?? 'Unknown Type' }}</span>
                    <span>{{ $mission->start_date?->format('M d, Y') }} - {{ $mission->end_date?->format('M d, Y') }}</span>
                </div>
            </div>

            {{-- Mission Details --}}
            <div class="mission-details">
                <div class="detail-row">
                    <strong>Theme:</strong> {{ $mission->theme ?? 'Not specified' }}
                </div>
                <div class="detail-row">
                    <strong>School Term:</strong> {{ $mission->schoolTerm?->name ?? 'Not specified' }}
                </div>
                <div class="detail-row">
                    <strong>Team Size:</strong> {{ $mission->missionSubscriptions->count() }} missionaries
                </div>
                <div class="detail-row">
                    <strong>Souls Reached:</strong> {{ $mission->souls->count() }}
                </div>
            </div>

            {{-- Executive Summary - Convert Markdown to HTML --}}
            <div class="executive-summary-content">
                <h3>Executive Summary</h3>
                <div class="markdown-content">
                    {!! Illuminate\Support\Str::markdown($mission->executive_summary) !!}
                </div>
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endsection

@section('styles')
    <style>
        .cover-page {
            text-align: center;
            padding-top: 150px;
        }

        .cover-page h1 {
            font-size: 28pt;
            color: #1e40af;
            margin-bottom: 15px;
        }

        .cover-page .subtitle {
            font-size: 14pt;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .cover-page .meta {
            font-size: 12pt;
            color: #9ca3af;
            margin: 5px 0;
        }

        .toc-list {
            list-style: none;
            padding: 0;
        }

        .toc-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dotted #d1d5db;
        }

        .toc-title {
            flex: 1;
            font-weight: 600;
        }

        .toc-date {
            color: #6b7280;
            font-size: 9pt;
        }

        .mission-summary {
            margin-bottom: 30px;
        }

        .mission-header {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            padding: 15px;
            border-left: 4px solid #1e40af;
            margin-bottom: 15px;
        }

        .mission-header h2 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 16pt;
        }

        .mission-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 9pt;
            color: #6b7280;
        }

        .mission-details {
            margin-bottom: 20px;
            padding: 12px;
            background: #f9fafb;
            border-radius: 4px;
        }

        .detail-row {
            margin-bottom: 6px;
            font-size: 10pt;
        }

        .executive-summary-content {
            margin-top: 20px;
        }

        .executive-summary-content h3 {
            border-bottom: 2px solid #1e40af;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-size: 13pt;
            color: #1e40af;
        }

        .markdown-content {
            line-height: 1.6;
        }

        .markdown-content h2 {
            font-size: 12pt;
            margin: 15px 0 10px 0;
            color: #374151;
        }

        .markdown-content h3 {
            font-size: 11pt;
            margin: 12px 0 8px 0;
            color: #4b5563;
        }

        .markdown-content p {
            margin: 8px 0;
        }

        .markdown-content ul,
        .markdown-content ol {
            margin: 8px 0;
            padding-left: 20px;
        }

        .markdown-content li {
            margin: 4px 0;
        }

        .markdown-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
        }

        .markdown-content table th {
            background: #f3f4f6;
            padding: 8px;
            border: 1px solid #d1d5db;
            text-align: left;
        }

        .markdown-content table td {
            padding: 8px;
            border: 1px solid #d1d5db;
        }

        .markdown-content blockquote {
            border-left: 3px solid #1e40af;
            padding-left: 15px;
            margin: 10px 0;
            color: #4b5563;
            font-style: italic;
        }

        .markdown-content strong {
            font-weight: 600;
            color: #111827;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-green {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-gray {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
@endsection
