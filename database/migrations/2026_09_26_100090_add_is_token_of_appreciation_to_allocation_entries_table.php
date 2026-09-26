<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('allocation_entries', function (Blueprint $table) {
            $table->boolean('is_token_of_appreciation')->default(false)->after('entry_type');
        });

        // Tokens were recorded without an expense category or requisition (AddTokenJob).
        DB::table('allocation_entries')
            ->whereNull('expense_category_id')
            ->whereNull('requisition_id')
            ->update(['is_token_of_appreciation' => true]);
    }

    public function down(): void
    {
        Schema::table('allocation_entries', function (Blueprint $table) {
            $table->dropColumn('is_token_of_appreciation');
        });
    }
};
