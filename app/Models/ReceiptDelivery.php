<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFReceiptChannel;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\ReceiptDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * One send of an income receipt to a giver, over one channel. Creating one sends it.
 */
#[Fillable([
    'ledger_entry_id',
    'channel',
    'recipient',
    'status',
    'share_url',
    'error',
    'sent_at',
    'requested_by',
])]
class ReceiptDelivery extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<ReceiptDeliveryFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = ['ledgerEntry'];

    public const SORTS = ['created_at', 'updated_at', 'sent_at'];

    protected function casts(): array
    {
        return [
            'channel' => PRFReceiptChannel::class,
            'status' => PRFDeliveryStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('channel'),
            AllowedFilter::exact('status'),
            AllowedFilter::callback('ledger_entry_ulid', function ($query, $value) {
                $query->where('ledger_entry_id', LedgerEntry::query()->select('id')->where('ulid', $value));
            }),
        ];
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
