<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Soul extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;


    protected $fillable = [
        'ulid',
        'mission_id',
        'class_group_id',
        'full_name',
    ];

    const INCLUDES = [
        'mission',
        'classGroup',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class);
    }
}
