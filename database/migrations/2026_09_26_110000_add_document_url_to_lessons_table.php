<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A document lesson can link to a PDF hosted elsewhere instead of uploading it, like video and
 * audio lessons already can.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('lessons', 'document_url')) {
            Schema::table('lessons', fn(Blueprint $table) => $table
                ->string('document_url')
                ->nullable()
                ->after('audio_url'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lessons', 'document_url')) {
            Schema::table('lessons', fn(Blueprint $table) => $table->dropColumn('document_url'));
        }
    }
};
