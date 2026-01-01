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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('accounting_event_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->unsignedBigInteger('charge')->default(0);
            $table->bigInteger('deficit_amount')->default(0);
            $table->longText('confirmation_message');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
