<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class Payment extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'payment_type_id',
        'member_id',
        'amount',
        'payment_status',
        'reference',
        'access_code',
        'authorization_url',
        'transaction_meta',
    ];

    /** @var array<string> */
    protected $casts = [
        'order_meta' => 'array',
        'transaction_meta' => 'array',
    ];

    public const INCLUDES = [
        'paymentType',
        'member',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('payment_type_ulid', function ($query, $value) {
                $query->where(
                    'payment_type_id',
                    PaymentType::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where(
                    'member_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
        ];
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
