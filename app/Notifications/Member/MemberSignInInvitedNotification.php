<?php

namespace App\Notifications\Member;

use App\Models\Member;
use App\Notifications\BaseNotification;
use App\Settings\TenantSettings;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Personal-email tenants: tells a member which address to sign in with. Contains no secrets.
 */
class MemberSignInInvitedNotification extends BaseNotification
{
    public function __construct(
        public Member $member,
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

        return new MailMessage()
            ->subject("You're invited to {$organisation}")
            ->greeting("Hello {$this->member->first_name},")
            ->line("You now have access to the {$organisation} app.")
            ->line("Open the app, tap \"Sign in with Google\" and choose **{$this->member->personal_email}**.")
            ->line('No password is needed; your Google account is your login.');
    }
}
