<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\MissionSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'mission_id',
    'facilitator_id',
    'speaker_id',
    'class_group_id',
    'starts_at',
    'ends_at',
    'notes',
    'order',
])]
class MissionSession extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MissionSessionFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasULID;
    use InteractsWithMedia;
    use SoftDeletes;

    public const INCLUDES = [
        'mission',
        'facilitator',
        'speaker',
        'classGroup',
        'media',
        'transcripts',
        'transcripts.media',
    ];

    public const SORTS = ['created_at', 'updated_at', 'starts_at', 'ends_at', 'order'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('mission_ulid', function ($query, $value) {
                $query->where('mission_id', Mission::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('facilitator_ulid', function ($query, $value) {
                $query->where('facilitator_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('speaker_ulid', function ($query, $value) {
                $query->where('speaker_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('class_group_ulid', function ($query, $value) {
                $query->where('class_group_id', ClassGroup::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    public const SESSION_AUDIOS = 'session-audios';

    public const LIVE_RECORDINGS = 'session-live-recordings';

    public const MEDIA_COLLECTIONS = [
        self::SESSION_AUDIOS,
        self::LIVE_RECORDINGS,
    ];

    public function casts()
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(related: Member::class, foreignKey: 'facilitator_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function speaker(): BelongsTo
    {
        return $this->belongsTo(related: Member::class, foreignKey: 'speaker_id');
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::SESSION_AUDIOS);
    }

    public function transcripts(): MorphMany
    {
        return $this->morphMany(related: Transcript::class, name: 'transcriptable');
    }
}
