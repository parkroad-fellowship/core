@extends('prf.reports.pdf-template-landscape')

@section('title', $title ?? 'Missions Schedule')

@section('content')
    @php
        $logoPath = public_path('landscape-logo.png');
        $logoDataUri = file_exists($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;
        $logoSrc = $logoDataUri ?: asset('landscape-logo.png');
        $totalCapacity = $missions->sum('capacity');
        $approvedOnlineTotal = $missions->sum(
            fn ($mission) => $mission->missionSubscriptions
                ->where('status', \App\Enums\PRFMissionSubscriptionStatus::APPROVED->value)
                ->count(),
        );
        $offlineTotal = $missions->sum(fn ($mission) => $mission->offlineMembers->count());
        $totalSubscribers = $approvedOnlineTotal + $offlineTotal;
        $totalOpenSlots = $missions->sum(function ($mission) {
            $approvedOnlineCount = $mission->missionSubscriptions
                ->where('status', \App\Enums\PRFMissionSubscriptionStatus::APPROVED->value)
                ->count();
            $offlineCount = $mission->offlineMembers->count();

            return max(((int) ($mission->capacity ?? 0)) - ($approvedOnlineCount + $offlineCount), 0);
        });
        $uniqueSchools = $missions->pluck('school_id')->unique()->count();
        $sortedMissions = $missions->sortBy(function ($mission) {
            $date = $mission->start_date?->format('Ymd') ?? '99999999';
            $time = $mission->start_time ?? '99:99';
            $school = $mission->school?->name ?? 'ZZZ';

            return "{$date}|{$time}|{$school}";
        })->values();
    @endphp

    <div class="report-header">
        <div class="brand-block">
            <img class="brand-logo" src="{{ $logoSrc }}" alt="Parkroad Fellowship Logo">
            <div class="brand-copy">
                <h1>{{ $title ?? 'Missions Schedule' }}</h1>
                <div class="subtitle">{{ $subtitle ?? 'Approved missions schedule with subscriber lists.' }}</div>
            </div>
        </div>

        <div class="meta-block">
            <div class="meta-line">Generated {{ now()->format('F d, Y \a\t h:i A') }}</div>
            <div class="meta-line">Total Missions: {{ $missions->count() }}</div>
            <div class="meta-line">Schools: {{ $uniqueSchools }}</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Missions</div>
            <div class="stat-value">{{ $missions->count() }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Capacity</div>
            <div class="stat-value">{{ $totalCapacity }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Subscribers</div>
            <div class="stat-value">{{ $totalSubscribers }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Open Slots</div>
            <div class="stat-value">{{ $totalOpenSlots }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Schools</div>
            <div class="stat-value">{{ $uniqueSchools }}</div>
        </div>
    </div>

    <div class="section schedule-section">
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 11%;">Date Range</th>
                    <th style="width: 12%;">School</th>
                    <th style="width: 7%;">Type</th>
                    <th style="width: 8%;">Time</th>
                    <th style="width: 14%;">Theme</th>
                    <th style="width: 34%;">Subscribers</th>
                    <th style="width: 9%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sortedMissions as $mission)
                    @php
                        $approvedOnlineNames = $mission->missionSubscriptions
                            ->where('status', \App\Enums\PRFMissionSubscriptionStatus::APPROVED->value)
                            ->map(function ($subscription) {
                                $member = $subscription->member;

                                if (! $member) {
                                    return null;
                                }

                                return $member->full_name ?: trim("{$member->first_name} {$member->last_name}");
                            })
                            ->filter()
                            ->values();
                        $offlineNames = $mission->offlineMembers->pluck('name')->filter()->values();
                        $subscribersCount = $approvedOnlineNames->count() + $offlineNames->count();
                        $neededCount = (int) ($mission->capacity ?? 0);
                        $slotsToFill = max($neededCount - $subscribersCount, 0);
                        if ($mission->status === \App\Enums\PRFMissionStatus::FULLY_SUBSCRIBED->value) {
                            $statusLabel = 'Fully subscribed';
                        } else {
                            $statusLabel = $slotsToFill > 0 ? "{$slotsToFill} needed" : 'Fully subscribed';
                        }

                        $hasDateRange = filled($mission->start_date) &&
                            filled($mission->end_date) &&
                            $mission->start_date->ne($mission->end_date);

                        $timeLabel = 'TBD';

                        $startTimeValue = $mission->start_time;
                        $endTimeValue = $mission->end_time;
                        $userTimezone = auth()->user()?->timezone ?? config('app.timezone', 'UTC');

                        $formatMissionTime = static function (?string $timeValue, $missionDate) use ($userTimezone): ?string {
                            if (! $timeValue) {
                                return null;
                            }

                            $referenceDate = $missionDate?->format('Y-m-d') ?? now('UTC')->format('Y-m-d');
                            $utcDateTime = \Carbon\Carbon::parse("{$referenceDate} {$timeValue}", 'UTC');

                            return $utcDateTime->setTimezone($userTimezone)->format('g:i A');
                        };

                        $localizedStartTime = $formatMissionTime($startTimeValue, $mission->start_date);
                        $localizedEndTime = $formatMissionTime($endTimeValue, $mission->end_date ?? $mission->start_date);

                        if ($localizedStartTime && $localizedEndTime) {
                            $timeLabel = $localizedStartTime.
                                ' - '.
                                $localizedEndTime;
                        } elseif ($localizedStartTime) {
                            $timeLabel = $localizedStartTime;
                        }
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if ($hasDateRange)
                                {{ $mission->start_date->format('d M Y') }}<br>
                                <span class="muted">to {{ $mission->end_date?->format('d M Y') }}</span>
                            @elseif($mission->start_date)
                                {{ $mission->start_date->format('d M Y') }}
                            @else
                                Unscheduled
                            @endif
                        </td>
                        <td>
                            <strong>{{ $mission->school->name ?? 'N/A' }}</strong>
                            @if ($mission->school?->distance)
                                <div class="muted">{{ $mission->school->distance }}</div>
                            @endif
                        </td>
                        <td>{{ $mission->missionType->name ?? 'N/A' }}</td>
                        <td>{{ $timeLabel }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($mission->theme, 70) ?: 'N/A' }}</td>
                        <td>
                            <div class="name-chips">
                                @forelse ($approvedOnlineNames as $name)
                                    <span class="name-chip">{{ $name }}</span>
                                @empty
                                @endforelse

                                @foreach ($offlineNames as $offlineName)
                                    <span class="name-chip name-chip-offline">{{ $offlineName }} (Offline)</span>
                                @endforeach

                                @if ($approvedOnlineNames->isEmpty() && $offlineNames->isEmpty())
                                    <span class="muted">No subscribers yet</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $statusLabel }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="info-note">
        NB: Seamless subscription for missions available through the PRF Missions App (Register for an account via https://forms.gle/W3aunvPsk8x3QHUN7 )
    </div>

    <div class="report-footer">
        <div>CONFIDENTIAL - FOR INTERNAL USE ONLY</div>
        <div>{{ config('app.name') }} | Schedule generated at {{ now()->format('Y-m-d H:i:s') }} UTC</div>
    </div>
@endsection
