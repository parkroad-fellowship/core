<body>
    {{-- Body --}}
    <p>{{ __('Hello!',) }}</p>

    <p>{{ __('A new mission has been created for :school. Please visit the missions app to subscribe.', ['school' => $mission->school->name]) }}</p>

    <p><strong>{{ __('Type: :type', ['type' => $mission->missionType->name]) }}</strong></p>
    <p><strong>{{ __('Start Date: :start_date', ['start_date' => $mission->start_date]) }}</strong></p>
    <p><strong>{{ __('End Date: :end_date', ['end_date' => $mission->end_date]) }}</strong></p>
    <p><strong>{{ __('Start Time: :start_time', ['start_time' => $mission->start_time]) }}</strong></p>
    <p><strong>{{ __('End Time: :end_time', ['end_time' => $mission->end_time]) }}</strong></p>

    <p>{{ __('Thank you for using our application!') }}</p>

    {{-- Footer --}}
    <footer>
        <p>© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
    </footer>
</body>
