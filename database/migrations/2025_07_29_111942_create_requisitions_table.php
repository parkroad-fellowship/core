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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('member_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->bigInteger('requisitionable_id');
            $table->tinyInteger('requisitionable_type')->index();

            $table->date('requisition_date');
            $table->tinyInteger('requisition_desk')->index();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();

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
