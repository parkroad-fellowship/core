<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\MissionGroundSuggestionObserver;
use Database\Factories\MissionGroundSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy(MissionGroundSuggestionObserver::class)]
class MissionGroundSuggestion extends Model implements HasQueryBuilderCapabilities
{
    /** @use HasFactory<MissionGroundSuggestionFactory> */
    use HasFactory;

    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'suggestor_id',
        'name',
        'contact_person',
        'contact_number',
        'status',
        'notes',
    ];

    const INCLUDES = [
        'suggestor',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('suggestor_ulid', function ($query, $value) {
                $query->where(
                    'suggestor_id',
                    Member::query()
                        ->select('id')
                        ->where('ulid', $value)
                        ->limit(1)
                );
            }),
            AllowedFilter::callback('status_key', function ($query, $value) {
                $query->where('status', $value);
            }),
            AllowedFilter::callback('status_keys', function ($query, $value) {
                $query->whereIn('status', $value);
            }),
        ];
    }

    public function suggestor()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'suggestor_id',
        );
    }
}
