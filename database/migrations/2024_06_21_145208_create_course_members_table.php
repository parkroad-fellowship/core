<?php

use App\Enums\PRFCompletionStatus;
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
        Schema::create('course_members', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->float('percent_complete')->default(0);
            $table->tinyInteger('completion_status')->default(PRFCompletionStatus::INCOMPLETE);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_members');
    }
};
