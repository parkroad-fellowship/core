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
        Schema::create('budget_estimate_entries', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('budget_estimate_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('expense_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('item_name');
            $table->bigInteger('unit_price');
            $table->integer('quantity');
            $table->bigInteger('total_price');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_estimate_entries');
    }
};
