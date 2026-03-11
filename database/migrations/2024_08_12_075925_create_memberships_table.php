<?php

use App\Enums\PRFMembershipType;
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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('spiritual_year_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->tinyInteger('type')
                ->default(PRFMembershipType::FRIEND);
            $table->boolean('approved');
            $table->integer('amount');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['member_id', 'spiritual_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
