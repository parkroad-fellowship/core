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
        Schema::create('budget_estimates', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->bigInteger('budget_estimatable_id');
            $table->tinyInteger('budget_estimatable_type');
            $table->bigInteger('grand_total')->default(0);

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
        Schema::dropIfExists('budget_estimates');
    }
};
