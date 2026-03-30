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

class CohortLetter extends Model implements HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'cohort',
        'letter',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    protected $fillable = [
        'ulid',
        'cohort_id',
        'letter_id',
    ];

    public function cohort()
    {
        return $this->belongsTo(Cohort::class);
    }

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public static function filters(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
