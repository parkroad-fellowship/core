<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\SchoolContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'school_id',
    'contact_type_id',
    'name',
    'email',
    'phone',
    'preferred_name',
])]
class SchoolContact extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<SchoolContactFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'school',
        'contactType',
    ];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('school_ulid', function ($query, $value) {
                $query->where('school_id', School::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('contact_type_ulid', function ($query, $value) {
                $query->where('contact_type_id', ContactType::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<ContactType, $this>
     */
    public function contactType(): BelongsTo
    {
        return $this->belongsTo(ContactType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
