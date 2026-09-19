@props(['forLivewire' => false])

@if (config('services.turnstile.enabled', true) && config('services.turnstile.site_key'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @if ($forLivewire)
        <div wire:ignore>
            <div
                class="cf-turnstile mt-4"
                data-sitekey="{{ config('services.turnstile.site_key') }}"
                data-callback="onPledgeTurnstile"
                data-expired-callback="onPledgeTurnstileExpired"
                data-error-callback="onPledgeTurnstileExpired"
            ></div>
        </div>
    @else
        <div class="cf-turnstile mt-4" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    @endif
@endif
