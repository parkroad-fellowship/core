<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibleVerse extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'bible_translation_id',
        'bible_book_id',
        'bible_chapter_id',
        'verse_number',
        'text',
    ];

    public const INCLUDES = [
        'bibleTranslation',
        'bibleBook',
        'bibleChapter',
    ];

    public function bibleTranslation()
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    public function bibleBook()
    {
        return $this->belongsTo(BibleBook::class);
    }

    public function bibleChapter()
    {
        return $this->belongsTo(BibleChapter::class);
    }
}
