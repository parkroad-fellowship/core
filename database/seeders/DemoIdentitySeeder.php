<?php

namespace Database\Seeders;

use App\Enums\PRFMemberEmailMode;
use App\Models\AppSetting;
use Illuminate\Database\Seeder;

/**
 * Outside production the demo tenant uses organisation-domain sign-in on example.org, a domain
 * reserved for documentation (RFC 2606), so seeded addresses can never reach a real inbox.
 */
class DemoIdentitySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        AppSetting::set(
            'organization.member_email_mode',
            PRFMemberEmailMode::ORGANISATION_DOMAIN->value,
            'organization',
            'integer',
        );
        AppSetting::set('organization.org_email_domain', 'example.org', 'organization');
    }
}
