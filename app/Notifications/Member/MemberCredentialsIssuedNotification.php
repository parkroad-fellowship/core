<?php

namespace App\Notifications\Member;

use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Member;
use App\Settings\TenantSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the member's personal email once their Workspace mailbox exists.
 *
 * Carries a one-off temporary password, so it is deliberately NOT queued: send it with
 * notifyNow() so the password is never written to the jobs or failed_jobs tables.
 */
class MemberCredentialsIssuedNotification extends Notification
{
    public function __construct(
        public Member $member,
        private readonly string $temporaryPassword,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $organisation = TenantSettings::fromCurrentTenant()->organizationName;
        $deskEmails = Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS_DESK);

        return new MailMessage()
            ->when($deskEmails !== [], fn(MailMessage $mail) => $mail->replyTo($deskEmails[0]))
            ->subject("Your {$organisation} account is ready")
            ->greeting("Hello {$this->member->first_name},")
            ->line("Your {$organisation} Google account has been created.")
            ->line("**Email address:** {$this->member->email}")
            ->line("**Temporary password:** {$this->temporaryPassword}")
            ->line(
                'Open the app, tap "Sign in with Google" and use the details above. Google will ask you to choose your own password the first time you sign in.',
            )
            ->line('If you did not expect this email, please contact your organisation\'s missions desk.');
    }
}
