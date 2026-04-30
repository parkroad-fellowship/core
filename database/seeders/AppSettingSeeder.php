<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Organization
            ['group' => 'organization', 'key' => 'organization.excluded_emails', 'value' => '[]', 'type' => 'array'],
            ['group' => 'organization', 'key' => 'organization.head_office_latitude', 'value' => '-1.2906674', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.head_office_longitude', 'value' => '36.7690094', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.org_email_domain', 'value' => 'example.org', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.google_workspace_temp_password', 'value' => '', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.telescope_emails', 'value' => '[]', 'type' => 'array'],
            ['group' => 'organization', 'key' => 'organization.media_cdn_domain', 'value' => '', 'type' => 'string'],

            // Desk emails
            ['group' => 'desk_emails', 'key' => 'desk_emails.missions', 'value' => '["missions@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.chairpersons', 'value' => '["chairperson@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.treasurers', 'value' => '["treasurer@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.prayer', 'value' => '["prayer@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.follow_up', 'value' => '["followup@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.music', 'value' => '["music@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.organising_secretary', 'value' => '["secretary@example.org"]', 'type' => 'array'],
            ['group' => 'desk_emails', 'key' => 'desk_emails.vice_chairpersons', 'value' => '["vicechair@example.org"]', 'type' => 'array'],

            // App stores
            ['group' => 'app_stores', 'key' => 'app_stores.android_url', 'value' => '', 'type' => 'string'],
            ['group' => 'app_stores', 'key' => 'app_stores.ios_url', 'value' => '', 'type' => 'string'],
            ['group' => 'app_stores', 'key' => 'app_stores.huawei_url', 'value' => '', 'type' => 'string'],
            ['group' => 'app_stores', 'key' => 'app_stores.huawei_app_id', 'value' => '', 'type' => 'string'],
            ['group' => 'app_stores', 'key' => 'app_stores.leadership_android_url', 'value' => '', 'type' => 'string'],

            // Africa's Talking
            ['group' => 'africas_talking', 'key' => 'africas_talking.callback_url', 'value' => '', 'type' => 'string'],
            ['group' => 'africas_talking', 'key' => 'africas_talking.from', 'value' => '', 'type' => 'string'],
            ['group' => 'africas_talking', 'key' => 'africas_talking.missions_desk', 'value' => '', 'type' => 'string'],
            ['group' => 'africas_talking', 'key' => 'africas_talking.os_desk', 'value' => '', 'type' => 'string'],

            // General
            ['group' => 'general', 'key' => 'general.executive_committee_roles', 'value' => json_encode([
                'chairperson', 'vice chairperson', 'organising secretary', 'missions secretary',
                'follow-up secretary', 'treasurer', 'prayer secretary', 'music secretary',
            ]), 'type' => 'array'],
            ['group' => 'general', 'key' => 'general.global_group', 'value' => 'All', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }
}
