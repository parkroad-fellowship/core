<?php

use App\Enums\PRFActiveStatus;
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
        Schema::create('mission_faq_categories', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->string('name');
            $table->tinyInteger('is_active')->default(PRFActiveStatus::ACTIVE);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_faq_categories');
    }
};
