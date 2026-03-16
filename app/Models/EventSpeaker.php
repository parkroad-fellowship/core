<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSpeaker extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public const INCLUDES = ['prfEvent', 'speaker'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    protected $fillable = [
        'prf_event_id',
        'speaker_id',
        'topic',
        'description',
        'comments',
    ];

    public function prfEvent()
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
