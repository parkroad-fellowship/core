<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'bible_translation_id',
    'name',
    'order',
])]
class BibleBook extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = [
        'translation',
        'chapters',
        'verses',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    /**
     * @return BelongsTo<BibleTranslation, $this>
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    /**
     * @return HasMany<BibleChapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(BibleChapter::class);
    }

    /**
     * @return HasMany<BibleVerse, $this>
     */
    public function verses(): HasMany
    {
        return $this->hasMany(BibleVerse::class);
    }
}
