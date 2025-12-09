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
        Schema::create('bible_chapters', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('bible_translation_id')->constrained();
            $table->foreignId('bible_book_id')->constrained();

            $table->integer('chapter_number');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bible_chapters');
    }
};
