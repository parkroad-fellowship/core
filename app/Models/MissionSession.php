<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\MissionSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;

class MissionSession extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    /** @use HasFactory<MissionSessionFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'facilitator_id',
        'speaker_id',
        'class_group_id',
        'starts_at',
        'ends_at',
        'notes',
        'order',
    ];

    public const INCLUDES = [
        'mission',
        'facilitator',
        'speaker',
        'classGroup',
        'media',
        'missionSessionTranscripts',
        'missionSessionTranscripts.media',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('mission_ulid', function ($query, $value) {
                $query->where(
                    'mission_id',
                    Mission::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('facilitator_ulid', function ($query, $value) {
                $query->where(
                    'facilitator_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('speaker_ulid', function ($query, $value) {
                $query->where(
                    'speaker_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('class_group_ulid', function ($query, $value) {
                $query->where(
                    'class_group_id',
                    ClassGroup::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
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

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function facilitator()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'facilitator_id',
        );
    }

    public function speaker()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'speaker_id',
        );
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::SESSION_AUDIOS);
    }

    public function missionSessionTranscripts()
    {
        return $this->hasMany(MissionSessionTranscript::class);
    }
}
