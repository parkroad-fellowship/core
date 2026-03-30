<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                $table->nullableMorphs('subject', 'subject');
                $table->string('event')->nullable();
                $table->nullableMorphs('causer', 'causer');
                $table->json('properties')->nullable();
                $table->json('attribute_changes')->nullable();
                $table->uuid('batch_uuid')->nullable();
                $table->timestamps();
                $table->index('log_name');
            });

            return;
        }

        Schema::table('activity_log', function (Blueprint $table): void {
            if (! Schema::hasColumn('activity_log', 'event')) {
                $table->string('event')->nullable()->after('subject_type');
            }

            if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
                $table->json('attribute_changes')->nullable()->after('properties');
            }

            if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->uuid('batch_uuid')->nullable()->after('attribute_changes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            if (Schema::hasColumn('activity_log', 'batch_uuid')) {
                $table->dropColumn('batch_uuid');
            }

            if (Schema::hasColumn('activity_log', 'attribute_changes')) {
                $table->dropColumn('attribute_changes');
            }

            if (Schema::hasColumn('activity_log', 'event')) {
                $table->dropColumn('event');
            }
        });
    }
};
