<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CohortMission extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'cohort_id',
        'mission_id',
    ];

    public function cohort()
    {
        return $this->belongsTo(Cohort::class);
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }
}
