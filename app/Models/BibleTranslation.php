<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasULID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
    'name',
    'code',
])]
class BibleTranslation extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = [
        'bibleBooks',
        'bibleChapters',
        'bibleVerses',
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
     * @return HasMany<BibleBook, $this>
     */
    public function bibleBooks(): HasMany
    {
        return $this->hasMany(BibleBook::class);
    }

    /**
     * @return HasMany<BibleChapter, $this>
     */
    public function bibleChapters(): HasMany
    {
        return $this->hasMany(BibleChapter::class);
    }

    /**
     * @return HasMany<BibleVerse, $this>
     */
    public function bibleVerses(): HasMany
    {
        return $this->hasMany(BibleVerse::class);
    }
}
