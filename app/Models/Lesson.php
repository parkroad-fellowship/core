<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * @property PRFLessonType $type
 * @property PRFActiveStatus $is_active
 * @property ?string $content
 * @property ?string $video_url
 * @property ?string $audio_url
 * @property ?string $document_url
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'type',
    'content',
    'video_url',
    'audio_url',
    'document_url',
    'is_active',
])]
class Lesson extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<LessonFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasSlug;
    use HasULID;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PRFLessonType::class,
            'is_active' => PRFActiveStatus::class,
        ];
    }

    public const INCLUDES = ['lessonModules', 'thumbnail'];

    public const SORTS = ['created_at', 'updated_at'];

    public static function filters(): array
    {
        return [];
    }

    public const THUMBNAILS = 'thumbnails';

    public const VIDEO = 'videos';

    public const AUDIO = 'audios';

    public const DOCUMENT = 'documents';

    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * The upload each lesson type is delivered through, with the file types it accepts and the
     * column holding a link instead, for files that can't be downloaded (e.g. YouTube).
     *
     * @var array<int, array{collection: string, url: string, types: list<string>}>
     */
    public const CONTENT_MEDIA = [
        PRFLessonType::VIDEO->value => [
            'collection' => self::VIDEO,
            'url' => 'video_url',
            'types' => ['video/mp4', 'video/webm', 'video/quicktime'],
        ],
        PRFLessonType::AUDIO->value => [
            'collection' => self::AUDIO,
            'url' => 'audio_url',
            'types' => ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/aac', 'audio/wav', 'audio/ogg'],
        ],
        PRFLessonType::DOCUMENT->value => [
            'collection' => self::DOCUMENT,
            'url' => 'document_url',
            'types' => ['application/pdf'],
        ],
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::THUMBNAILS)->singleFile()->acceptsMimeTypes(self::IMAGE_TYPES);

        foreach (self::CONTENT_MEDIA as ['collection' => $collection, 'types' => $types]) {
            $this->addMediaCollection($collection)->singleFile()->acceptsMimeTypes($types);
        }
    }

    /**
     * The media collection holding this lesson's content, or null for a text lesson.
     */
    public function contentCollection(): ?string
    {
        return self::CONTENT_MEDIA[$this->type->value]['collection'] ?? null;
    }

    /**
     * Whether a student would find something to learn: text for a text lesson, otherwise an
     * uploaded file or a link to one.
     */
    public function hasContent(): bool
    {
        $collection = $this->contentCollection();

        if ($collection === null) {
            return filled(strip_tags((string) $this->content));
        }

        return $this->hasMedia($collection) || filled($this->contentURL());
    }

    /**
     * The outside link a video, audio or document lesson points to instead of an upload.
     */
    public function contentURL(): ?string
    {
        return match ($this->type) {
            PRFLessonType::VIDEO => $this->video_url,
            PRFLessonType::AUDIO => $this->audio_url,
            PRFLessonType::DOCUMENT => $this->document_url,
            default => null,
        };
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    /**
     * @return HasMany<LessonModule, $this>
     */
    public function lessonModules(): HasMany
    {
        return $this->hasMany(related: LessonModule::class);
    }

    /**
     * @return HasMany<LessonMember, $this>
     */
    public function lessonMembers(): HasMany
    {
        return $this->hasMany(related: LessonMember::class);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function videos(): MorphMany
    {
        return $this->media()->where('collection_name', self::VIDEO);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function audios(): MorphMany
    {
        return $this->media()->where('collection_name', self::AUDIO);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function documents(): MorphMany
    {
        return $this->media()->where('collection_name', self::DOCUMENT);
    }

    /**
     * @return HasOne<Media, $this>
     */
    public function thumbnail(): HasOne
    {
        return $this->hasOne(related: Media::class, foreignKey: 'model_id')->where([
            'collection_name' => self::THUMBNAILS,
            'model_type' => self::class,
        ]);
    }

    /**
     * @return HasOne<LessonMember, $this>
     */
    public function lessonMember(): HasOne
    {
        return $this->hasOne(LessonMember::class)->where([
            'member_id' => Member::currentMemberIdQuery(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
