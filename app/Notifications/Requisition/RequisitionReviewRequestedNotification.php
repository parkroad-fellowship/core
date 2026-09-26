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

class RequisitionReviewRequestedNotification extends BaseNotification implements HasTargetApp
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
        $requisition->load(['accountingEvent', 'member', 'appointedApprover', 'requisitionItems']);

        $eventName = $requisition->accountingEvent->name ?? 'N/A';
        $requesterName = $requisition->member->full_name ?? 'N/A';
        $totalAmount = number_format($requisition->total_amount, 2);
        $itemCount = $requisition->requisitionItems->count();
        $submissionDate = $requisition->review_requested_at
            ? $requisition->review_requested_at->format('d/m/Y H:i:s')
            : now()->format('d/m/Y H:i:s');

        return new MailMessage()
            ->subject("📋 Requisition Review Required - {$eventName}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('A new requisition has been submitted and requires your **review and approval**.')
            ->line('')
            ->line('**Requisition Summary:**')
            ->line("• **Event:** {$eventName}")
            ->line("• **Submitted by:** {$requesterName}")
            ->line("• **Total Amount:** KES {$totalAmount}")
            ->line("• **Number of Items:** {$itemCount}")
            ->line("• **Submission Date:** {$submissionDate}")
            ->line("• **Requisition ID:** {$requisition->ulid}")
            ->line('')
            ->line('**Action Required:**')
            ->line('Please review the requisition details and take one of the following actions:')
            ->line('• **Approve** - If all details are correct and within budget')
            ->line('• **Reject** - If changes are needed or request is invalid')
            ->line('')
            ->line('**Important Notes:**')
            ->line('• Review all line items and payment instructions carefully')
            ->line('• Ensure the requisition aligns with the event budget')
            ->line('• Add approval notes if rejecting to guide the requester')
            ->line('')
            ->action('Review Requisition Now', config('prf.app.leadership_app.android.url'))
            ->line('')
            ->line('Please complete your review soon to avoid delays in event preparation.')
            ->line('')
            ->line('Thank you for your prompt attention to this matter.')
            ->salutation('Best regards,');
    }

    public function toFcm($notifiable)
    {
        $requisition = $this->requisition;
        $requisition->load(['accountingEvent', 'member']);

        $eventName = $requisition->accountingEvent->name ?? 'Unknown Event';
        $requesterName = $requisition->member->full_name ?? 'Unknown Member';
        $totalAmount = number_format($requisition->total_amount, 2);

        $title = '📋 Review Required';
        $body = "{$requesterName} submitted a {$eventName} requisition (KES {$totalAmount}) for your review.";

        return new FcmMessage(notification: new FcmNotification(title: $title, body: $body))->data([
            'type' => PRFNotificationType::REQUISITION_REVIEW_REQUESTED->value,
            'requisition_ulid' => $requisition->ulid,
            'event_name' => $eventName,
            'requester_name' => $requesterName,
            'total_amount' => (string) $requisition->total_amount,
            'notification_action' => 'review_requisition',
            'priority' => 'high',
            'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
        ])->topic(PRFEnvironment::fromEnv(config('app.env'))->value . '_' . PRFAppTopics::LEADERSHIP_APP->value);
    }
}
