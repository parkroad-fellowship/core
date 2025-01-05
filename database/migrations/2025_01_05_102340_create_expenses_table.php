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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('member_id')->constrained();

            $table->bigInteger('expensable_id')->unsigned();
            $table->tinyInteger('expensable_type')->unsigned();
            $table->bigInteger('amount');
            $table->integer('charge');
            $table->longText('confirmation_message')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
