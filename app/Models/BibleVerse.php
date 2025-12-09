<?php

namespace App\Models;

use App\Traits\HasUlid;
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
        'translation',
        'book',
        'chapter',
    ];

    public function translation()
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    public function book()
    {
        return $this->belongsTo(BibleBook::class);
    }

    public function chapter()
    {
        return $this->belongsTo(BibleChapter::class);
    }
}
