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
        Schema::create('student_enquiries', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('student_id')->comment('The initiating student')->constrained();
            $table->foreignId('mission_faq_id')->comment('If this was triggered by an FAQ')->nullable()->constrained();

            $table->longText('content');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enquiries');
    }
};
