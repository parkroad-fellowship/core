<?php

use App\Enums\PRFPaymentStatus;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('payment_type_id')->constrained();
            $table->foreignId('member_id')->constrained();

            $table->bigInteger('amount');
            $table->tinyInteger('payment_status')->default(PRFPaymentStatus::PENDING);
            $table->string('reference')->nullable()->unique();
            $table->string('access_code')->nullable()->unique();
            $table->string('authorization_url')->nullable();

            $table->json('transaction_meta')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
