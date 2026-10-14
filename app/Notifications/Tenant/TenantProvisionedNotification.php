<?php

namespace App\Notifications\Tenant;

use App\Enums\PRFMemberEmailMode;
use App\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a new tenant's first admin. Contains a password-reset link, never a password.
 * Sent synchronously (not queued) because it carries a reset token.
 */
class TenantProvisionedNotification extends Notification
{
    public function __construct(
        public Tenant $tenant,
        public PRFMemberEmailMode $memberEmailMode,
        private readonly string $passwordResetToken,
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
        $baseUrl = $this->tenantUrl();
        $resetUrl =
            $baseUrl
            . route(
                'password.reset',
                [
                    'token' => $this->passwordResetToken,
                    'email' => $notifiable->email,
                ],
                absolute: false,
            );

        return new MailMessage()
            ->subject("Welcome to {$this->tenant->name}")
            ->line("You are now an administrator of {$this->tenant->name}.")
            ->line("Your admin panel: {$baseUrl}/admin")
            ->line(
                'Members sign in with: ' . $this->memberEmailMode->getLabel() . '. '
                    . $this->memberEmailMode->getDescription(),
            )
            ->line(
                'Before members can pay, receive SMS or push notifications, add your own Paystack, SMS, Firebase and AI credentials under Settings → App Settings.',
            )
            ->action('Set your password', $resetUrl);
    }

    private function tenantUrl(): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $domain = $this->tenant->domains()->orderBy('id')->value('domain');

        return $domain ? "{$scheme}://{$domain}" : rtrim((string) config('app.url'), '/');
    }
}
