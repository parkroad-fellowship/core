<?php

namespace App\Filament\Forms\Schemas;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

/**
 * The course, module and lesson forms, shared by the resources and the course builder so a
 * lesson looks the same wherever it's edited. Active/Inactive reads as Published/Hidden here.
 */
class ELearningSchema
{
    public static function statusLabel(?PRFActiveStatus $status): string
    {
        return $status === PRFActiveStatus::ACTIVE ? 'Published' : 'Hidden';
    }

    public static function statusColor(?PRFActiveStatus $status): string
    {
        return $status === PRFActiveStatus::ACTIVE ? 'success' : 'gray';
    }

    public static function statusIcon(?PRFActiveStatus $status): string
    {
        return $status === PRFActiveStatus::ACTIVE ? 'heroicon-m-eye' : 'heroicon-m-eye-slash';
    }

    public static function typeIcon(?PRFLessonType $type): string
    {
        return match ($type) {
            PRFLessonType::VIDEO => 'heroicon-m-play-circle',
            PRFLessonType::AUDIO => 'heroicon-m-musical-note',
            PRFLessonType::DOCUMENT => 'heroicon-m-document',
            default => 'heroicon-m-document-text',
        };
    }

    public static function typeColor(?PRFLessonType $type): string
    {
        return match ($type) {
            PRFLessonType::VIDEO => 'info',
            PRFLessonType::AUDIO => 'warning',
            PRFLessonType::DOCUMENT => 'success',
            default => 'gray',
        };
    }

    public static function statusField(string $what): ToggleButtons
    {
        return ToggleButtons::make('is_active')
            ->label('Visible to students?')
            ->options([
                PRFActiveStatus::INACTIVE->value => 'Hidden',
                PRFActiveStatus::ACTIVE->value => 'Published',
            ])
            ->icons([
                PRFActiveStatus::INACTIVE->value => 'heroicon-m-eye-slash',
                PRFActiveStatus::ACTIVE->value => 'heroicon-m-eye',
            ])
            ->colors([
                PRFActiveStatus::INACTIVE->value => 'gray',
                PRFActiveStatus::ACTIVE->value => 'success',
            ])
            ->default(PRFActiveStatus::INACTIVE->value)
            ->inline()
            ->required()
            ->helperText("Students only see published {$what}. Keep it hidden while you're still working on it.");
    }

    public static function coverField(string $collection): SpatieMediaLibraryFileUpload
    {
        return MediaSchema::uploadField(
            collection: $collection,
            label: 'Cover image',
            multiple: false,
            acceptedFileTypes: Lesson::IMAGE_TYPES,
            helperText: 'Optional. A landscape picture (about 1200 × 630) shown on the card in the app.',
        )->image();
    }

