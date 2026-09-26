<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMembershipType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'ulid',
    'member_id',
    'spiritual_year_id',
    'type',
    'approved',
    'amount',
])]
class Membership extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'member',
        'spiritualYear',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
            'type' => PRFMembershipType::class,
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<SpiritualYear, $this>
     */
    public function spiritualYear(): BelongsTo
    {
        return $this->belongsTo(SpiritualYear::class);
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
