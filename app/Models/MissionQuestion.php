<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class MissionQuestion extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'mission_id',
        'question',
    ];

    public const QUESTION_ANSWERS = 'question-answers';

    public const MEDIA_COLLECTIONS = [
        self::QUESTION_ANSWERS,
    ];

    const INCLUDES = [
        'mission',
        'questionMediaAnswers',
        'transcripts',
        'transcripts.media',
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
        ];
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::QUESTION_ANSWERS);

    }

    public function transcripts(): MorphMany
    {
        return $this->morphMany(
            related: Transcript::class,
            name: 'transcriptable',
        );
    }
}
