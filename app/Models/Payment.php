<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFPaymentStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'payment_type_id',
    'member_id',
    'pledge_id',
    'amount',
    'payment_status',
    'reference',
    'access_code',
    'authorization_url',
    'transaction_meta',
    'status_checked_at',
    'status_check_count',
    'next_status_check_at',
])]
class Payment extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    /** @var array<string> */

    protected function casts(): array
    {
        return [
            'order_meta' => 'array',
            'transaction_meta' => 'array',
            'payment_status' => PRFPaymentStatus::class,
            'status_checked_at' => 'datetime',
            'next_status_check_at' => 'datetime',
        ];
    }

    public const INCLUDES = [
        'paymentType',
        'member',
        'pledge',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('payment_type_ulid', function ($query, $value) {
                $query->where('payment_type_id', PaymentType::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where('member_id', Member::query()->select('id')->where('ulid', $value)->limit(1));
            }),
            AllowedFilter::callback('pledge_ulid', function ($query, $value) {
                $query->where('pledge_id', Pledge::query()->select('id')->where('ulid', $value)->limit(1));
            }),
        ];
    }

    /**
     * @return BelongsTo<PaymentType, $this>
     */
    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }
}
