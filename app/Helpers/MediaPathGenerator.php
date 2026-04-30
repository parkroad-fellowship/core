<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class MediaPathGenerator implements PathGenerator
{
    // TODO: Remove the environment from the path
    /**
     * Get the path for the given media, relative to the root storage path.
     *
     * @param  \Spatie\MediaLibrary\Media  $media
     */
    public function getPath(Media $media): string
    {
        return 'prf-core/'.App::environment().'/media-library/'.md5($media->id).'/';
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     *
     * @param  \Spatie\MediaLibrary\Media  $media
     */
    public function getPathForConversions(Media $media): string
    {
        return 'prf-core/'.App::environment().'/media-library/'.md5($media->id).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'/cri/';
    }
}
