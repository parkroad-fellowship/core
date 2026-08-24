<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\SpeakerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Speaker extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<SpeakerFactory> */
    use BelongsToTenant;
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public const INCLUDES = [
        'eventSpeakers',
    ];

    public const SORTS = ['created_at', 'updated_at'];

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

    public static function filters(): array
    {
        return [];
    }
}
