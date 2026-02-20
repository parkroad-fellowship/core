<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PRFEventHandler extends Model
{
    use HasUlid;
    use SoftDeletes;

    public $table = 'prf_event_handlers';

    protected $fillable = [
        'ulid',
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
