<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibleTranslation extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
    ];

    public const INCLUDES = [
        'bibleBooks',
        'bibleChapters',
        'bibleVerses',
    ];

    public function bibleBooks()
    {
        return $this->hasMany(BibleBook::class);
    }

    public function bibleChapters()
    {
        return $this->hasMany(BibleChapter::class);
    }

    public function bibleVerses()
    {
        return $this->hasMany(BibleVerse::class);
    }
}
