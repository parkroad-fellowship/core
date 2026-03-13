<?php

namespace App\Models;

use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissionSocialMediaPost extends Model
{
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'status',
        'image_urls',
        'video_path',
        'video_url',
        'social_media_post_id',
        'error_message',
        'images_processed_at',
        'video_created_at',
        'video_uploaded_at',
        'sent_to_social_at',
    ];

    protected $casts = [
        'image_urls' => 'array',
        'images_processed_at' => 'datetime',
        'video_created_at' => 'datetime',
        'video_uploaded_at' => 'datetime',
        'sent_to_social_at' => 'datetime',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function updateStatus(string $status, array $data = []): void
    {
        $updateData = array_merge(['status' => $status], $data);
        $this->update($updateData);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return in_array($this->status, ['failed', 'pending']);
    }
}
