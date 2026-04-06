<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;

class MissionFaq extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'question',
        'answer',
        'mission_faq_category_id',
    ];

    const INCLUDES = [
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
                    MissionFaqCategory::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1),
                );
            }),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query
                        ->whereLike('question', "%{$value}%")
                        ->orWhereLike('answer', "%{$value}%");
                });
            }),
        ];
    }

    public function studentEnquiries()
    {
        return $this->hasMany(StudentEnquiry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function missionFaqCategory()
    {
        return $this->belongsTo(MissionFaqCategory::class);
    }
}
