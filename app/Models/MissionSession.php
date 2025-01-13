<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MissionSession extends Model
{
    /** @use HasFactory<\Database\Factories\MissionSessionFactory> */
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'mission_id',
        'facilitator_id',
        'speaker_id',
        'class_group_id',
        'start_at',
        'end_at',
        'notes',
    ];

    public const INCLUDES = [
        'mission',
        'facilitator',
        'speaker',
        'classGroup',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function facilitator()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'facilitator_id',
        );
    }

    public function speaker()
    {
        return $this->belongsTo(
            related: Member::class,
            foreignKey: 'speaker_id',
        );
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }
}
