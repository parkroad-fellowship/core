<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class MediaPathGenerator implements PathGenerator
{
    // TODO: Remove the environment from the path
    /**
     * Get the tenant scoped root path for the given media, relative to the root storage path.
     *
     * Media without a tenant keeps the legacy layout so existing files and
     * persisted URLs keep working during the shared-container migration.
     *
     * @param  \Spatie\MediaLibrary\Media  $media
     */
    private function rootPath(Media $media): string
    {
        $tenantPath = $media->tenant_id ? "{$media->tenant_id}/" : '';

        return 'gospel-flood-core/' . App::environment() . '/media-library/' . $tenantPath;
    }

    /**
     * Get the path for the given media, relative to the root storage path.
     *
     * @param  \Spatie\MediaLibrary\Media  $media
     */
    public function getPath(Media $media): string
    {
        return $this->rootPath($media) . md5($media->id) . '/';
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     *
     * @param  \Spatie\MediaLibrary\Media  $media
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->rootPath($media) . md5($media->id) . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->rootPath($media) . md5($media->id) . '/cri/';
    }
}
