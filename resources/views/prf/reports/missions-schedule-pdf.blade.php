@extends('prf.reports.pdf-template')

@section('title', $title ?? 'Missions Schedule')

@section('content')
    {{-- Report Header --}}
    <div class="report-header">
        <h1>Missions Schedule</h1>
        <div class="subtitle">{{ $subtitle ?? 'Filtered Missions List' }}</div>
        <div class="meta">
            Generated on {{ now()->format('F d, Y \a\t h:i A') }} |
            Total Missions: {{ $missions->count() }}
        </div>
    </div>

    {{-- Summary Statistics --}}
    @php
        $statusCounts = $missions->groupBy('status')->map->count();
        $totalCapacity = $missions->sum('capacity');
        $totalSubscriptions = $missions->sum(fn($m) => $m->missionSubscriptions->count());
        $uniqueSchools = $missions->pluck('school_id')->unique()->count();
    @endphp
    <div class="stats-grid keep-together">
        <div class="stat-card">
            <div class="stat-value">{{ $missions->count() }}</div>
            <div class="stat-label">Total Missions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $uniqueSchools }}</div>
            <div class="stat-label">Schools</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $totalCapacity }}</div>
            <div class="stat-label">Total Capacity</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $totalSubscriptions }}</div>
            <div class="stat-label">Subscriptions</div>
        </div>
    </div>

    {{-- Missions Grouped by Date --}}
    @php
        $groupedMissions = $missions->groupBy(fn($m) => $m->start_date?->format('Y-m-d') ?? 'No Date');
    @endphp

    @foreach ($groupedMissions as $date => $dateMissions)
        <div class="section avoid-break">
            <h2 class="section-title">
                @if ($date !== 'No Date')
                    {{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}
                @else
                    Unscheduled
                @endif
                <span style="font-weight: normal; font-size: 10pt; float: right;">
                    {{ $dateMissions->count() }} mission{{ $dateMissions->count() !== 1 ? 's' : '' }}
                </span>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">School</th>
                        <th style="width: 12%;">Type</th>
                        <th style="width: 10%;">Time</th>
                        <th style="width: 18%;">Theme</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 13%;">Team</th>
                        <th style="width: 10%;">Term</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dateMissions->sortBy('start_time') as $mission)
                        @php
                            $approvedCount = $mission->missionSubscriptions
                                ->where('status', \App\Enums\PRFMissionSubscriptionStatus::APPROVED->value)
                                ->count();
                            $statusColors = [
                                \App\Enums\PRFMissionStatus::PENDING->value => 'badge-warning',
                                \App\Enums\PRFMissionStatus::APPROVED->value => 'badge-info',
                                \App\Enums\PRFMissionStatus::FULLY_SUBSCRIBED->value => 'badge-success',
                                \App\Enums\PRFMissionStatus::SERVICED->value => 'badge-success',
                                \App\Enums\PRFMissionStatus::CANCELLED->value => 'badge-danger',
                                \App\Enums\PRFMissionStatus::REJECTED->value => 'badge-danger',
                                \App\Enums\PRFMissionStatus::POSTPONED->value => 'badge-warning',
                            ];
                            $statusClass = $statusColors[$mission->status] ?? 'badge-info';
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $mission->school->name ?? 'N/A' }}</strong>
                                @if ($mission->school?->distance)
                                    <br><span style="font-size: 7pt; color: #6b7280;">{{ $mission->school->distance }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $mission->missionType->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @if ($mission->start_time)
                                    {{ \Carbon\Carbon::parse($mission->start_time)->format('g:i A') }}
                                @else
                                    TBD
                                @endif
                            </td>
                            <td style="font-size: 8pt;">{{ Str::limit($mission->theme, 40) ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ \App\Enums\PRFMissionStatus::fromValue($mission->status)->getLabel() }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $fillPercentage = $mission->capacity > 0 ? ($approvedCount / $mission->capacity) * 100 : 0;
                                    $teamClass = match (true) {
                                        $fillPercentage >= 100 => 'badge-success',
                                        $fillPercentage >= 80 => 'badge-warning',
                                        default => 'badge-info',
                                    };
                                @endphp
                                <span class="badge {{ $teamClass }}">
                                    {{ $approvedCount }}/{{ $mission->capacity }}
                                </span>
                            </td>
                            <td style="font-size: 8pt;">{{ $mission->schoolTerm->name ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Status Summary --}}
    @if ($statusCounts->count() > 1)
        <div class="section keep-together">
            <h2 class="section-title">Status Summary</h2>
            <div class="stats-grid" style="grid-template-columns: repeat({{ min($statusCounts->count(), 6) }}, 1fr);">
                @foreach ($statusCounts as $status => $count)
                    @php
                        $statusLabel = \App\Enums\PRFMissionStatus::fromValue($status)->getLabel();
                    @endphp
                    <div class="stat-card">
                        <div class="stat-value">{{ $count }}</div>
                        <div class="stat-label">{{ $statusLabel }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Schools List --}}
    @php
        $schoolsWithMissions = $missions->groupBy('school_id')->map(function ($schoolMissions) {
            return [
                'school' => $schoolMissions->first()->school,
                'count' => $schoolMissions->count(),
                'capacity' => $schoolMissions->sum('capacity'),
            ];
        })->sortByDesc('count');
    @endphp

    @if ($schoolsWithMissions->count() > 1)
        <div class="section avoid-break">
            <h2 class="section-title">Schools Overview</h2>
            <table class="compact-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 45%;">School</th>
                        <th style="width: 25%;">Missions</th>
                        <th style="width: 25%;">Total Capacity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schoolsWithMissions as $index => $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data['school']->name ?? 'Unknown' }}</td>
                            <td>{{ $data['count'] }}</td>
                            <td>{{ $data['capacity'] }} missionaries</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Footer --}}
    <div class="report-footer">
        <div class="confidential">CONFIDENTIAL - FOR INTERNAL USE ONLY</div>
        <div>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
        <div style="margin-top: 5px;">
            Schedule generated at {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
@endsection
