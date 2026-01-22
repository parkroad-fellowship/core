<?php

namespace App\Models;

use Database\Factories\MissionFaqCategoryFactory;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MissionFaqCategory extends Model
{
    /** @use HasFactory<MissionFaqCategoryFactory> */
    use HasFactory;

    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'is_active',
    ];

    const INCLUDES = [];

    public function missionFaqs()
    {
        return $this->hasMany(MissionFaq::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
