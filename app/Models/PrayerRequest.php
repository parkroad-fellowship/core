<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\PrayerRequestObserver;
use Database\Factories\PrayerRequestFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(PrayerRequestObserver::class)]
class PrayerRequest extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<PrayerRequestFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'title',
        'description',
    ];

    const INCLUDES = [
        'member',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('member_ulid', function ($query, $value) {
                $query->where(
                    'member_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1),
                );
            }),
        ];
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
