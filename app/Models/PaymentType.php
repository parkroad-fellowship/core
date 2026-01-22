<?php

namespace App\Models;

use Database\Factories\PaymentTypeFactory;
use App\Traits\HasUlid;
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
