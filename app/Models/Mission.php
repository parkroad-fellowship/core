<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mission extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'school_term_id',
        'mission_type_id',
        'school_id',
        'start_date',
        'end_date',
        'capacity',
        'mission_prep_notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    const INCLUDES = [
        'schoolTerm',
        'missionType',
        'school',
        'missionSubscriptions',
        'missionSubscriptions.member',
    ];

    public function schoolTerm()
    {
        return $this->belongsTo(SchoolTerm::class);
    }

    public function missionType()
    {
        return $this->belongsTo(MissionType::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function missionSubscriptions()
    {
        return $this->hasMany(MissionSubscription::class);
    }
}
