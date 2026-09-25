<?php

namespace App\Notifications\Concerns;

use App\Enums\PRFIntegration;
use App\Services\Tenancy\TenantIntegrations;

/**
 * Push (FCM) is only attempted when the tenant has configured Firebase and the
 * notifiable has device tokens. Without configuration the push is skipped (fail closed).
 */
trait RoutesToFcm
{
    protected function shouldSendFcm(object $notifiable): bool
    {
        return !empty($notifiable->fcm_tokens) && app(TenantIntegrations::class)->isConfigured(PRFIntegration::FCM);
    }
}
