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
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('requisition_id')->constrained();
            $table->foreignId('accounting_event_id')->constrained();

            $table->bigInteger('amount');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['accounting_event_id', 'requisition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
