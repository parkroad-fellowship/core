<?php

namespace App\Tenancy\Listeners;

use App\Contracts\Services\FirebaseManagerInterface;
use App\Services\AI\LaravelAIService;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Notifications\ChannelManager;

/**
 * Clears tenant-owned credentials when tenancy ends, so long-running workers never
 * carry one tenant's keys into the next job.
 */
class ResetTenantSettings
{
    public function __construct(
        private readonly TenantIntegrations $integrations,
    ) {}

    public function handle(): void
    {
        $this->integrations->reset();

        // Clients built for the previous tenant (Firebase, cached notification channels) must not be reused.
        app(FirebaseManagerInterface::class)->reset();
        $channels = app(ChannelManager::class);

        if ($channels instanceof ChannelManager) {
            $channels->forgetDrivers();
        }
        LaravelAIService::forgetTenantProvider();
    }
}
