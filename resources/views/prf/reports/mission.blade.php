@extends('prf.reports.template')

@section('content')
    <!-- Fixed background image for print and screen -->
    <div class="page-background"></div>

    <div class="container mx-auto px-4 py-8 content-container">
        <div class="text-center mb-8 pb-4 border-b border-gray-200 avoid-break">
            <h1 class="text-2xl font-bold mb-2">Mission Report</h1>
            <p class="text-gray-600">Generated on: {{ now()->format('F d, Y') }}</p>
        </div>

        @if ($mission->executive_summary)
            <div class="mb-8 bg-blue-50 p-4 border-l-4 border-blue-500 avoid-break">
                <h2 class="text-xl font-bold text-blue-700 mt-0">Executive Summary</h2>
                <p class="italic text-lg">{{ $mission->executive_summary }}</p>
            </div>
        @endif

        <div class="mb-6 mt-4">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Mission Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-4 mb-4">
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
                    <span class="font-semibold text-gray-600">Missioner Shortage:</span>
                    <span>{{ $mission->mission_subscriptions_needed }}</span>
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
            <div class="mb-6 after-page-break">
                <h2 class="text-xl font-bold mb-4 text-gray-700">School Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-4 mb-4">
                    <div class="mb-2">
                        <span class="font-semibold text-gray-600">School Name:</span>
                        <span>{{ $mission->school->name ?? 'N/A' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-gray-600">Address:</span>
                        <span>{{ $mission->school->address ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-4 mb-4">
                    @if ($mission->missionType)
                        <div class="mb-6">
                            <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Type</h2>
                            <div class="mb-2">
                                <span class="font-semibold text-gray-600">Type:</span>
                                <span>{{ $mission->missionType->name ?? 'N/A' }}</span>
                            </div>
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
                </div>

                @if ($mission->school->schoolContacts && count($mission->school->schoolContacts) > 0)
                    <h3 class="text-lg font-semibold mb-2 text-gray-600">School Contacts</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                            <thead>
                                <tr>
                                    <th
                                        class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                        Name</th>
                                    <th
                                        class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                        Contact Type</th>
                                    <th
                                        class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/3">
                                        Email</th>
                                    <th
                                        class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                        Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mission->school->schoolContacts as $contact)
                                    <tr>
                                        <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $contact->name ?? 'N/A' }}</td>
                                        <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                            {{ $contact->contactType->name ?? 'N/A' }}</td>
                                        <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                            {{ $contact->email ?? 'N/A' }}
                                        </td>
                                        <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $contact->phone ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif



        @if ($mission->missionSubscriptions && count($mission->missionSubscriptions) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Subscriptions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-2/5">
                                    Member</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/5">
                                    Status</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-2/5">
                                    Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionSubscriptions as $subscription)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $subscription->member->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $subscription->status_label ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
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
                <div class="overflow-x-auto avoid-break">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                    Admission Number</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/2">
                                    Name</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                    Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->souls as $soul)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $soul->admission_number ?? 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $soul->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $soul->classGroup->name ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->missionSessions && count($mission->missionSessions) > 0)
            <div class="mb-6 avoid-break after-page-break">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Sessions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Date</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Facilitator</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Speaker</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Class</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-2/6">
                                    Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionSessions as $session)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $session->starts_at ? $session->starts_at->format('M d') : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $session->facilitator?->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $session->speaker?->full_name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $session->classGroup?->name ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $session->notes ?? 'N/A' }}
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
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                    Date</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-3/4">
                                    Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->debriefNotes as $note)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $note->created_at ? $note->created_at->format('M d, Y') : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">{{ $note->note ?? 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($mission->missionQuestions && count($mission->missionQuestions) > 0)
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-2 text-gray-700">Mission Questions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/4">
                                    Date</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-3/4">
                                    Question</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->missionQuestions as $missionQuestion)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $missionQuestion->created_at ? $missionQuestion->created_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $missionQuestion->question ?? 'N/A' }}
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
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 print:text-xs table-fixed">
                        <thead>
                            <tr>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Date</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/3">
                                    Summary</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Temp</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Humid</th>
                                <th
                                    class="bg-gray-50 text-left py-1 px-2 border border-gray-200 font-semibold text-gray-600 w-1/6">
                                    Precip</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mission->weatherForecasts as $forecast)
                                <tr>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $forecast->forecast_date ? $forecast->forecast_date->format('M d') : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ $forecast->weather_code_description ?? 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ is_array($forecast->temperature) ? ($forecast->temperature['avg'] ?? 'N/A') . '°' : 'N/A' }}
                                    </td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ is_array($forecast->humidity) ? ($forecast->humidity['avg'] ?? 'N/A') . '%' : 'N/A' }}</td>
                                    <td class="py-1 px-2 border border-gray-200 wrap-break-word">
                                        {{ is_array($forecast->precipitation_probability) ? ($forecast->precipitation_probability['avg'] ?? 'N/A') . '%' : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Recommendations in a separate section to avoid table overflow -->
                @if ($mission->weatherForecasts->whereNotNull('dressing_recommendations')->count() > 0)
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold mb-2 text-gray-600">Weather Recommendations</h3>
                        @foreach ($mission->weatherForecasts->whereNotNull('dressing_recommendations') as $forecast)
                            <div class="mb-2 p-2 bg-gray-50 rounded">
                                <span class="font-semibold">{{ $forecast->forecast_date ? $forecast->forecast_date->format('M d') : 'N/A' }}:</span>
                                {{ $forecast->dressing_recommendations }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Additional weather details in a separate section -->
                @if ($mission->weatherForecasts->count() > 0)
                    <div class="mt-4">
                        <h3 class="text-lg font-semibold mb-2 text-gray-600">Additional Weather Details</h3>
                        @foreach ($mission->weatherForecasts as $forecast)
                            <div class="mb-2 p-2 bg-gray-50 rounded text-sm">
                                <span class="font-semibold">{{ $forecast->forecast_date ? $forecast->forecast_date->format('M d') : 'N/A' }}:</span>
                                Visibility: {{ is_array($forecast->visibility) ? $forecast->visibility['avg'] : 'N/A' }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Footer section with added space -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-sm text-gray-500 text-center">
            <p>Confidential - For internal use only</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>

    <!-- Footer space -->
    <div class="footer-space"></div>
@endsection
