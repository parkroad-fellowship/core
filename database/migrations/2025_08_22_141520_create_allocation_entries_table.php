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
        Schema::create('allocation_entries', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('accounting_event_id')->constrained();
            $table->foreignId('requisition_id')->nullable()->constrained();
            $table->foreignId('expense_category_id')->nullable()->constrained();
            $table->foreignId('member_id')->constrained(); // Person who recorded the entry

            // enum PRFEntryType
            $table->tinyInteger('entry_type')->unsigned();
            $table->bigInteger('amount');
            // enum PRFTransactionType
            $table->tinyInteger('charge_type')->unsigned()->nullable();
            $table->bigInteger('unit_cost');
            $table->bigInteger('quantity');
            $table->integer('charge');
            $table->string('narration');

            $table->longText('confirmation_message')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['accounting_event_id', 'entry_type']);
            $table->index(['member_id', 'entry_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocation_entries');
    }
};
