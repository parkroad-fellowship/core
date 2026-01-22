<?php

namespace App\Models;

use Database\Factories\SpeakerFactory;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Speaker extends Model
{
    /** @use HasFactory<SpeakerFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'phone_number',
        'email',
        'title',
        'bio',
    ];

    public function eventSpeakers()
    {
        return $this->hasMany(EventSpeaker::class);
    }
}
