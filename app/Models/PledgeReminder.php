<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\PledgeReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Audit ledger of reminder notices issued for a pledge's due date. Rows exist
 * to guarantee a pledge's due date is only reminded about once, and to allow
 * safe re-runs and retries of the reminder dispatch command.
 *
 */
#[Fillable([
    'pledge_id',
    'due_on',
    'remind_on',
    'channel',
    'sent_at',
])]
class PledgeReminder extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<PledgeReminderFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = [
        'pledge',
    ];

    public const SORTS = ['created_at', 'due_on'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'remind_on' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }
}
