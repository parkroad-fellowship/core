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
        Schema::create('payment_instructions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('requisition_id')
                ->constrained()
                ->cascadeOnDelete();

            // Payment method type: mpesa, bank, paybill
            $table->tinyInteger('payment_method')->index();

            // Common fields
            $table->string('recipient_name');
            $table->string('reference')->nullable(); // Optional reference/description

            // MPESA specific fields
            $table->bigInteger('mpesa_phone_number')->nullable();

            // Bank transfer fields
            $table->string('bank_name')->nullable();
            $table->bigInteger('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_swift_code')->nullable();

            // Paybill fields
            $table->bigInteger('paybill_number')->nullable();
            $table->string('paybill_account_number')->nullable();

            // Till fields
            $table->bigInteger('till_number')->nullable();

            // Amount
            $table->bigInteger('amount')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['requisition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_instructions');
    }
};
