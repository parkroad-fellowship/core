<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MissionSessionTranscript extends Model
{
    use HasUlid;
    use SoftDeletes;

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

    public function missionSession()
    {
        return $this->belongsTo(MissionSession::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
