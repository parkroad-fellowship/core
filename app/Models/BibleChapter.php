<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibleChapter extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'bible_translation_id',
        'bible_book_id',
        'chapter_number',
    ];

    public const INCLUDES = [
        'bibleTranslation',
        'bibleBook',
        'bibleVerses',
    ];

    public function bibleTranslation()
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    public function bibleBook()
    {
        return $this->belongsTo(BibleBook::class);
    }

    public function bibleVerses()
    {
        return $this->hasMany(BibleVerse::class);
    }
}
