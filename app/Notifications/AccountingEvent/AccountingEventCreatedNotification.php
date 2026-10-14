<?php

namespace App\Notifications\AccountingEvent;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Enums\PRFNotificationType;
use App\Models\AccountingEvent;
use App\Models\Mission;
use App\Models\PRFEvent;
use App\Models\Requisition;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

/**
 * An accounting event was opened for a mission or an event: the responsible desk should raise
 * (or edit) its requisition.
 */
class AccountingEventCreatedNotification extends BaseNotification implements HasTargetApp
{
    public function __construct(
        public AccountingEvent $accountingEvent,
    ) {}

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::LEADERSHIP_APP;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->shouldSendFcm($notifiable) ? ['mail', FcmChannel::class] : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject($this->title())
            ->line($this->body());
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $requisition = Requisition::query()->where('accounting_event_id', $this->accountingEvent->id)->first();

        return new FcmMessage(notification: new FcmNotification(title: $this->title(), body: $this->body()))->data([
            'type' => PRFNotificationType::ACCOUNTING_EVENT_CREATED->value,
            'accounting_event_ulid' => $this->accountingEvent->ulid,
            'requisition_ulid' => (string) ($requisition?->ulid ?? ''),
            'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
        ])->topic(PRFEnvironment::fromEnv(config('app.env'))->value . '_' . PRFAppTopics::LEADERSHIP_APP->value);
    }

    private function title(): string
    {
        $source = $this->accountingEvent->accountingEventable;

        return match (true) {
            $source instanceof Mission => sprintf(
                '%s: %s - %s Requisition',
                $source->start_date->format('d-m-Y'),
                $source->school->name,
                $source->missionType->name,
            ),
            $source instanceof PRFEvent => sprintf(
                '%s: %s Requisition',
                $source->start_date->format('d-m-Y'),
                $source->name,
            ),
            default => "{$this->accountingEvent->name} Requisition",
        };
    }

    private function body(): string
    {
        $what = $this->accountingEvent->accountingEventable instanceof Mission ? 'mission' : 'event';

        return "An accounting event has been created for this {$what}. Please go ahead and make or edit the requisition.";
    }
}
