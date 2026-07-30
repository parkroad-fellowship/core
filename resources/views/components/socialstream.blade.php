<div class="space-y-6 mt-3 mb-2">
    @php
        $providers = config('socialstream.providers', []);
    @endphp

    @if(! empty($providers))
        <div class="relative flex items-center">
            <div class="grow border-t border-gray-400"></div>
            <span class="shrink text-gray-400 px-6">
                {{ config('socialstream.prompt', 'Or Login Via') }}
            </span>
            <div class="grow border-t border-gray-400"></div>
        </div>
    @endif

    <x-input-error :for="'socialstream'" class="text-center"/>

    <div class="grid gap-4">
        @foreach ($providers as $provider)
            <a class="flex gap-2 items-center justify-center transition duration-200 border border-gray-400 w-full py-2.5 rounded-lg text-sm shadow-xs hover:shadow-md"
               href='{{ route('oauth.redirect', $provider) }}'>
                <x-socialstream-icons.provider-icon :provider="$provider" class="h-6 w-6"/>
                <span class="block font-medium text-sm text-gray-700">{{ ucfirst($provider) }}</span>
            </a>
        @endforeach
    </div>
</div>
