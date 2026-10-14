<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends SpatieMedia
{
    /** Custom property holding the ULID of the user who uploaded the file. */
    public const UPLOADED_BY_PROPERTY = 'uploaded_by_user_ulid';

    /**
     * Capture the owning tenant at creation time so media paths can be
     * scoped to a tenant without relying on runtime tenant context.
     */
    protected static function booted(): void
    {
        static::creating(function (Media $media): void {
            $media->tenant_id ??= $media->model?->tenant_id;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
