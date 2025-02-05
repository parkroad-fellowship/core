<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissionGroundSuggestion extends Model
{
    /** @use HasFactory<\Database\Factories\MissionGroundSuggestionFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'suggestor_id',
        'name',
        'contact_person',
        'contact_number',
        'status',
    ];

    const INCLUDES = [
        'suggestor',
    ];

    public function suggestor()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'suggestor_id',
        );
    }
}
