<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ClassGroup extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'is_active',
        'institution_type',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => PRFActiveStatus::class,
            'institution_type' => PRFInstitutionType::class,
        ];
    }

    const INCLUDES = [
        'souls',
    ];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value) {
                $query->where('is_active', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value) {
                $query->whereIn('is_active', Arr::wrap($value));
            }),
            AllowedFilter::callback('institution_type', function ($query, $value) {
                $query->where('institution_type', $value);
            }),
        ];
    }

    public function souls()
    {
        return $this->hasMany(Soul::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
