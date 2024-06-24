<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolContact extends Model
{
    use HasFactory;
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'contact_type_id',
        'name',
        'email',
        'phone',
        'is_active',
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
}
