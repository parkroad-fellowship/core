<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why a mission was rejected, cancelled or postponed. It used to overwrite the executive summary.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('missions', 'status_reason')) {
            Schema::table('missions', fn(Blueprint $table) => $table
                ->text('status_reason')
                ->nullable()
                ->after('status'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('missions', 'status_reason')) {
            Schema::table('missions', fn(Blueprint $table) => $table->dropColumn('status_reason'));
        }
    }
};
