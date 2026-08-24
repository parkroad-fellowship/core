<?php

use App\Enums\PRFMorphType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mission_session_transcripts', function (Blueprint $table) {
            $table->bigInteger('transcriptable_id')->nullable()->after('mission_session_id');
            $table->tinyInteger('transcriptable_type')->nullable()->index()->after('transcriptable_id');
            $table->index(
                ['transcriptable_type', 'transcriptable_id'],
                'mission_session_transcripts_transcriptable_index',
            );

            $table->foreignId('mission_session_id')->nullable()->change();
        });

        DB::table('mission_session_transcripts')
            ->whereNotNull('mission_session_id')
            ->update([
                'transcriptable_id' => DB::raw('mission_session_id'),
                'transcriptable_type' => PRFMorphType::MISSION_SESSION->value,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mission_session_transcripts', function (Blueprint $table) {
            $table->dropIndex('mission_session_transcripts_transcriptable_index');
            $table->dropIndex(['transcriptable_type']);
            $table->dropColumn([
                'transcriptable_id',
                'transcriptable_type',
            ]);

            $table->foreignId('mission_session_id')->nullable(false)->change();
        });
    }
};
