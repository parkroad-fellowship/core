<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFFinancialReportType;
use App\Enums\PRFProcessingStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\FinancialReportFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * A generated finance workbook or PDF. Creating one queues the generation; the file lives on the
 * app's default disk (not the container's local disk, which a redeploy wipes).
 */
#[Fillable([
    'type',
    'period_start',
    'period_end',
    'status',
    'file_path',
    'error',
    'completed_at',
    'requested_by',
])]
class FinancialReport extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<FinancialReportFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use SoftDeletes;

    public const INCLUDES = ['requestedBy'];

    public const SORTS = ['created_at', 'updated_at', 'period_start'];

    protected function casts(): array
    {
        return [
            'type' => PRFFinancialReportType::class,
            'status' => PRFProcessingStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::exact('type'),
            AllowedFilter::exact('status'),
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(config()->string('filesystems.default'));
    }

    public function storagePath(): string
    {
        return "financial-reports/{$this->tenant_id}/{$this->ulid}.{$this->type->extension()}";
    }

    public function fileExists(): bool
    {
        return $this->file_path !== null && self::disk()->exists($this->file_path);
    }

    public function isReady(): bool
    {
        return $this->status === PRFProcessingStatus::COMPLETED && $this->file_path !== null;
    }

    public function downloadName(): string
    {
        return sprintf(
            '%s %s to %s.%s',
            $this->type->getLabel(),
            $this->period_start->format('Y-m-d'),
            $this->period_end->format('Y-m-d'),
            $this->type->extension(),
        );
    }
}
