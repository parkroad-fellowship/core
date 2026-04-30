<?php

namespace App\Notifications\PRFEvent;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\AppSetting;
use App\Models\PRFEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewEventNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PRFEvent $prfEvent,
    ) {
        //
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::MISSIONS_APP;
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
        $event = $this->prfEvent;

        return (new MailMessage)
            ->subject("New Event: {$event->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line($event->description)
            ->line("Start Date: {$event->start_date->format('D, d-M-Y')}")
            ->line("End Date: {$event->end_date->format('D, d-M-Y')}")
            ->line('Please visit the missions app to subscribe to this event and to view more details.')
            ->action('Google Play', AppSetting::get('app_stores.android_url', ''))
            ->line('Thank you for using our application!');
    }

    public function toFcm($notifiable)
    {
        $event = $this->prfEvent;
        $title = "New Event: {$event->name}";
        $body = $event->description;

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'new_event',
                'event_ulid' => $event->ulid,
                'target_app' => PRFAppTopics::MISSIONS_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::MISSIONS_APP->value
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
