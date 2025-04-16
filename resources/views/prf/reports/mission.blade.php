<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Report</title>
    <style>
        /* TailwindCSS-inspired styles for PDF */
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.5;
            color: #1a202c;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: bold;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        h3 {
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .section {
            margin-bottom: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            margin-bottom: 0.5rem;
        }

        .label {
            font-weight: bold;
            color: #4a5568;
        }

        .value {
            color: #1a202c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        th {
            background-color: #f7fafc;
            text-align: left;
            padding: 0.5rem;
            font-weight: bold;
            border: 1px solid #e2e8f0;
        }

        td {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
        }

        .footer {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.875rem;
            color: #718096;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 1rem;
        }

        .image-item {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if (config('app.logo'))
                <img src="{{ config('app.logo') }}" alt="Company Logo" class="logo">
            @endif
            <h1>Mission Report</h1>
            <p>Generated on: {{ now()->format('F d, Y') }}</p>
        </div>

        @if ($mission->executive_summary)
            <div class="section"
                style="background-color: #f8fafc; padding: 1rem; border-left: 4px solid #4299e1; margin-bottom: 2rem;">
                <h2 style="color: #2b6cb0; margin-top: 0;">Executive Summary</h2>
                <p style="font-style: italic; font-size: 1.05rem;">{{ $mission->executive_summary }}</p>
            </div>
        @endif

        <div class="section">
            <h2>Mission Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Mission ID:</span>
                    <span class="value">{{ $mission->ulid }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Theme:</span>
                    <span class="value">{{ $mission->theme ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Status:</span>
                    <span class="value">{{ $mission->status_label }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Start Date:</span>
                    <span
                        class="value">{{ $mission->start_date ? $mission->start_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Start Time:</span>
                    <span class="value">{{ $mission->start_time ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">End Date:</span>
                    <span class="value">{{ $mission->end_date ? $mission->end_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">End Time:</span>
                    <span class="value">{{ $mission->end_time ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Capacity:</span>
                    <span class="value">{{ $mission->capacity ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Subscriptions Needed:</span>
                    <span class="value">{{ $mission->mission_subscriptions_needed }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Location:</span>
                    <span class="value">{{ $mission->location ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">WhatsApp Link:</span>
                    <span class="value">{{ $mission->whats_app_link ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        @if ($mission->executive_summary)
            <div class="section">
                <h2>Executive Summary</h2>
                <p>{{ $mission->executive_summary }}</p>
            </div>
        @endif

        @if ($mission->mission_prep_notes)
            <div class="section">
                <h2>Mission Preparation Notes</h2>
                <p>{{ $mission->mission_prep_notes }}</p>
            </div>
        @endif



        @if ($mission->school)
            <div class="section">
                <h2>School Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">School Name:</span>
                        <span class="value">{{ $mission->school->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Address:</span>
                        <span class="value">{{ $mission->school->address ?? 'N/A' }}</span>
                    </div>
                </div>

                @if ($mission->school->schoolContacts && count($mission->school->schoolContacts) > 0)
                    <h3>School Contacts</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Type</th>
                                <th>Email</th>
                                <th>Phone</th>
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

        @if ($mission->missionType)
            <div class="section">
                <h2>Mission Type</h2>
                <div class="info-item">
                    <span class="label">Type:</span>
                    <span class="value">{{ $mission->missionType->name ?? 'N/A' }}</span>
                </div>
                @if ($mission->missionType->description)
                    <div class="info-item">
                        <span class="label">Description:</span>
                        <span class="value">{{ $mission->missionType->description }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if ($mission->schoolTerm)
            <div class="section">
                <h2>School Term</h2>
                <div class="info-item">
                    <span class="label">Term:</span>
                    <span class="value">{{ $mission->schoolTerm->name ?? 'N/A' }}
                        ({{ $mission->schoolTerm->year }})</span>
                </div>
            </div>
        @endif

        @if ($mission->missionSubscriptions && count($mission->missionSubscriptions) > 0)
            <div class="section">
                <h2>Mission Subscriptions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Status</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->missionSubscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->member->full_name ?? 'N/A' }}</td>
                                <td>{{ $subscription->status_label ?? 'N/A' }}</td>
                                <td>{{ $subscription->mission_role_label ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($mission->souls && count($mission->souls) > 0)
            <div class="section">
                <h2>Souls</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->souls as $soul)
                            <tr>
                                <td>{{ $soul->full_name ?? 'N/A' }}</td>
                                <td>{{ $soul->classGroup->name ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($mission->missionSessions && count($mission->missionSessions) > 0)
            <div class="section">
                <h2>Mission Sessions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Facilitator</th>
                            <th>Speaker</th>
                            <th>Class</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->missionSessions as $session)
                            <tr>
                                <td>{{ $session->starts_at ? $session->starts_at : 'N/A' }}</td>
                                <td>{{ $session->facilitator?->full_name ?? 'N/A' }}</td>
                                <td>{{ $session->speaker?->full_name ?? 'N/A' }}</td>
                                <td>{{ $session->classGroup?->name ?? 'N/A' }}</td>
                                <td>{{ $session->notes ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($mission->debriefNotes && count($mission->debriefNotes) > 0)
            <div class="section">
                <h2>Debrief Notes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->debriefNotes as $note)
                            <tr>
                                <td>{{ $note->created_at ? $note->created_at->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $note->note ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($mission->missionExpense)
            <div class="section">
                <h2>Mission Expenses</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label">Total Budget:</span>
                        <span class="value">{{ $mission->missionExpense->total_budget ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Total Spent:</span>
                        <span class="value">{{ $mission->missionExpense->total_spent ?? 'N/A' }}</span>
                    </div>
                </div>

                @if ($mission->missionExpense->expenses && count($mission->missionExpense->expenses) > 0)
                    <h3>Expense Details</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionExpense->expenses as $expense)
                                <tr>
                                    <td>{{ $expense->description ?? 'N/A' }}</td>
                                    <td>{{ $expense->amount ?? 'N/A' }}</td>
                                    <td>{{ $expense->date ? $expense->date->format('M d, Y') : 'N/A' }}</td>
                                    <td>{{ $expense->category ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        <div class="section">
            <h2>Recommendations</h2>
            <div class="info-grid">
                @if ($mission->dressing_recommendations)
                    <div class="info-item">
                        <span class="label">Dressing:</span>
                        <span class="value">{{ $mission->dressing_recommendations }}</span>
                    </div>
                @endif

            </div>
        </div>

        @if ($mission->weatherForecasts && count($mission->weatherForecasts) > 0)
            <div class="section">
                <h2>Weather Forecasts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Summary</th>
                            <th>Temperature</th>
                            <th>Humidity</th>
                            <th>Visibility</th>
                            <th>Precipitation</th>
                            <th>Recommendations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mission->weatherForecasts as $forecast)
                            <tr>
                                <td>{{ $forecast->forecast_date ? $forecast->forecast_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>{{ $forecast->weather_code_description }}</td>
                                <td>{{ is_array($forecast->humidity) ? $forecast->humidity['avg'] : 'N/A' }}
                                </td>
                                <td>{{ is_array($forecast->temperature) ? $forecast->temperature['avg'] : 'N/A' }}
                                </td>
                                <td>{{ is_array($forecast->visibility) ? $forecast->visibility['avg'] : 'N/A' }}
                                </td>
                                <td>{{ is_array($forecast->precipitation_probability) ? $forecast->precipitation_probability['avg'] : 'N/A' }}
                                </td>
                                <td>{{ $forecast->dressing_recommendations ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($mission->media && count($mission->media) > 0)
            <div class="section">
                <h2>Mission Photos</h2>
                <div class="image-gallery">
                    @foreach ($mission->getMedia('mission-photos') as $media)
                        <img src="{{ $media->getUrl() }}" alt="Mission Photo" class="image-item">
                    @endforeach
                </div>
            </div>
        @endif

        <div class="footer">
            <p>Confidential - For internal use only</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
