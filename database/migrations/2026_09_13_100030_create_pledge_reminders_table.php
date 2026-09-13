<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pledge_reminders', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();

            $table->string('tenant_id', 36)->nullable()->index();

            $table->foreignId('pledge_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->date('remind_on');
            $table->string('channel')->default('mail');
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pledge_reminders');
    }
};
