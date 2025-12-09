<?php

namespace App\Models;

use App\Traits\HasUlid;
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
        'translation',
        'book',
        'verses',
    ];

    public function translation()
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    public function book()
    {
        return $this->belongsTo(BibleBook::class);
    }

    public function verses()
    {
        return $this->hasMany(BibleVerse::class);
    }
}
