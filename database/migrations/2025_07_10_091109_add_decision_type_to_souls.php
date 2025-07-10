<?php

use App\Enums\PRFSoulDecisionType;
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
        Schema::table('souls', function (Blueprint $table) {
            $table
                ->tinyInteger('decision_type')
                ->default(PRFSoulDecisionType::SALVATION);
            $table->longText('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souls', function (Blueprint $table) {
            $table->dropColumn([
                'decision_type',
                'notes',
            ]);
        });
    }
};
