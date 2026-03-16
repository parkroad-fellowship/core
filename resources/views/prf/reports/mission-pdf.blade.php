@extends('prf.reports.pdf-template')

@section('title', 'Mission Report - ' . ($mission->school->name ?? 'Unknown'))

@section('content')
    {{-- Report Header --}}
    <div class="report-header">
        <h1>Mission Report</h1>
        <div class="subtitle">{{ $mission->school->name ?? 'Mission Details' }}</div>
        <div class="meta">
            Generated on {{ now()->format('F d, Y \a\t h:i A') }} |
            Reference: {{ $mission->ulid }}
        </div>
    </div>

    {{-- Executive Summary --}}
    @if ($mission->executive_summary)
        <div class="executive-summary keep-together">
            <h2>Executive Summary</h2>
            <p>{{ $mission->executive_summary }}</p>
        </div>
    @endif

    {{-- Key Statistics --}}
    <div class="stats-grid keep-together">
        <div class="stat-card">
            <div class="stat-value">{{ $mission->missionSubscriptions->count() }}</div>
            <div class="stat-label">Missionaries</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $mission->souls->count() }}</div>
            <div class="stat-label">Souls Reached</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $mission->missionSessions->count() }}</div>
            <div class="stat-label">Sessions</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">{{ $mission->capacity ?? 0 }}</div>
            <div class="stat-label">Capacity</div>
        </div>
    </div>

    {{-- Mission Details Section --}}
    <div class="section keep-together">
        <h2 class="section-title">Mission Details</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Theme:</span>
                <span class="info-value">{{ $mission->theme ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    @php
                        $statusColors = [
                            'Pending' => 'badge-warning',
                            'Approved' => 'badge-info',
                            'Serviced' => 'badge-success',
                            'Cancelled' => 'badge-danger',
                            'Rejected' => 'badge-danger',
                        ];
                        $statusClass = $statusColors[$mission->status_label] ?? 'badge-info';
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $mission->status_label }}</span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Mission Type:</span>
                <span class="info-value">{{ $mission->missionType->name ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">School Term:</span>
                <span class="info-value">
                    {{ $mission->schoolTerm->name ?? 'N/A' }}
                    @if ($mission->schoolTerm?->year)
                        ({{ $mission->schoolTerm->year }})
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Start Date:</span>
                <span class="info-value">{{ $mission->start_date?->format('l, F d, Y') ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">End Date:</span>
                <span class="info-value">{{ $mission->end_date?->format('l, F d, Y') ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Start Time:</span>
                <span class="info-value">{{ $mission->start_time ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">End Time:</span>
                <span class="info-value">{{ $mission->end_time ?? 'N/A' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Team Capacity:</span>
                <span class="info-value">{{ $mission->capacity ?? 'N/A' }} missionaries</span>
            </div>
            <div class="info-item">
                <span class="info-label">Shortage:</span>
                <span class="info-value">
                    @if ($mission->mission_subscriptions_needed > 0)
                        <span class="badge badge-warning">{{ $mission->mission_subscriptions_needed }} needed</span>
                    @else
                        <span class="badge badge-success">Fully staffed</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Preparation Notes --}}
    @if ($mission->mission_prep_notes)
        <div class="section keep-together">
            <h2 class="section-title">Preparation Notes</h2>
            <div class="note-box">
                {{ $mission->mission_prep_notes }}
            </div>
        </div>
    @endif

    {{-- School Information --}}
    @if ($mission->school)
        <div class="section keep-together">
            <h2 class="section-title">School Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">School Name:</span>
                    <span class="info-value">{{ $mission->school->name ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $mission->school->address ?? 'N/A' }}</span>
                </div>
                @if ($mission->school->student_count)
                    <div class="info-item">
                        <span class="info-label">Student Count:</span>
                        <span class="info-value">{{ number_format($mission->school->student_count) }}</span>
                    </div>
                @endif
                @if ($mission->school->distance_from_nairobi)
                    <div class="info-item">
                        <span class="info-label">Distance:</span>
                        <span class="info-value">{{ $mission->school->distance_from_nairobi }} km from Nairobi</span>
                    </div>
                @endif
            </div>

            {{-- School Contacts --}}
            @if ($mission->school->schoolContacts && $mission->school->schoolContacts->count() > 0)
                <h3 class="subsection-title">School Contacts</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Name</th>
                            <th style="width: 20%;">Role</th>
                            <th style="width: 30%;">Email</th>
                            <th style="width: 25%;">Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->school->schoolContacts as $contact)
                            <tr>
                                <td>{{ $contact->name ?? 'N/A' }}</td>
                                <td>{{ $contact->contactType->name ?? 'N/A' }}</td>
                                <td>{{ $contact->email ?? 'N/A' }}</td>
                                <td>{{ $contact->phone ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- Mission Team --}}
    @if ($mission->missionSubscriptions && $mission->missionSubscriptions->count() > 0)
        <div class="section avoid-break">
            <h2 class="section-title">Mission Team ({{ $mission->missionSubscriptions->count() }} Members)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">Name</th>
                        <th style="width: 25%;">Role</th>
                        <th style="width: 20%;">Status</th>
                        <th style="width: 15%;">Phone</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mission->missionSubscriptions as $index => $subscription)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $subscription->member->full_name ?? 'N/A' }}</td>
                            <td>
                                @php
                                    $roleColors = [
                                        'Mission Leader' => 'badge-danger',
                                        'Assistant Leader' => 'badge-warning',
                                        'Member' => 'badge-info',
                                    ];
                                    $roleClass = $roleColors[$subscription->mission_role_label] ?? 'badge-info';
                                @endphp
                                <span class="badge {{ $roleClass }}">{{ $subscription->mission_role_label ?? 'N/A' }}</span>
                            </td>
                            <td>
                                @php
                                    $subStatusColors = [
                                        'Approved' => 'badge-success',
                                        'Pending' => 'badge-warning',
                                        'Rejected' => 'badge-danger',
                                    ];
                                    $subStatusClass = $subStatusColors[$subscription->status_label] ?? 'badge-info';
                                @endphp
                                <span class="badge {{ $subStatusClass }}">{{ $subscription->status_label ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $subscription->member->phone_number ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Offline Members --}}
    @if ($mission->offlineMembers->isNotEmpty())
        <div class="section keep-together">
            <h2 class="section-title">Additional Team Members (Offline)</h2>
            <div class="info-grid">
                @foreach ($mission->offlineMembers as $member)
                    <div class="info-item">
                        <span class="info-label">Member:</span>
                        <span class="info-value">{{ $member->name }}{{ $member->phone ? " ({$member->phone})" : '' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Page Break Before Outcomes --}}
    @if ($mission->souls->count() > 0 || $mission->missionSessions->count() > 0)
        <div class="page-break"></div>
    @endif

    {{-- Mission Sessions --}}
    @if ($mission->missionSessions && $mission->missionSessions->count() > 0)
        <div class="section avoid-break">
            <h2 class="section-title">Mission Sessions ({{ $mission->missionSessions->count() }} Sessions)</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 12%;">Time</th>
                        <th style="width: 18%;">Facilitator</th>
                        <th style="width: 18%;">Speaker</th>
                        <th style="width: 15%;">Class</th>
                        <th style="width: 25%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mission->missionSessions as $session)
                        <tr>
                            <td>{{ $session->starts_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>{{ $session->starts_at?->format('H:i') ?? 'N/A' }}</td>
                            <td>{{ $session->facilitator?->full_name ?? 'N/A' }}</td>
                            <td>{{ $session->speaker?->full_name ?? 'N/A' }}</td>
                            <td>{{ $session->classGroup?->name ?? 'N/A' }}</td>
                            <td>{{ Str::limit($session->notes, 50) ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Souls Reached --}}
    @if ($mission->souls && $mission->souls->count() > 0)
        <div class="section avoid-break">
            <h2 class="section-title">Souls Reached ({{ $mission->souls->count() }} Students)</h2>

            {{-- Summary by decision type --}}
            @php
                $soulsByDecision = $mission->souls->groupBy('decision_type');
                $decisionLabels = [
                    1 => 'Salvation',
                    2 => 'Rededication',
                    3 => 'Camp',
                    4 => 'Prayer',
                    5 => 'Other',
                ];
            @endphp
            <div class="stats-grid" style="grid-template-columns: repeat({{ min($soulsByDecision->count(), 4) }}, 1fr); margin-bottom: 15px;">
                @foreach ($soulsByDecision as $type => $souls)
                    <div class="stat-card">
                        <div class="stat-value">{{ $souls->count() }}</div>
                        <div class="stat-label">{{ $decisionLabels[$type] ?? 'Other' }}</div>
                    </div>
                @endforeach
            </div>

            <table class="compact-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 20%;">Admission No.</th>
                        <th style="width: 35%;">Name</th>
                        <th style="width: 20%;">Class</th>
                        <th style="width: 20%;">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mission->souls as $index => $soul)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $soul->admission_number ?? 'N/A' }}</td>
                            <td>{{ $soul->full_name ?? 'N/A' }}</td>
                            <td>{{ $soul->classGroup->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-success">{{ $decisionLabels[$soul->decision_type] ?? 'Other' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Debrief Notes --}}
    @if ($mission->debriefNotes && $mission->debriefNotes->count() > 0)
        <div class="section keep-together">
            <h2 class="section-title">Debrief Notes</h2>
            @foreach ($mission->debriefNotes as $note)
                <div class="quote-box">
                    <div style="font-size: 8pt; color: #6b7280; margin-bottom: 5px;">
                        {{ $note->created_at?->format('F d, Y') }}
                        @if ($note->member)
                            - {{ $note->member->full_name }}
                        @endif
                    </div>
                    {{ $note->note }}
                </div>
            @endforeach
        </div>
    @endif

    {{-- Mission Questions --}}
    @if ($mission->missionQuestions && $mission->missionQuestions->count() > 0)
        <div class="section keep-together">
            <h2 class="section-title">Questions Raised ({{ $mission->missionQuestions->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 85%;">Question</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mission->missionQuestions as $question)
                        <tr>
                            <td>{{ $question->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                            <td>{{ $question->question }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Financial Summary --}}
    @if ($mission->accountingEvent)
        <div class="page-break"></div>
        <div class="section">
            <h2 class="section-title">Financial Summary</h2>

            {{-- Financial Overview Cards --}}
            @php
                $accountingEvent = $mission->accountingEvent;
                $credits = $accountingEvent->allocationEntries->where('entry_type', \App\Enums\PRFEntryType::CREDIT->value)->sum('amount');
                $debits = $accountingEvent->allocationEntries->where('entry_type', \App\Enums\PRFEntryType::DEBIT->value)->sum('amount');
                $balance = $credits - $debits;
            @endphp
            <div class="financial-summary keep-together">
                <div class="financial-row">
                    <span>Amount Disbursed (Credits)</span>
                    <span>KES {{ number_format($credits, 2) }}</span>
                </div>
                <div class="financial-row">
                    <span>Amount Spent (Debits)</span>
                    <span>KES {{ number_format($debits, 2) }}</span>
                </div>
                <div class="financial-row">
                    <span>Balance</span>
                    <span style="color: {{ $balance >= 0 ? '#065f46' : '#991b1b' }}">KES {{ number_format($balance, 2) }}</span>
                </div>
            </div>

            {{-- Allocation Entries (Expenses) --}}
            @php
                $debitEntries = $accountingEvent->allocationEntries->where('entry_type', \App\Enums\PRFEntryType::DEBIT->value);
            @endphp
            @if ($debitEntries->count() > 0)
                <h3 class="subsection-title">Expenses (Debits)</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Description</th>
                            <th style="width: 20%;">Category</th>
                            <th style="width: 20%;">Member</th>
                            <th style="width: 10%;">Qty</th>
                            <th style="width: 20%;">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($debitEntries as $entry)
                            <tr>
                                <td>{{ $entry->narration ?? 'N/A' }}</td>
                                <td>{{ $entry->expenseCategory->name ?? 'N/A' }}</td>
                                <td>{{ $entry->member->full_name ?? 'N/A' }}</td>
                                <td>{{ $entry->quantity ?? 1 }}</td>
                                <td style="text-align: right;">{{ number_format($entry->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f3f4f6; font-weight: bold;">
                            <td colspan="4" style="text-align: right;">Total Expenses:</td>
                            <td style="text-align: right;">KES {{ number_format($debits, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            {{-- Credits (Disbursements) --}}
            @php
                $creditEntries = $accountingEvent->allocationEntries->where('entry_type', \App\Enums\PRFEntryType::CREDIT->value);
            @endphp
            @if ($creditEntries->count() > 0)
                <h3 class="subsection-title">Disbursements (Credits)</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">Description</th>
                            <th style="width: 25%;">Member</th>
                            <th style="width: 20%;">Confirmation</th>
                            <th style="width: 20%;">Amount (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($creditEntries as $entry)
                            <tr>
                                <td>{{ $entry->narration ?? 'N/A' }}</td>
                                <td>{{ $entry->member->full_name ?? 'N/A' }}</td>
                                <td style="font-size: 7pt;">{{ Str::limit($entry->confirmation_message, 30) ?? '-' }}</td>
                                <td style="text-align: right;">{{ number_format($entry->amount ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f3f4f6; font-weight: bold;">
                            <td colspan="3" style="text-align: right;">Total Disbursed:</td>
                            <td style="text-align: right;">KES {{ number_format($credits, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            {{-- Requisitions --}}
            @if ($accountingEvent->requisitions && $accountingEvent->requisitions->count() > 0)
                <h3 class="subsection-title">Requisitions</h3>
                @foreach ($accountingEvent->requisitions as $requisition)
                    <div class="keep-together" style="margin-bottom: 15px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">
                            <div>
                                <strong>{{ $requisition->member->full_name ?? 'N/A' }}</strong>
                                <span style="color: #6b7280; font-size: 8pt;"> - {{ $requisition->requisition_date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>
                            <div>
                                @php
                                    $reqStatusColors = [
                                        1 => 'badge-warning',
                                        2 => 'badge-info',
                                        3 => 'badge-success',
                                        4 => 'badge-danger',
                                    ];
                                    $reqStatusLabels = [
                                        1 => 'Pending',
                                        2 => 'Under Review',
                                        3 => 'Approved',
                                        4 => 'Rejected',
                                    ];
                                @endphp
                                <span class="badge {{ $reqStatusColors[$requisition->approval_status] ?? 'badge-info' }}">
                                    {{ $reqStatusLabels[$requisition->approval_status] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>

                        @if ($requisition->requisitionItems && $requisition->requisitionItems->count() > 0)
                            <table class="compact-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Item</th>
                                        <th style="width: 20%;">Category</th>
                                        <th style="width: 15%;">Unit Price</th>
                                        <th style="width: 10%;">Qty</th>
                                        <th style="width: 20%;">Total (KES)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requisition->requisitionItems as $item)
                                        <tr>
                                            <td>{{ $item->item_name ?? $item->narration ?? 'N/A' }}</td>
                                            <td>{{ $item->expenseCategory->name ?? 'N/A' }}</td>
                                            <td style="text-align: right;">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                            <td style="text-align: center;">{{ $item->quantity ?? 1 }}</td>
                                            <td style="text-align: right;">{{ number_format($item->total_price ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f3f4f6; font-weight: bold;">
                                        <td colspan="4" style="text-align: right;">Requisition Total:</td>
                                        <td style="text-align: right;">KES {{ number_format($requisition->total_amount ?? 0, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif

                        @if ($requisition->approvedBy)
                            <div style="margin-top: 8px; font-size: 8pt; color: #6b7280;">
                                Approved by: {{ $requisition->approvedBy->full_name }}
                                @if ($requisition->approved_at)
                                    on {{ $requisition->approved_at->format('M d, Y') }}
                                @endif
                            </div>
                        @endif

                        @if ($requisition->remarks)
                            <div style="margin-top: 5px; font-size: 8pt; color: #6b7280;">
                                <em>Remarks: {{ $requisition->remarks }}</em>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            {{-- Refunds --}}
            @if ($accountingEvent->refunds && $accountingEvent->refunds->count() > 0)
                <h3 class="subsection-title">Refunds</h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Date</th>
                            <th style="width: 30%;">Amount</th>
                            <th style="width: 30%;">Charge</th>
                            <th style="width: 20%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accountingEvent->refunds as $refund)
                            <tr>
                                <td>{{ $refund->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                <td style="text-align: right;">KES {{ number_format($refund->amount ?? 0, 2) }}</td>
                                <td style="text-align: right;">KES {{ number_format($refund->charge ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $refund->status === 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($refund->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif

    {{-- Weather Forecasts --}}
    @if ($mission->weatherForecasts && $mission->weatherForecasts->count() > 0)
        <div class="section keep-together">
            <h2 class="section-title">Weather Conditions</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                @foreach ($mission->weatherForecasts as $forecast)
                    <div class="weather-card">
                        <div class="weather-date">
                            {{ $forecast->forecast_date?->format('l, M d') ?? 'N/A' }}
                        </div>
                        <div style="font-size: 9pt;">
                            <strong>{{ $forecast->weather_code_description ?? 'N/A' }}</strong><br>
                            @if (is_array($forecast->temperature))
                                Temp: {{ $forecast->temperature['avg'] ?? 'N/A' }}°C |
                            @endif
                            @if (is_array($forecast->humidity))
                                Humidity: {{ $forecast->humidity['avg'] ?? 'N/A' }}% |
                            @endif
                            @if (is_array($forecast->precipitation_probability))
                                Rain: {{ $forecast->precipitation_probability['avg'] ?? 'N/A' }}%
                            @endif
                        </div>
                        @if ($forecast->dressing_recommendations)
                            <div style="font-size: 8pt; color: #0e7490; margin-top: 5px;">
                                <em>{{ $forecast->dressing_recommendations }}</em>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recommendations --}}
    @if ($mission->activity_recommendations || $mission->weather_recommendations || $mission->dressing_recommendations)
        <div class="section keep-together">
            <h2 class="section-title">Recommendations</h2>
            @if ($mission->activity_recommendations)
                <div class="note-box">
                    <strong>Activity:</strong> {{ $mission->activity_recommendations }}
                </div>
            @endif
            @if ($mission->weather_recommendations)
                <div class="note-box">
                    <strong>Weather:</strong> {{ $mission->weather_recommendations }}
                </div>
            @endif
            @if ($mission->dressing_recommendations)
                <div class="note-box">
                    <strong>Dressing:</strong> {{ $mission->dressing_recommendations }}
                </div>
            @endif
        </div>
    @endif

    {{-- Footer --}}
    <div class="report-footer">
        <div class="confidential">CONFIDENTIAL - FOR INTERNAL USE ONLY</div>
        <div>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
        <div style="margin-top: 5px;">
            Report ID: {{ $mission->ulid }} | Page generated at {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
@endsection
