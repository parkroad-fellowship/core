<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MpesaRate extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'transaction_type',
        'min_amount',
        'max_amount',
        'charge',
    ];
}
