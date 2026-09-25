<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFProcessingStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LedgerImportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * An upload of the treasurer's cashbook workbook. The file is deleted once the import finishes.
 */
#[Fillable([
    'file_path',
    'original_name',
    'year',
    'status',
    'mapping',
    'summary',
    'error',
    'imported_by',
    'completed_at',
])]
class LedgerImport extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LedgerImportFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    public const DISK = 'local';

    public const INCLUDES = [];

    public const SORTS = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'status' => PRFProcessingStatus::class,
            'year' => 'integer',
            'mapping' => 'array',
            'summary' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, \Spatie\QueryBuilder\AllowedFilter>
     */
    public static function filters(): array
    {
        return [];
    }

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
