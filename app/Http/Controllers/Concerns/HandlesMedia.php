<?php

namespace App\Http\Controllers\Concerns;

use App\Jobs\Media\DeleteTemporaryFileJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Shared media endpoints for controllers whose `$modelClass` implements HasMedia
 * and declares a `MEDIA_COLLECTIONS` constant.
 *
 * Media is always resolved through its owner, so a request can never read or
 * delete media belonging to another record.
 */
trait HandlesMedia
{
    public function getMedia(Request $request, string $ulid): AnonymousResourceCollection
    {
        $owner = $this->findMediaOwner($ulid);

        $this->authorize('view', $owner);

        $collections = $this->requestedMediaCollections($request);

        return \App\Http\Resources\Media\Resource::collection(
            collect($collections)->flatMap(fn(string $collection) => $owner->getMedia($collection))->values(),
        );
    }

    /**
     * The uploader may remove their own media; anyone else needs update rights on the owner.
     */
    public function deleteMedia(string $ulid, string $mediaUuid): Response
    {
        $owner = $this->findMediaOwner($ulid);

        $media = $owner->media()->where('uuid', $mediaUuid)->firstOrFail();

        if (!$media instanceof Media) {
            throw new LogicException('Media relation returned an unexpected model.');
        }

        if ($media->getCustomProperty(\App\Models\Media::UPLOADED_BY_PROPERTY) !== Auth::user()?->ulid) {
            $this->authorize('update', $owner);
        }

        $media->delete();

        return response()->noContent();
    }

    protected function findMediaOwner(string $ulid): Model&HasMedia
    {
        $owner = $this->mediaOwnerClass()::query()->where('ulid', $ulid)->firstOrFail();

        if (!$owner instanceof HasMedia) {
            throw new LogicException($owner::class . ' does not implement ' . HasMedia::class . '.');
        }

        return $owner;
    }

    /**
     * @param  array<string, mixed>  $customProperties
     */
    protected function attachUploadedMedia(
        HasMedia $owner,
        UploadedFile $file,
        string $collection,
        array $customProperties = [],
    ): Media {
        return $owner
            ->addMedia($file)
            ->withCustomProperties($this->withUploader($customProperties))
            ->toMediaCollection($collection);
    }

    /**
     * Attach a file the client already uploaded to the temporary disk, then clean up the temporary copy.
     *
     * @param  array<string, mixed>  $customProperties
     */
    protected function attachTemporaryMedia(
        HasMedia $owner,
        string $storagePath,
        string $collection,
        array $customProperties = [],
    ): Media {
        $contents = Http::get(Storage::disk('azure_tmp')->url($storagePath))->throw()->body();

        $localCopy = tempnam(sys_get_temp_dir(), 'prf-media-');

        if ($localCopy === false || file_put_contents($localCopy, $contents) === false) {
            throw new LogicException('Could not stage the temporary media file.');
        }

        $media = $owner
            ->addMedia($localCopy)
            ->usingFileName(basename($storagePath))
            ->withCustomProperties($this->withUploader($customProperties))
            ->toMediaCollection($collection);

        DeleteTemporaryFileJob::dispatch(['azure_tmp', 'azure'], $storagePath);

        return $media;
    }

    protected function uploadedMediaFile(Request $request, string $key = 'media_file'): UploadedFile
    {
        $file = $request->file($key);

        if (!$file instanceof UploadedFile) {
            throw ValidationException::withMessages([$key => 'A single file upload is required.']);
        }

        return $file;
    }

    /**
     * @return class-string<Model>
     */
    protected function mediaOwnerClass(): string
    {
        $class = $this->modelClass;

        if ($class === null || !is_a($class, Model::class, true) || !is_a($class, HasMedia::class, true)) {
            throw new LogicException(static::class
            . ' must set $modelClass to an Eloquent model implementing HasMedia.');
        }

        return $class;
    }

    /**
     * @return list<string>
     */
    protected function mediaCollections(): array
    {
        $collections = constant($this->mediaOwnerClass() . '::MEDIA_COLLECTIONS');

        return is_array($collections) ? array_values(array_filter($collections, is_string(...))) : [];
    }

    /**
     * Accepts `?collection=a`, `?collections=a,b` or `?collections[]=a&collections[]=b`.
     *
     * @return list<string>
     */
    private function requestedMediaCollections(Request $request): array
    {
        $requested = $request->query('collections', $request->query('collection'));

        $collections = is_string($requested) ? explode(',', $requested) : Arr::wrap($requested);
        $collections = array_values(array_filter($collections, fn(mixed $value) => is_string($value) && $value !== ''));

        Validator::make(['collections' => $collections], [
            'collections' => ['required', 'array', 'min:1'],
            'collections.*' => ['string', Rule::in($this->mediaCollections())],
        ])->validate();

        return $collections;
    }

    /**
     * @param  array<string, mixed>  $customProperties
     * @return array<string, mixed>
     */
    private function withUploader(array $customProperties): array
    {
        return [...$customProperties, \App\Models\Media::UPLOADED_BY_PROPERTY => Auth::user()?->ulid];
    }
}
