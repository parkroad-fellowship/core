<?php

use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pledges', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();

            $table->string('tenant_id', 36)->nullable()->index();

            $table->foreignId('member_id')->nullable()->constrained();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->decimal('amount', 12, 2);
            $table->tinyInteger('frequency')->default(PRFPledgeFrequency::MONTHLY->value);
            $table->date('start_date')->nullable();
            $table->date('next_due_on')->nullable();
            $table->date('last_fulfilled_on')->nullable();
            $table->tinyInteger('status')->default(PRFPledgeStatus::ACTIVE->value);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledges');
    }
};
