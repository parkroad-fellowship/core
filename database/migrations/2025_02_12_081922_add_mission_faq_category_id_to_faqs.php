<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mission_faqs', function (Blueprint $table) {
            $table->foreignId('mission_faq_category_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mission_faqs', function (Blueprint $table) {
            $table->dropForeign(['mission_faq_category_id']);
            $table->dropColumn('mission_faq_category_id');
        });
    }
};
