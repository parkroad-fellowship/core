<?php

use App\Enums\PRFMissionGroundSuggestionStatus;
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
        Schema::create('mission_ground_suggestions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('suggestor_id')->constrained('members');
            $table->string('name')->comment('Name of the mission ground');
            $table->string('contact_person');
            $table->string('contact_number');
            $table->tinyInteger('status')->default(PRFMissionGroundSuggestionStatus::PENDING);
            $table->longText('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_ground_suggestions');
    }
};
