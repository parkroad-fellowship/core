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
        Schema::create('gift_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_id')->constrained();
            $table->foreignId('member_id')->constrained();
            $table->timestamps();

            $table->unique(['gift_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_member');
    }
};
