<?php

use App\Enums\PRFPledgeInstallmentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pledge_installments', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();

            $table->string('tenant_id', 36)->nullable()->index();

            $table->foreignId('pledge_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('fulfilled_on');
            $table->tinyInteger('method')->default(PRFPledgeInstallmentMethod::MANUAL->value);

            $table->foreignId('payment_id')->nullable()->constrained();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledge_installments');
    }
};
