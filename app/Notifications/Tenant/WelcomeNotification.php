<?php

namespace App\Notifications\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Welcome to {$this->tenant->name}")
            ->line("You have been added as an admin to {$this->tenant->name}.")
            ->line("Your fellowship's subdomain is: {$this->tenant->slug}.".config('tenancy.identification.central_domains')[0] ?? '')
            ->action('Open Dashboard', url('/admin'));
    }
}
