<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class MpesaRate extends Model
{
    use HasUlid;

    protected $fillable = [
        'transaction_type',
        'min_amount',
        'max_amount',
        'charge',
    ];
}
