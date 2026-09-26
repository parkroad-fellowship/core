<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_categories', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->string('tenant_id', 36)->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('name');
            // Stable handle for categories the app posts to automatically (e.g. "charge.transaction_costs").
            $table->string('code')->nullable();
            $table->unsignedTinyInteger('kind');
            $table->unsignedTinyInteger('responsible_desk')->nullable();
            $table->string('statement_line')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'code']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_categories');
    }
};
