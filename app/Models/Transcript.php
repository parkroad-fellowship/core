<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Transcript extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $table = 'mission_session_transcripts';

    public const INCLUDES = [
        'media',
        'transcriptable',
        'missionSession',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    protected $fillable = [
        'mission_session_id',
        'transcriptable_id',
        'transcriptable_type',
        'media_id',
        'transcription_status_url',
        'transcription_content_url',
        'status',
        'transcription_content',
        'transcription_request_meta',
        'transcription_meta',
    ];

    protected function casts(): array
    {
        return [
            'transcriptable_type' => 'integer',
            'transcription_request_meta' => 'array',
            'transcription_meta' => 'array',
        ];
    }

    public static function permissionEntity(): string
    {
        return 'mission session transcript';
    }

    public function transcriptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function missionSession(): BelongsTo
    {
        return $this->belongsTo(MissionSession::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
