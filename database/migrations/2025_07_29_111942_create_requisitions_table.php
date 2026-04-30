<?php

use App\Enums\PRFApprovalStatus;
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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('accounting_event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('requisition_date');
            $table->tinyInteger('responsible_desk')->index();

            $table->foreignId('appointed_approver_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete()
                ->comment('The designated approver for this requisition (maker-checker)');
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete()
                ->comment('The member who actually approved/rejected this requisition');

            $table->timestamp('review_requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->tinyInteger('approval_status')->default(PRFApprovalStatus::PENDING)->index();
            $table->longText('approval_notes')->nullable();
            $table->longText('remarks')->nullable();

            $table->bigInteger('total_amount')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
