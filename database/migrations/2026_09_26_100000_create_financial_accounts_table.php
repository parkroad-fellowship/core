<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('tenant_id', 36)->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('name');
            $table->unsignedTinyInteger('type');
            $table->string('identifier')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unique(['tenant_id', 'name']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_accounts');
    }
};
