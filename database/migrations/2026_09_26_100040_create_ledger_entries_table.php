<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('tenant_id', 36)->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreignId('financial_account_id')->constrained();
            $table->foreignId('ledger_category_id')->constrained();
            $table->unsignedTinyInteger('flow');
            $table->unsignedTinyInteger('channel')->nullable();
            $table->unsignedBigInteger('amount');
            $table->date('transacted_on');

            $table->string('counterparty')->nullable();
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('receipt_number')->nullable();

            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('giver_email')->nullable();
            $table->string('giver_phone')->nullable();

            // Where the money came from or went to. Exactly one source is set for auto-posted lines.
            $table->foreignId('accounting_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pledge_installment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('allocation_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_transfer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_import_id')->nullable()->constrained()->nullOnDelete();

            // Idempotency key for auto-posted lines, e.g. "payment:12:gift" or "import:<hash>".
            $table->string('source_key')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['tenant_id', 'receipt_number']);
            $table->unique(['tenant_id', 'source_key']);
            $table->index(['tenant_id', 'financial_account_id', 'transacted_on']);
            $table->index(['tenant_id', 'ledger_category_id', 'transacted_on']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
