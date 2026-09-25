<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the pledge tables and two older tables in line with every other tenant table:
 * a required tenant_id foreign key, unique ULIDs, and money as whole KES integers.
 */
return new class extends Migration {
    private const PLEDGE_TABLES = ['pledges', 'pledge_installments', 'pledge_reminders'];

    public function up(): void
    {
        foreach (self::PLEDGE_TABLES as $table) {
            if (DB::table($table)->whereNull('tenant_id')->exists()) {
                throw new RuntimeException("{$table} has rows without a tenant_id; assign them before migrating.");
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('tenant_id', 36)->nullable(false)->change();
                $blueprint->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }

        foreach (['pledges', 'pledge_installments'] as $table) {
            DB::table($table)->update(['amount' => DB::raw('round(amount)')]);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('amount')->change();
            });
        }

        Schema::table('mission_sessions', fn(Blueprint $table) => $table->unique('ulid'));
        Schema::table('payment_types', fn(Blueprint $table) => $table->unique('ulid'));
    }

    public function down(): void
    {
        Schema::table('payment_types', fn(Blueprint $table) => $table->dropUnique(['ulid']));
        Schema::table('mission_sessions', fn(Blueprint $table) => $table->dropUnique(['ulid']));

        foreach (['pledges', 'pledge_installments'] as $table) {
            Schema::table($table, fn(Blueprint $blueprint) => $blueprint->decimal('amount', 12, 2)->change());
        }

        foreach (self::PLEDGE_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['tenant_id']);
                $blueprint->string('tenant_id', 36)->nullable()->change();
            });
        }
    }
};
