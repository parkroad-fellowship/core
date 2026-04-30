<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PRFEventHandler extends Model implements HasQueryBuilderCapabilities
{
    use HasModelPermissions;
    use HasUlid;
    use SoftDeletes;

    public static function permissionEntity(): string
    {
        return 'event handler';
    }

    public $table = 'prf_event_handlers';

    public const INCLUDES = ['prfEvent', 'member'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    protected $fillable = [
        'ulid',
        'prf_event_id',
        'member_id',
    ];

    public function prfEvent()
    {
        return $this->belongsTo(PRFEvent::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
