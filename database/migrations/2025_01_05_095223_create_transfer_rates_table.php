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
        Schema::create('transfer_rates', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->tinyInteger('transaction_type'); // app/Enums/PRFMpesaTransactionType.php
            $table->integer('min_amount');
            $table->integer('max_amount');
            $table->integer('charge');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_type', 'min_amount', 'max_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_rates');
    }
};
