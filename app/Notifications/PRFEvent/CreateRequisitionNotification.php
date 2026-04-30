<?php

namespace App\Notifications\PRFEvent;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\AccountingEvent;
use App\Models\PRFEvent;
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

    public PRFEvent $prfEvent;

    public ?Requisition $requisition;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AccountingEvent $accountingEvent
    ) {
        $this->prfEvent = PRFEvent::query()
            ->where('id', $this->accountingEvent->accounting_eventable_id)
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
        $prfEvent = $this->prfEvent;

        return (new MailMessage)
            ->subject(sprintf('%s: %s Requisition', $prfEvent->start_date->format('d-m-Y'), $prfEvent->name))
            ->line('An accounting event has been created for this event. Please go ahead and make a requisition.');
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
        $prfEvent = $this->prfEvent;

        $title = sprintf('%s: %s Requisition', $prfEvent->start_date->format('d-m-Y'), $prfEvent->name);
        $body = 'An accounting event has been created for this event. Please go ahead and make a requisition.';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'new_requisition',
                'accounting_event_ulid' => $this->accountingEvent->ulid,
                'requisition_ulid' => (string) $this->requisition?->ulid ?? '',
                'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::LEADERSHIP_APP->value
            );
    }
}
