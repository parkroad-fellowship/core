<?php

namespace App\Notifications\Requisition;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Enums\PRFNotificationType;
use App\Models\Requisition;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class RequisitionRejectedNotification extends BaseNotification implements HasTargetApp
{
    public function __construct(
        public Requisition $requisition,
    ) {}

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
        if ($this->shouldSendFcm($notifiable)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $requisition = $this->requisition;
        $requisition->load(['accountingEvent', 'member', 'approvedBy']);

        $eventName = $requisition->accountingEvent->name ?? 'N/A';
        $requesterName = $requisition->member->full_name ?? 'N/A';
        $rejectedBy = $requisition->approvedBy->full_name ?? 'System';
        $totalAmount = number_format($requisition->total_amount, 2);
        $rejectionNotes = $requisition->approval_notes ?? 'No specific reason provided.';

        return new MailMessage()
            ->subject("❌ Requisition Rejected - {$eventName}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('We regret to inform you that this requisition has been **rejected**.')
            ->line('')
            ->line('**Requisition Details:**')
            ->line("• **Event:** {$eventName}")
            ->line("• **Requested by:** {$requesterName}")
            ->line("• **Total Amount:** KES {$totalAmount}")
            ->line("• **Rejected by:** {$rejectedBy}")
            ->line('• **Rejection Date:** ' . now()->format('d/m/Y H:i:s'))
            ->line('')
            ->line('**Reason for Rejection:**')
            ->line($rejectionNotes)
            ->line('')
            ->line('**What\'s Next:**')
            ->line('1. Review the rejection reason above')
            ->line('2. Make necessary adjustments to this requisition')
            ->line('3. Submit a new requisition if needed')
            ->line('4. Contact the approver if you need clarification')
            ->line('')
            ->action('View Requisition Details', config('prf.app.leadership_app.android.url'))
            ->line('')
            ->line(
                'If you have any questions about this rejection, please don\'t hesitate to reach out to the approver.',
            )
            ->line('')
            ->line('Thank you for your understanding.')
            ->salutation('Best regards,');
    }

    public function toFcm($notifiable)
    {
        $requisition = $this->requisition;
        $requisition->load(['accountingEvent']);

        $eventName = $requisition->accountingEvent->name ?? 'Unknown Event';
        $totalAmount = number_format($requisition->total_amount, 2);

        $title = '❌ Requisition Rejected';
        $body = "The {$eventName} requisition (KES {$totalAmount}) has been rejected. Please review the details.";

        return new FcmMessage(notification: new FcmNotification(title: $title, body: $body))->data([
            'type' => PRFNotificationType::REQUISITION_REJECTED->value,
            'requisition_ulid' => $requisition->ulid,
            'event_name' => $eventName,
            'total_amount' => (string) $requisition->total_amount,
            'rejection_reason' => $requisition->approval_notes ?? 'No reason provided',
            'notification_action' => 'view_requisition',
            'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
        ])->topic(PRFEnvironment::fromEnv(config('app.env'))->value . '_' . PRFAppTopics::LEADERSHIP_APP->value);
    }
}
