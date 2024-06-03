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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('marital_status_id')->constrained();
            $table->foreignId('profession_id')->constrained();
            $table->foreignId('church_id')->constrained();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('postal_address')->nullable();
            $table->string('phone_number')->unique();
            $table->string('email')->unique();
            $table->text('residence');
            $table->integer('year_of_salvation')->nullable();
            $table->boolean('church_volunteer')->default(false);
            $table->string('pastor');
            $table->text('profession_institution')->nullable();
            $table->text('profession_location')->nullable();
            $table->text('profession_contact')->nullable();
            $table->boolean('accept_terms')->default(false);
            $table->boolean('approved')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
