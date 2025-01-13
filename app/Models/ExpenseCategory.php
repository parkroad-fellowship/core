<?php

namespace App\Models;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseCategory extends Model
{
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'description',
        'is_active',
    ];

    public const INCLUDES = [
        'expenses',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
