<?php

namespace App\Models;

use App\Observers\EventSubscriptionObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(EventSubscriptionObserver::class)]
class EventSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\EventSubscriptionFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'prf_event_id',
        'member_id',
        'number_of_attendees',
    ];

    const INCLUDES = [
        'prfEvent',
        'prfEvent.posters',
        'prfEvent.loggedInMemberEventSubscription',
        'member',
    ];

    public function prfEvent()
    {
        return $this->belongsTo(PRFEvent::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('start_date', '<', now()->toDateString());
    }
}
