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
        Schema::create('mission_expenses', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('mission_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->bigInteger('amount_received');
            $table->bigInteger('amount_spent')->default(0);
            $table->bigInteger('token_amount')->default(0);
            $table->bigInteger('amount_to_refund')->default(0);
            $table->bigInteger('amount_refunded')->default(0);
            $table->boolean('is_refunded')->default(false);
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('refund_charge')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_expenses');
    }
};
