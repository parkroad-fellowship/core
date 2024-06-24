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
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('group_id')->constrained();
            $table->foreignId('member_id')->constrained();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['group_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
