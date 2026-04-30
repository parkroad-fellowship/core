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
        Schema::create('announcement_groups', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('announcement_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['announcement_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_groups');
    }
};
