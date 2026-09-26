<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\MissionFaqFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'question',
    'answer',
    'mission_faq_category_id',
])]
class MissionFaq extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MissionFaqFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'missionFaqCategory',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::callback('mission_faq_category_ulid', function ($query, $value) {
                $query->where(
                    'mission_faq_category_id',
                    MissionFaqCategory::query()->select('id')->where('ulid', $value)->limit(1),
                );
            }),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query->whereLike('question', "%{$value}%")->orWhereLike('answer', "%{$value}%");
                });
            }),
        ];
    }

    /**
     * @return HasMany<StudentEnquiry, $this>
     */
    public function studentEnquiries(): HasMany
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    /**
     * @return BelongsTo<MissionFaqCategory, $this>
     */
    public function missionFaqCategory(): BelongsTo
    {
        return $this->belongsTo(MissionFaqCategory::class);
    }
}
