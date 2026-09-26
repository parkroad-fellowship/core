<?php

namespace App\Notifications;

use App\Notifications\Concerns\RoutesToFcm;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;

/**
 * Parent of every queued notification: sent on the `high` queue after the surrounding
 * transaction commits, with push only when the tenant has configured Firebase.
 *
 * Notifications that carry secrets (temporary passwords, reset tokens) must NOT extend this;
 * they extend Notification directly and are sent with notifyNow().
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesToFcm;

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['mail' => 'high', 'database' => 'high', FcmChannel::class => 'high'];
    }
}
