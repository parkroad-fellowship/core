@extends('prf.reports.template')

@section('content')
    <style>
        @media print {
            .page-break-before {
                page-break-before: always;
            }
            .page-break-after {
                page-break-after: always;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-8 pb-4 border-b border-gray-200 avoid-break">
            @if (config('app.logo'))
                <img src="{{ config('app.logo') }}" alt="Company Logo" class="max-w-[150px] mx-auto mb-4">
            @endif
            <h1 class="text-2xl font-bold mb-2">Mission Report</h1>
            <p class="text-gray-600">Generated on: {{ now()->format('F d, Y') }}</p>
        </div>

        @if ($mission->executive_summary)
            <div class="mb-8 bg-blue-50 p-4 border-l-4 border-blue-500 avoid-break">
                <h2 class="text-xl font-bold text-blue-700 mt-0">Executive Summary</h2>
                <p class="italic text-lg">{{ $mission->executive_summary }}</p>
            </div>
        @endif

        <div class="mb-6 mt-4 avoid-break">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Mission Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Mission ID:</span>
                    <span>{{ $mission->ulid }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Theme:</span>
                    <span>{{ $mission->theme ?? 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Status:</span>
                    <span>{{ $mission->status_label }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Start Date:</span>
                    <span>{{ $mission->start_date ? $mission->start_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Start Time:</span>
                    <span>{{ $mission->start_time ?? 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">End Date:</span>
                    <span>{{ $mission->end_date ? $mission->end_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">End Time:</span>
                    <span>{{ $mission->end_time ?? 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Capacity:</span>
                    <span>{{ $mission->capacity ?? 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Subscriptions Needed:</span>
                    <span>{{ $mission->mission_subscriptions_needed }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Location:</span>
                    <span>{{ $mission->location ?? 'N/A' }}</span>
                </div>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">WhatsApp Link:</span>
                    <span>{{ $mission->whats_app_link ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        @if ($mission->mission_prep_notes)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Preparation Notes</h2>
                <p class="mb-4">{{ $mission->mission_prep_notes }}</p>
            </div>
        @endif

        @if ($mission->school)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-700">School Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="mb-2">
                        <span class="font-semibold text-gray-600">School Name:</span>
                        <span>{{ $mission->school->name ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-gray-600">Address:</span>
                        <span>{{ $mission->school->address ?? 'N/A' }}</span>
                    </div>
                </div>

                @if ($mission->school->schoolContacts && count($mission->school->schoolContacts) > 0)
                    <h3 class="text-lg font-semibold mb-2 text-gray-600">School Contacts</h3>
                    <div class="overflow-x-auto print:overflow-visible avoid-break">
                        <table class="w-full text-sm border border-gray-200 print:text-xs">
                            <thead>
                                <tr>
                                    <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                        Name</th>
                                    <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                        Contact Type</th>
                                    <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                        Email</th>
                                    <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                        Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mission->school->schoolContacts as $contact)
                                    <tr>
                                        <td class="py-1 px-2 border border-gray-200">{{ $contact->name ?? 'N/A' }}</td>
                                        <td class="py-1 px-2 border border-gray-200">
                                            {{ $contact->contactType->name ?? 'N/A' }}</td>
                                        <td class="py-1 px-2 border border-gray-200 break-words">
                                            {{ $contact->email ?? 'N/A' }}
                                        </td>
                                        <td class="py-1 px-2 border border-gray-200">{{ $contact->phone ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if ($mission->missionType)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Type</h2>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Type:</span>
                    <span>{{ $mission->missionType->name ?? 'N/A' }}</span>
                </div>
                @if ($mission->missionType->description)
                    <div class="mb-2">
                        <span class="font-semibold text-gray-600">Description:</span>
                        <span>{{ $mission->missionType->description }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if ($mission->schoolTerm)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">School Term</h2>
                <div class="mb-2">
                    <span class="font-semibold text-gray-600">Term:</span>
                    <span>{{ $mission->schoolTerm->name ?? 'N/A' }}
                        ({{ $mission->schoolTerm->year }})</span>
                </div>
            </div>
        @endif

        @if ($mission->missionSubscriptions && count($mission->missionSubscriptions) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Subscriptions</h2>
                <div class="overflow-x-auto print:overflow-visible avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs">
                        <thead>
                            <tr>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Member</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Status</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionSubscriptions as $subscription)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $subscription->member->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $subscription->status_label ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $subscription->mission_role_label ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->souls && count($mission->souls) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Souls</h2>
                <div class="overflow-x-auto print:overflow-visible avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs">
                        <thead>
                            <tr>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Name</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->souls as $soul)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200">{{ $soul->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">{{ $soul->classGroup->name ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->missionSessions && count($mission->missionSessions) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Sessions</h2>
                <div class="overflow-x-auto print:overflow-visible avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs">
                        <thead>
                            <tr>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Date</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Facilitator</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Speaker</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Class</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-2/6">
                                    Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionSessions as $session)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $session->starts_at ? $session->starts_at : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $session->facilitator?->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $session->speaker?->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $session->classGroup?->name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 break-words">{{ $session->notes ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->debriefNotes && count($mission->debriefNotes) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Debrief Notes</h2>
                <div class="overflow-x-auto print:overflow-visible avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs">
                        <thead>
                            <tr>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                    Date</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-3/4">
                                    Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->debriefNotes as $note)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $note->created_at ? $note->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 break-words">{{ $note->note ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->weatherForecasts && count($mission->weatherForecasts) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Weather Forecasts</h2>
                <div class="overflow-x-auto print:overflow-visible avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs">
                        <thead>
                            <tr>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Date</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Summary</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Temp</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Humid</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Vis</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Precip</th>
                                <th class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600">
                                    Recommendations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->weatherForecasts as $forecast)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $forecast->forecast_date ? $forecast->forecast_date->format('M d') : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ $forecast->weather_code_description }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ is_array($forecast->temperature) ? $forecast->temperature['avg'] : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ is_array($forecast->humidity) ? $forecast->humidity['avg'] : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ is_array($forecast->visibility) ? $forecast->visibility['avg'] : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200">
                                        {{ is_array($forecast->precipitation_probability) ? $forecast->precipitation_probability['avg'] : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200 break-words">
                                        {{ $forecast->dressing_recommendations ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Footer section remains unchanged -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-sm text-gray-500 text-center">
            <p>Confidential - For internal use only</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>

@endsection
