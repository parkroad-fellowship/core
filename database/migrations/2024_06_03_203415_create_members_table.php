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
            $table->foreignId('marital_status_id')->nullable()->constrained();
            $table->foreignId('profession_id')->nullable()->constrained();
            $table->foreignId('church_id')->nullable()->constrained();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number')->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('personal_email')->unique();

            $table->tinyInteger('gender')->nullable();
            $table->string('postal_address')->nullable();
            $table->text('residence')->nullable();
            $table->integer('year_of_salvation')->nullable();
            $table->boolean('church_volunteer')->default(false);
            $table->string('pastor')->nullable();
            $table->text('profession_institution')->nullable();
            $table->text('profession_location')->nullable();
            $table->text('profession_contact')->nullable();
            $table->boolean('accept_terms')->default(false);
            $table->boolean('approved')->default(false);
            $table->boolean('is_invited')->default(false);
            $table->longText('bio')->nullable();
            $table->text('linked_in_url')->nullable();

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
