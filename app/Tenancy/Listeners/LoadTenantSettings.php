<?php

namespace App\Tenancy\Listeners;

use App\Contracts\Services\FirebaseManagerInterface;
use App\Models\AppSetting;
use App\Services\AI\LaravelAIService;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Notifications\ChannelManager;

/**
 * Runs after stancl's BootstrapTenancy: copies the tenant's organisation settings and
 * integration credentials into config(). Registered in TenancyServiceProvider::events()
 * (kept out of app/Listeners so event discovery does not register it a second time).
 */
class LoadTenantSettings
{
    public function __construct(
        private readonly TenantIntegrations $integrations,
    ) {}

    public function handle(): void
    {
        config([
            'prf.app.global_group' => AppSetting::get('general.global_group', 'All'),
            'prf.app.excluded_emails' => AppSetting::get('organization.excluded_emails', []),
            'prf.app.head_office.latitude' => AppSetting::get('organization.head_office_latitude', '-1.2906674'),
            'prf.app.head_office.longitude' => AppSetting::get('organization.head_office_longitude', '36.7690094'),

            'prf.app.missions_desk.emails' => AppSetting::get('desk_emails.missions', []),
            'prf.app.chairpersons_desk.emails' => AppSetting::get('desk_emails.chairpersons', []),
            'prf.app.treasurers_desk.emails' => AppSetting::get('desk_emails.treasurers', []),
            'prf.app.prayer_desk.emails' => AppSetting::get('desk_emails.prayer', []),
            'prf.app.follow_up_desk.emails' => AppSetting::get('desk_emails.follow_up', []),
            'prf.app.music_desk.emails' => AppSetting::get('desk_emails.music', []),
            'prf.app.organising_secretary_desk.emails' => AppSetting::get('desk_emails.organising_secretary', []),
            'prf.app.vice_chairpersons_desk.emails' => AppSetting::get('desk_emails.vice_chairpersons', []),

            'prf.app.app_stores.android.url' => AppSetting::get('app_stores.android_url', ''),
            'prf.app.app_stores.ios.url' => AppSetting::get('app_stores.ios_url', ''),
            'prf.app.app_stores.huawei.url' => AppSetting::get('app_stores.huawei_url', ''),
            'prf.app.leadership_app.android.url' => AppSetting::get('app_stores.leadership_android_url', ''),
            'prf.app.leadership_app.ios.url' => AppSetting::get('app_stores.leadership_ios_url', ''),

            'prf.app.africas_talking.callback_url' => AppSetting::get('africas_talking.callback_url', ''),
            'prf.app.africas_talking.missions_desk' => AppSetting::get('africas_talking.missions_desk', ''),
            'prf.app.africas_talking.os_desk' => AppSetting::get('africas_talking.os_desk', ''),

            'prf.app.executive_committee.roles' => AppSetting::get('general.executive_committee_roles', []),
            'prf.app.camp_committee.emails' => [],
            'prf.app.telescope_emails' => AppSetting::get('organization.telescope_emails', []),
        ]);

        $this->integrations->load();

        // Clients built for the previous tenant (Firebase, cached notification channels) must not be reused.
        app(FirebaseManagerInterface::class)->reset();
        $channels = app(ChannelManager::class);

        if ($channels instanceof ChannelManager) {
            $channels->forgetDrivers();
        }
        LaravelAIService::forgetTenantProvider();
    }
}
