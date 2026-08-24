<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\MissionFaqCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class MissionFaqCategory extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<MissionFaqCategoryFactory> */
    use BelongsToTenant, HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public const INCLUDES = [];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    public function missionFaqs()
    {
        return $this->hasMany(MissionFaq::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
