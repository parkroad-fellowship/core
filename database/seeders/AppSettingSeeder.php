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
            // Organization & Branding Assets
            ['group' => 'organization', 'key' => 'organization.name', 'value' => 'Parkroad Fellowship', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.logo_url', 'value' => '/images/default-logo.png', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.favicon_url', 'value' => '/favicon.ico', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'branding.primary_color', 'value' => '#1E40AF', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.excluded_emails', 'value' => '[]', 'type' => 'array'],
            ['group' => 'organization', 'key' => 'organization.head_office_latitude', 'value' => '-1.2906674', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.head_office_longitude', 'value' => '36.7690094', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.org_email_domain', 'value' => '', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.google_workspace_temp_password', 'value' => '', 'type' => 'string'],
            ['group' => 'organization', 'key' => 'organization.telescope_emails', 'value' => '[]', 'type' => 'array'],
            ['group' => 'organization', 'key' => 'organization.media_cdn_domain', 'value' => '', 'type' => 'string'],

            // Firebase
            ['group' => 'firebase', 'key' => 'firebase.service_account_json', 'value' => '', 'type' => 'string'],
            ['group' => 'firebase', 'key' => 'firebase.database_url', 'value' => '', 'type' => 'string'],

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
            ['group' => 'africas_talking', 'key' => 'africas_talking.username', 'value' => '', 'type' => 'string'],
            ['group' => 'africas_talking', 'key' => 'africas_talking.api_key', 'value' => '', 'type' => 'string'],

            // SMS
            ['group' => 'sms', 'key' => 'sms.default', 'value' => 'advanta', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'sms.advanta_base_url', 'value' => '', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'sms.advanta_api_key', 'value' => '', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'sms.advanta_partner_id', 'value' => '', 'type' => 'string'],
            ['group' => 'sms', 'key' => 'sms.advanta_short_code', 'value' => '', 'type' => 'string'],

            // General
            ['group' => 'general', 'key' => 'general.executive_committee_roles', 'value' => json_encode([
                'chairperson', 'vice chairperson', 'organising secretary', 'missions secretary',
                'follow-up secretary', 'treasurer', 'prayer secretary', 'music secretary',
            ]), 'type' => 'array'],
            ['group' => 'general', 'key' => 'general.global_group', 'value' => 'All', 'type' => 'string'],

            // Feature flags (single source of truth)
            ['group' => 'features', 'key' => 'feature.missions', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.finance', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.e_learning', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.prayer_requests', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.announcements', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.events', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.groups', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.member_management', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.courses', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'features', 'key' => 'feature.payments', 'value' => '0', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(
                ['tenant_id' => tenant('id'), 'key' => $setting['key']],
                ['tenant_id' => tenant('id'), ...$setting],
            );
        }
    }
}
