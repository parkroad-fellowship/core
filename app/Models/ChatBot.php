<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ChatBot extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public const INCLUDES = [];

    public const SORTS = ['created_at', 'updated_at'];

    protected $fillable = [
        'ulid',
        'name',
        'description',
    ];

    public static function filters(): array
    {
        return [];
    }
}
