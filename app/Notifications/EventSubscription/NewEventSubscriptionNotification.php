<?php

namespace App\Notifications\EventSubscription;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\EventSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewEventSubscriptionNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public EventSubscription $eventSubscription,
    ) {
        //
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::LEADERSHIP_APP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if (! empty($notifiable->fcm_tokens)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $eventSubscription = $this->eventSubscription;
        $eventSubscription->load('prfEvent', 'member');
        $extraPeople = $eventSubscription->number_of_attendees - 1;

        return (new MailMessage)
            ->subject("New Event Subscription: {$eventSubscription->prfEvent->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('🎉 **Someone new has subscribed to an event you are to be notified about!**')
            ->line('')
            ->line('**Subscription Details:**')
            ->line("👤 **Subscriber:** {$eventSubscription->member->full_name}")
            ->line(
                $eventSubscription->number_of_attendees === 1
                    ? '👥 **Number of Attendees:** Coming alone'
                    : "👥 **Number of Attendees:** Coming with {$extraPeople} people"
            )
            ->line('')
            ->line('---');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable): FcmMessage
    {
        $eventSubscription = $this->eventSubscription;
        $eventSubscription->load('prfEvent', 'member');

        $title = "New Event Subscription: {$eventSubscription->prfEvent->name}";
        $body = "{$eventSubscription->member->full_name} has subscribed to your event with {$eventSubscription->number_of_attendees} attendee(s).";

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'new_event_subscription',
                'event_subscription_ulid' => $eventSubscription->ulid,
                'event_ulid' => $eventSubscription->prfEvent->ulid,
                'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::LEADERSHIP_APP->value
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
