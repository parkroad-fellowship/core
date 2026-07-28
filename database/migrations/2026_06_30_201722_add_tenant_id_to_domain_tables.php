<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'accounting_events',
        'activity_log',
        'allocation_entries',
        'announcement_groups',
        'announcements',
        'api_clients',
        'app_settings',
        'bible_books',
        'bible_chapters',
        'bible_translations',
        'bible_verses',
        'budget_estimate_entries',
        'budget_estimates',
        'chat_bots',
        'churches',
        'class_groups',
        'cohort_letters',
        'cohort_missions',
        'cohorts',
        'connected_accounts',
        'contact_types',
        'course_groups',
        'course_members',
        'course_modules',
        'courses',
        'debrief_notes',
        'department_member',
        'departments',
        'event_speakers',
        'event_subscriptions',
        'expense_categories',
        'expenses',
        'gift_member',
        'gifts',
        'group_members',
        'groups',
        'lesson_members',
        'lesson_modules',
        'lessons',
        'letters',
        'marital_statuses',
        'member_modules',
        'members',
        'memberships',
        'mission_expenses',
        'mission_faq_categories',
        'mission_faqs',
        'mission_ground_suggestions',
        'mission_offline_members',
        'mission_questions',
        'mission_session_transcripts',
        'mission_sessions',
        'mission_social_media_posts',
        'mission_subscriptions',
        'mission_types',
        'missions',
        'modules',
        'payment_instructions',
        'payment_types',
        'payments',
        'personal_access_tokens',
        'prayer_prompts',
        'prayer_requests',
        'prayer_responses',
        'prf_event_handlers',
        'prf_event_participants',
        'prf_events',
        'professions',
        'refunds',
        'requisition_items',
        'requisitions',
        'route_distances',
        'school_contacts',
        'school_terms',
        'schools',
        'sms_logs',
        'souls',
        'speakers',
        'spiritual_years',
        'student_enquiries',
        'student_enquiry_replies',
        'students',
        'transfer_rates',
        'users',
        'weather_forecasts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function ($table) {
                    $table->string('tenant_id', 36)->nullable()->after('id');
                    $table->index('tenant_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function ($table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
