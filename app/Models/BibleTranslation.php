<?php

namespace App\Models;

use App\Traits\HasUlid;
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
        'books',
        'chapters',
        'verses',
    ];

    public function books()
    {
        return $this->hasMany(BibleBook::class);
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
