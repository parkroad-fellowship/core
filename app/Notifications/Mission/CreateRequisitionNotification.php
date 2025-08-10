<?php

namespace App\Notifications\Mission;

use App\Models\AccountingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class CreateRequisitionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AccountingEvent $accountingEvent
    ) {
        //
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
        $mission = $this->accountingEvent->accountingEventable;
        $mission->load(['school', 'missionType']);

        return (new MailMessage)
            ->subject(sprintf('%s : %s - %s Requisition', [$mission->start_date, $mission->school->name, $mission->missionType->name]))
            ->line('An accounting event has been created for this mission. Please go ahead and make a requisition.');
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

    public function toFcm($notifiable): FcmMessage
    {
        $mission = $this->accountingEvent->accountingEventable;
        $mission->load(['school', 'missionType']);

        $title = sprintf('%s : %s - %s Requisition', [$mission->start_date, $mission->school->name, $mission->missionType->name]);
        $body = 'An accounting event has been created for this mission. Please go ahead and make a requisition.';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'new_requisition',
                'accounting_event_ulid' => $this->accountingEvent->ulid,
            ]);
    }
}