    /**
     * @return list<Component>
     */
    public static function courseForm(): array
    {
        return [
            Section::make()
                ->columnSpanFull()
                ->schema([
                    ContentSchema::nameField(
                        name: 'name',
                        label: 'Course name',
                        placeholder: 'e.g. Foundations of Faith',
                        helperText: 'What students will see as the course title.',
                    ),
                    ContentSchema::descriptionField(
                        name: 'description',
                        label: 'What is this course about?',
                        rows: 3,
                        required: true,
                        placeholder: 'A few sentences on what students will learn and who it’s for.',
                    ),
                    self::coverField(Course::THUMBNAILS),
                ]),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function moduleForm(): array
    {
        return [
            ContentSchema::nameField(
                name: 'name',
                label: 'Module name',
                placeholder: 'e.g. Salvation',
                helperText: 'A module groups related lessons, like a chapter.',
            ),
            ContentSchema::descriptionField(
                name: 'description',
                label: 'Short summary',
                rows: 2,
                required: true,
                placeholder: 'One or two sentences on what this module covers.',
            ),
            self::statusField('modules')->default(PRFActiveStatus::ACTIVE->value),
            self::coverField(Module::THUMBNAILS),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function lessonForm(): array
    {
        $isType = fn(PRFLessonType $type): \Closure => function (Get $get) use ($type): bool {
            $state = $get('type');

            return (
                ($state instanceof PRFLessonType ? $state->value : (is_numeric($state) ? (int) $state : null))
                === $type->value
            );
        };

        return [
            ContentSchema::nameField(name: 'name', label: 'Lesson title', placeholder: 'e.g. What is grace?'),
            ContentSchema::descriptionField(
                name: 'description',
                label: 'Short summary (shown in the lesson list)',
                rows: 2,
                required: true,
                placeholder: 'One sentence on what the student will learn.',
            ),
            Radio::make('type')
                ->label('How is this lesson delivered?')
                ->options(PRFLessonType::getOptions())
                ->descriptions([
                    PRFLessonType::TEXT->value => 'Write the lesson here.',
                    PRFLessonType::VIDEO->value => 'Upload a video, or link to one (e.g. YouTube).',
                    PRFLessonType::AUDIO->value => 'Upload a recording, sermon or podcast, or link to one.',
                    PRFLessonType::DOCUMENT->value => 'Upload a PDF, or link to one hosted elsewhere.',
                ])
                ->default(PRFLessonType::TEXT->value)
                ->columns(2)
                ->required()
                ->live(),

            ContentSchema::richEditorField(name: 'content', label: 'Lesson', required: true, toolbarButtons: [
                'h2',
                'h3',
                'bold',
                'italic',
                'underline',
                'bulletList',
                'orderedList',
                'blockquote',
                'link',
            ])->visible($isType(PRFLessonType::TEXT)),

            ...collect([
                PRFLessonType::VIDEO,
                PRFLessonType::AUDIO,
                PRFLessonType::DOCUMENT,
            ])->flatMap(fn(PRFLessonType $type): array => self::lessonFileFields($type, $isType($type)))->all(),

            self::statusField('lessons')->default(PRFActiveStatus::ACTIVE->value),
            self::coverField(Lesson::THUMBNAILS),
        ];
    }

    /**
     * An upload plus a link for a video, audio or document lesson. One of them is needed; the
     * link is for files that can't be downloaded, like a YouTube video.
     *
     * @return list<Component>
     */
    private static function lessonFileFields(PRFLessonType $type, \Closure $isVisible): array
    {
        $spec = Lesson::CONTENT_MEDIA[$type->value] ?? null;

        if ($spec === null) {
            return [];
        }

        ['collection' => $collection, 'url' => $urlField, 'types' => $types] = $spec;

        [$what, $formats, $placeholder] = match ($type) {
            PRFLessonType::VIDEO => ['video', 'MP4, WebM or MOV', 'https://www.youtube.com/watch?v=…'],
            PRFLessonType::AUDIO => ['recording', 'MP3, M4A, AAC, WAV or OGG', 'https://…'],
            default => ['PDF', 'PDF only', 'https://…/notes.pdf'],
        };

        return [
            MediaSchema::uploadField(
                collection: $collection,
                label: 'Upload the ' . $what,
                multiple: false,
                acceptedFileTypes: $types,
                helperText: "{$formats}. Uploading a new file replaces the old one.",
            )
                ->required(fn(Get $get): bool => blank($get($urlField)))
                ->visible($isVisible),

            TextInput::make($urlField)
                ->label('…or link to it')
                ->url()
                ->rule('regex:#^https?://#i')
                ->validationMessages(['regex' => 'Use a web link starting with https:// (or http://).'])
                ->maxLength(2048)
                ->placeholder($placeholder)
                ->helperText(
                    "Use a link when the {$what} lives elsewhere and can’t be downloaded. If you upload a file too, students get the file.",
                )
                ->visible($isVisible),
        ];
    }

    /**
     * Where a lesson is used, e.g. "Salvation (Foundations of Faith)".
     *
     * @return list<string>
     */
    public static function lessonPlacements(Lesson $lesson): array
    {
        return array_values(
            LessonModule::query()
                ->where('lesson_id', $lesson->id)
                ->with('module.courseModules.course')
                ->get()
                ->map(function (LessonModule $link): string {
                    $courses = $link->module->courseModules->map(fn(CourseModule $courseModule): string => $courseModule->course->name);

                    return $courses->isEmpty()
                        ? $link->module->name
                        : "{$link->module->name} ({$courses->implode(', ')})";
                })
                ->all(),
        );
    }

    /**
     * Course names a module is part of.
     *
     * @return list<string>
     */
    public static function modulePlacements(Module $module): array
    {
        return array_values(
            $module
                ->courseModules()
                ->with('course')
                ->get()
                ->map(fn(CourseModule $courseModule): string => $courseModule->course->name)
                ->all(),
        );
    }

    /**
     * Shown above a shared lesson or module's form: editing it changes every place it's used.
     *
     * @param  list<string>  $placements
     */
    public static function sharedCallout(array $placements, string $what): Callout
    {
        return Callout::make('Shared ' . $what)
            ->warning()
            ->description(new HtmlString(
                'Used in '
                . count($placements)
                . ' places: <strong>'
                . e(implode('; ', $placements))
                . '</strong>. Changes you save here apply everywhere.',
            ))
            ->visible(count($placements) > 1)
            ->columnSpanFull();
    }
}
