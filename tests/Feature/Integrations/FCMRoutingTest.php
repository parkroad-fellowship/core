<?php

use App\Notifications\Concerns\RoutesToFcm;

function fcmProbe(): object
{
    return new class {
        use RoutesToFcm;

        public function check(object $notifiable): bool
        {
            return $this->shouldSendFcm($notifiable);
        }
    };
}

it('skips push notifications when the tenant has not configured Firebase', function () {
    expect(fcmProbe()->check((object) ['fcm_tokens' => ['token-1']]))->toBeFalse();
});

it('sends push notifications once Firebase is configured and the recipient has tokens', function () {
    configureIntegration(['firebase.service_account_json' => '{"type":"service_account"}']);

    expect(fcmProbe()->check((object) ['fcm_tokens' => ['token-1']]))
        ->toBeTrue()
        ->and(fcmProbe()->check((object) ['fcm_tokens' => []]))
        ->toBeFalse();
});
