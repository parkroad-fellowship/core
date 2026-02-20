<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSpeaker extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'prf_event_id',
        'speaker_id',
        'topic',
        'description',
        'comments',
    ];

    public function event()
    {
        return $this->belongsTo(
            PRFEvent::class,
            'prf_event_id'
        );
    }

    public function speaker()
    {
        return $this->belongsTo(Speaker::class);
    }
}
