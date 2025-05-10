<?php

namespace App\Models;

use App\Observers\PrayerRequestObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PrayerRequestObserver::class)]
class PrayerRequest extends Model
{
    /** @use HasFactory<\Database\Factories\PrayerRequestFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'title',
        'description',
    ];

    const INCLUDES = [
        'member',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
