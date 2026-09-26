<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipt_deliveries', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('tenant_id', 36)->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignId('ledger_entry_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('channel');
            $table->string('recipient');
            $table->unsignedTinyInteger('status');
            $table->text('share_url')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_deliveries');
    }
};
