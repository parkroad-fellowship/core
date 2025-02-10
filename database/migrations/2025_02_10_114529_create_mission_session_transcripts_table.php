<?php

use App\Enums\PRFTranscriptionStatus;
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
        Schema::create('mission_session_transcripts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('mission_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();

            $table->text('transcription_status_url');
            $table->text('transcription_content_url')->nullable();
            $table->tinyInteger('status')->default(PRFTranscriptionStatus::NOT_STARTED);
            $table->longText('transcription_content')->nullable();

            $table->json('transcription_request_meta')->nullable();
            $table->json('transcription_meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_session_transcripts');
    }
};
