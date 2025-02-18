<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\EventSubscriptionFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'event_id',
        'member_id',
        'number_of_attendees',
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
