<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFPledgeInstallmentMethod;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\PledgeInstallmentObserver;
use Database\Factories\PledgeInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A single recorded fulfillment against a pledge. This is the follow-through
 * history for a pledge and works for members as well as unregistered givers
 * (the pledge is the identity).
 *
 * @use HasFactory<PledgeInstallmentFactory>
 */
#[ObservedBy(PledgeInstallmentObserver::class)]
class PledgeInstallment extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = [
        'pledge',
        'payment',
        'recordedBy',
    ];

    public const SORTS = ['created_at', 'fulfilled_on'];

    protected $fillable = [
        'pledge_id',
        'amount',
        'fulfilled_on',
        'method',
        'payment_id',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'method' => PRFPledgeInstallmentMethod::class,
            'fulfilled_on' => 'date',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('ulid'),
            AllowedFilter::exact('method'),
            AllowedFilter::callback('pledge_ulid', function ($query, string $value) {
                $query->where('pledge_id', Pledge::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
