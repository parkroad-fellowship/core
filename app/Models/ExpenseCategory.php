<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasUlid;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'description',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
