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
        Schema::create('student_enquiry_replies', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('student_enquiry_id')->constrained();

            $table->bigInteger('commentorable_id')->unsigned();
            $table->tinyInteger('commentorable_type')->unsigned();

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
        Schema::dropIfExists('student_enquiry_replies');
    }
};
