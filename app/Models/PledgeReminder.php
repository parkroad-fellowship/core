<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\PledgeReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Audit ledger of reminder notices issued for a pledge's due date. Rows exist
 * to guarantee a pledge's due date is only reminded about once, and to allow
 * safe re-runs and retries of the reminder dispatch command.
 *
 * @use HasFactory<PledgeReminderFactory>
 */
class PledgeReminder extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public const INCLUDES = [
        'pledge',
    ];

    public const SORTS = ['created_at', 'due_on'];

    protected $fillable = [
        'pledge_id',
        'due_on',
        'remind_on',
        'channel',
        'sent_at',
    ];

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
