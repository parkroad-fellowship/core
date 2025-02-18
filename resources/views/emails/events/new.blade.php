<body>
    {{-- Body --}}
    <p>{{ __('Hello!',) }}</p>

    <p>{{ $prfEvent->description }}</p>

    <p><strong>{{ __('Start Date: :start_date', ['start_date' => $prfEvent->start_date]) }}</strong></p>
    <p><strong>{{ __('End Date: :end_date', ['end_date' => $prfEvent->end_date]) }}</strong></p>
    <p><strong>{{ __('Start Time: :start_time', ['start_time' => $prfEvent->start_time]) }}</strong></p>
    <p><strong>{{ __('End Time: :end_time', ['end_time' => $prfEvent->end_time]) }}</strong></p>

    <p>{{ __('Thank you for using our application!') }}</p>

    {{-- Footer --}}
    <footer>
        <p>© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
    </footer>
</body>
