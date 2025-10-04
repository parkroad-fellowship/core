<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PRFEventParticipant extends Model
{
    use HasUlid;
    use SoftDeletes;

    public $table = 'prf_event_participants';

    protected $fillable = [
        'prf_event_id',
        'member_id',
    ];

    public function prfEvent()
    {
        return $this->belongsTo(PRFEvent::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
