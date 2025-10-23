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
        Schema::table('student_enquiry_replies', function (Blueprint $table) {
            $table->boolean('is_from_chat_bot')->default(false)->after('content');
            $table->json('chat_bot_payload')->nullable()->after('is_from_chat_bot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enquiry_replies', function (Blueprint $table) {
            $table->dropColumn(['is_from_chat_bot', 'chat_bot_payload']);
        });
    }
};
