<?php

use App\Enums\PRFAccountEventStatus;
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
        Schema::create('accounting_events', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->bigInteger('accounting_eventable_id');
            $table->tinyInteger('accounting_eventable_type')->index();

            $table->tinyInteger('responsible_desk')->index();

            $table->text('name');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->tinyInteger('status')->default(PRFAccountEventStatus::PENDING);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_events');
    }
};
