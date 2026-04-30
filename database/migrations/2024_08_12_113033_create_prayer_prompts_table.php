<?php

use App\Enums\PRFActiveStatus;
use App\Enums\PRFPromptFrequency;
use App\Enums\PRFPromptTime;
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
        Schema::create('prayer_prompts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->text('description');
            $table->tinyInteger('frequency')->default(PRFPromptFrequency::WEEKLY);
            $table->tinyInteger('day_of_week');
            $table->tinyInteger('time_of_day')->default(PRFPromptTime::MORNING);
            $table->tinyInteger('is_active')->default(PRFActiveStatus::ACTIVE);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_prompts');
    }
};
