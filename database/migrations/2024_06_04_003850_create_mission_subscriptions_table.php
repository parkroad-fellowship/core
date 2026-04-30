<?php

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
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
        Schema::create('mission_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('status')->default(PRFMissionSubscriptionStatus::PENDING);
            $table->tinyInteger('mission_role')->default(PRFMissionRole::MEMBER);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mission_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_subscriptions');
    }
};
