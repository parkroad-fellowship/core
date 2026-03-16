<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class PaymentInstruction extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'requisition_id',
        'payment_method',
        'recipient_name',
        'reference',
        'mpesa_phone_number',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_branch',
        'bank_swift_code',
        'paybill_number',
        'paybill_account_number',
        'till_number',
        'amount',
    ];

    protected $casts = [
        'payment_method' => 'integer',
        'mpesa_phone_number' => 'integer',
        'bank_account_number' => 'integer',
        'paybill_number' => 'integer',
        'till_number' => 'integer',
        'amount' => 'integer',
    ];

    public const INCLUDES = [
        'requisition',
        'requisition.member',
        'requisition.accountingEvent',
    ];

    public const SORTS = ['created_at', 'updated_at', 'payment_method'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('requisition_ulid', function ($query, $value) {
                $query->where(
                    'requisition_id',
                    Requisition::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('payment_method', function ($query, $value) {
                $query->where('payment_method', $value);
            }),
            AllowedFilter::callback('recipient_name', function ($query, $value) {
                $query->where('recipient_name', 'like', '%'.$value.'%');
            }),
        ];
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }
}
