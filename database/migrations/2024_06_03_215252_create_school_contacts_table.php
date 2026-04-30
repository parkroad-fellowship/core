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
        Schema::create('school_contacts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('school_id')->constrained();
            $table->foreignId('contact_type_id')->constrained();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_contacts');
    }
};
