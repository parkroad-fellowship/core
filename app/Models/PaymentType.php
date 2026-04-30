<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Database\Factories\PaymentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class PaymentType extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<PaymentTypeFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public const INCLUDES = [];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('status_key', function ($query, $value): void {
                $query->where('is_active', $value);
            }),
        ];
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
