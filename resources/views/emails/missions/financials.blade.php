<body>
    {{-- Body --}}
    <p>{{ __('Hello Treasurer,') }}</p>

    <p>{{ __('Kindly find the financials of the mission to :school linked in this email', ['school' => $mission->school->name]) }}</p>

    <p>
        {{-- HTML Link --}}
        <a href="{{ $link }}">{{ __('View Financials') }}</a>
    </p>

    <p>{{ __('Thank you for using our application!') }}</p>

    {{-- Footer --}}
    <footer>
        <p>© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
    </footer>
</body>
