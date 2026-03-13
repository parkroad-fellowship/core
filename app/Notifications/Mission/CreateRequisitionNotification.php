<?php

namespace App\Notifications\Mission;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\AccountingEvent;
use App\Models\Mission;
use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class CreateRequisitionNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    public Mission $mission;

    public ?Requisition $requisition;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AccountingEvent $accountingEvent
    ) {
        $this->mission = Mission::query()
            ->where('id', $this->accountingEvent->accounting_eventable_id)
            ->with(['school', 'missionType'])
            ->first();

        $this->requisition = Requisition::query()
            ->where('accounting_event_id', $this->accountingEvent->id)
            ->first();
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
        $mission = $this->mission;

        return (new MailMessage)
            ->subject(sprintf('%s: %s - %s Requisition', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name))
            ->line('An accounting event has been created for this mission. Please go ahead and make/edit the requisition.');
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
        $mission = $this->mission;

        $title = sprintf('%s: %s - %s Requisition', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name);
        $body = 'An accounting event has been created for this mission. Please go ahead and make/edit the requisition.';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'new_requisition',
                'accounting_event_ulid' => $this->accountingEvent->ulid,
                'requisition_ulid' => (string) $this->requisition?->ulid ?? '',
                'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
            ])
            ->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::LEADERSHIP_APP->value
            );
    }
}
