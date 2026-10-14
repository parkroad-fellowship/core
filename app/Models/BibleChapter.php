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
    'bible_book_id',
    'chapter_number',
])]
class BibleChapter extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = [
        'bibleTranslation',
        'bibleBook',
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
     * @return BelongsTo<BibleTranslation, $this>
     */
    public function bibleTranslation(): BelongsTo
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    /**
     * @return BelongsTo<BibleBook, $this>
     */
    public function bibleBook(): BelongsTo
    {
        return $this->belongsTo(BibleBook::class);
    }

    /**
     * @return HasMany<BibleVerse, $this>
     */
    public function bibleVerses(): HasMany
    {
        return $this->hasMany(BibleVerse::class);
    }
}
