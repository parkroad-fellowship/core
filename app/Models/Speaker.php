<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\SpeakerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'name',
    'phone_number',
    'email',
    'title',
    'bio',
])]
class Speaker extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<SpeakerFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = [
        'eventSpeakers',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return HasMany<EventSpeaker, $this>
     */
    public function eventSpeakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class);
    }

    public static function filters(): array
    {
        return [];
    }
}
