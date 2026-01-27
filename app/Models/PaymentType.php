<?php

namespace App\Models;

use App\Traits\HasUlid;
use Database\Factories\PaymentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentType extends Model
{
    /** @use HasFactory<PaymentTypeFactory> */
    use HasFactory;

    use HasUlid;
    use SoftDeletes;

    /** @var array<string> */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    const INCLUDES = [];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
