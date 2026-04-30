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
        Schema::create('mission_social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();

            $table->foreignId('mission_id')
                ->constrained('missions')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'processing_images',
                'images_processed',
                'creating_video',
                'video_created',
                'uploading_video',
                'video_uploaded',
                'sending_to_social',
                'completed',
                'failed',
            ])->default('pending');

            $table->json('image_urls')->nullable();
            $table->text('video_path')->nullable();
            $table->text('video_url')->nullable();
            $table->string('social_media_post_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('images_processed_at')->nullable();
            $table->timestamp('video_created_at')->nullable();
            $table->timestamp('video_uploaded_at')->nullable();
            $table->timestamp('sent_to_social_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['mission_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_social_media_posts');
    }
};
