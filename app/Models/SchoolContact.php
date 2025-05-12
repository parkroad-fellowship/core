<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SchoolContact extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'contact_type_id',
        'name',
        'email',
        'phone',
        'is_active',
        'preferred_name',
    ];

    const INCLUDES = [
        'school',
        'contactType',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
