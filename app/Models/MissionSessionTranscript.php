<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MissionSessionTranscript extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public const INCLUDES = ['missionSession'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    protected $fillable = [
        'mission_session_id',
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
            'transcription_request_meta' => 'array',
            'transcription_meta' => 'array',
        ];
    }

    public function missionSession()
    {
        return $this->belongsTo(MissionSession::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
