<?php

namespace App\Models;

use App\Observers\MissionGroundSuggestionObserver;
use App\Traits\HasUlid;
use Database\Factories\MissionGroundSuggestionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(MissionGroundSuggestionObserver::class)]
class MissionGroundSuggestion extends Model
{
    /** @use HasFactory<MissionGroundSuggestionFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'suggestor_id',
        'name',
        'contact_person',
        'contact_number',
        'status',
        'notes',
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
