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

class SchoolContact extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'contact_type_id',
        'name',
        'email',
        'phone',
        'is_active',
        'preferred_name',
    ];

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
                $query->where(
                    'school_id',
                    School::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('contact_type_ulid', function ($query, $value) {
                $query->where(
                    'contact_type_id',
                    ContactType::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
