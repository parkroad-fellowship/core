<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibleBook extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'bible_translation_id',
        'name',
        'order',
    ];

    public const INCLUDES = [
        'translation',
        'chapters',
        'verses',
    ];

    public function translation()
    {
        return $this->belongsTo(BibleTranslation::class);
    }

    public function chapters()
    {
        return $this->hasMany(BibleChapter::class);
    }

    public function verses()
    {
        return $this->hasMany(BibleVerse::class);
    }
}
