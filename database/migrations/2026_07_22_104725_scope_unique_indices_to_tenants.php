<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Unique indices that must be scoped to tenant_id to prevent cross-tenant conflicts.
     *
     * @var list<array{table: string, old: string, columns: string[], new: string}>
     */
    private array $indicesToScope = [
        [
            'table' => 'cohorts',
            'old' => 'cohorts_slug_unique',
            'columns' => ['tenant_id', 'slug'],
            'new' => 'cohorts_tenant_id_slug_unique',
        ],
        [
            'table' => 'cohorts',
            'old' => 'cohorts_start_date_unique',
            'columns' => ['tenant_id', 'start_date'],
            'new' => 'cohorts_tenant_id_start_date_unique',
        ],
        [
            'table' => 'courses',
            'old' => 'courses_slug_unique',
            'columns' => ['tenant_id', 'slug'],
            'new' => 'courses_tenant_id_slug_unique',
        ],
        [
            'table' => 'lessons',
            'old' => 'lessons_slug_unique',
            'columns' => ['tenant_id', 'slug'],
            'new' => 'lessons_tenant_id_slug_unique',
        ],
        [
            'table' => 'letters',
            'old' => 'letters_slug_unique',
            'columns' => ['tenant_id', 'slug'],
            'new' => 'letters_tenant_id_slug_unique',
        ],
        [
            'table' => 'modules',
            'old' => 'modules_slug_unique',
            'columns' => ['tenant_id', 'slug'],
            'new' => 'modules_tenant_id_slug_unique',
        ],
        [
            'table' => 'app_settings',
            'old' => 'app_settings_key_unique',
            'columns' => ['tenant_id', 'key'],
            'new' => 'app_settings_tenant_id_key_unique',
        ],
        [
            'table' => 'students',
            'old' => 'students_name_unique',
            'columns' => ['tenant_id', 'name'],
            'new' => 'students_tenant_id_name_unique',
        ],
        [
            'table' => 'payments',
            'old' => 'payments_access_code_unique',
            'columns' => ['tenant_id', 'access_code'],
            'new' => 'payments_tenant_id_access_code_unique',
        ],
        [
            'table' => 'payments',
            'old' => 'payments_reference_unique',
            'columns' => ['tenant_id', 'reference'],
            'new' => 'payments_tenant_id_reference_unique',
        ],
        [
            'table' => 'payment_instructions',
            'old' => 'payment_instructions_requisition_id_unique',
            'columns' => ['tenant_id', 'requisition_id'],
            'new' => 'payment_instructions_tenant_id_requisition_id_unique',
        ],
    ];

    public function up(): void
    {
        foreach ($this->indicesToScope as $config) {
            if (!Schema::hasIndex($config['table'], $config['old'])) {
                continue;
            }

            Schema::table($config['table'], function ($table) use ($config) {
                $table->dropUnique($config['old']);
                $table->unique($config['columns'], $config['new']);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indicesToScope as $config) {
            if (!Schema::hasIndex($config['table'], $config['new'])) {
                continue;
            }

            Schema::table($config['table'], function ($table) use ($config) {
                $table->dropUnique($config['new']);
                $table->unique([$config['columns'][1]], $config['old']);
            });
        }
    }
};
