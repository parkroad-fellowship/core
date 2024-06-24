<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebriefNote extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'mission_id',
        'note',
    ];

    const INCLUDES = [
        'mission',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }
}
