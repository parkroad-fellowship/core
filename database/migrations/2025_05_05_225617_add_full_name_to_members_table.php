<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('last_name');
        });

        // Populate the full_name field with combined first_name and last_name
        // Using PostgreSQL concatenation syntax
        DB::statement("UPDATE members SET full_name = first_name || ' ' || last_name");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
