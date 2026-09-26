<?php

namespace App\Filament\Concerns;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ELearningSchema;
use App\Jobs\Lesson\CreateJob as CreateLessonJob;
use App\Jobs\Lesson\UpdateJob as UpdateLessonJob;
use App\Jobs\LessonModule\CreateJob as AddLessonJob;
use App\Jobs\LessonModule\DeleteJob as RemoveLessonJob;
use App\Jobs\LessonModule\MoveJob as MoveLessonJob;
use App\Jobs\LessonModule\ReorderJob as ReorderLessonsJob;
use App\Models\Lesson;
use App\Models\LessonModule;
use App\Models\Module;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * The lesson half of the course builder: add, create, edit, preview, remove and drag lessons
 * inside a module. Used by the course's Curriculum tab and the module's Lessons tab.
 */
trait BuildsCurriculum
{
    public function canChangeCurriculum(): bool
    {
        return userCan(LessonModule::permission('create')) && userCan(LessonModule::permission('delete'));
    }

    public function canEditLessons(): bool
    {
        return userCan(Lesson::permission('edit'));
    }

    /**
     * wire:sort handler for lessons. Dropping into another module's list moves the lesson there.
     */
    public function sortLessons(string $linkULID, int $position, string $moduleULID): void
    {
        abort_unless($this->canChangeCurriculum(), 403);

        $link = LessonModule::query()->where('ulid', $linkULID)->with('module')->firstOrFail();
        $module = Module::query()->where('ulid', $moduleULID)->firstOrFail();

        if ($link->module_id !== $module->id) {
            MoveLessonJob::dispatchSync($link, $module, $this->actor(), $position);

            return;
        }

        $ulids = LessonModule::query()
            ->where('module_id', $module->id)
            ->whereKeyNot($link->id)
            ->orderBy('order')
            ->get()
            ->map(fn(LessonModule $sibling): string => $sibling->ulid)
            ->all();

        array_splice($ulids, max(0, $position), 0, [$link->ulid]);

        ReorderLessonsJob::dispatchSync($module, $ulids);
    }

    public function newLessonAction(): Action
    {
        return Action::make('newLesson')
            ->label('New lesson')
            ->icon('heroicon-m-plus')
            ->link()
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(
                fn(array $arguments): string => 'New lesson in “' . $this->moduleFrom($arguments)->name . '”',
            )
            ->modalSubmitActionLabel('Add lesson')
            ->model(Lesson::class)
            ->schema(ELearningSchema::lessonForm())
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, array $arguments, Schema $schema): void {
                    $module = $this->moduleFrom($arguments);

                    DB::transaction(function () use ($data, $module, $schema): void {
                        $lesson = CreateLessonJob::dispatchSync($data);
                        assert($lesson instanceof Lesson);
                        $schema->model($lesson)->saveRelationships();

                        AddLessonJob::dispatchSync(['module_ulid' => $module->ulid, 'lesson_ulid' => $lesson->ulid]);
                    });

                    Notification::make()->success()->title('Lesson added')->send();
                },
            )
            ->visible(fn(): bool => $this->canChangeCurriculum() && userCan(Lesson::permission('create')));
    }

    public function addLessonAction(): Action
    {
        return Action::make('addLesson')
            ->label('Add from library')
            ->icon('heroicon-m-book-open')
            ->link()
            ->color('gray')
            ->modalHeading(
                fn(array $arguments): string => 'Add a lesson to “' . $this->moduleFrom($arguments)->name . '”',
            )
            ->modalDescription(
                'Pick a lesson that already exists. It will be shared, so edits to it show everywhere it’s used.',
            )
            ->modalSubmitActionLabel('Add')
            ->schema(fn(array $arguments): array => [
                Select::make('lesson_ulid')
                    ->label('Lesson')
                    ->searchable()
                    ->required()
                    ->allowHtml()
                    ->getSearchResultsUsing(fn(string $search): array => $this->libraryLessons(
                        $this->moduleFrom($arguments),
                        $search,
                    ))
                    ->options(fn(): array => $this->libraryLessons($this->moduleFrom($arguments)))
                    ->noSearchResultsMessage('No lessons match. Use “New lesson” to write one.'),
            ])
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, array $arguments): void {
                    AddLessonJob::dispatchSync([
                        'module_ulid' => $this->moduleFrom($arguments)->ulid,
                        'lesson_ulid' => $data['lesson_ulid'],
                    ]);

                    Notification::make()->success()->title('Lesson added')->send();
                },
            )
            ->visible(fn(): bool => $this->canChangeCurriculum());
    }

    public function editLessonAction(): Action
    {
        return Action::make('editLesson')
            ->label('Edit')
            ->icon('heroicon-m-pencil-square')
            ->iconButton()
            ->tooltip('Edit lesson')
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(fn(Lesson $record): string => "Edit “{$record->name}”")
            ->modalSubmitActionLabel('Save lesson')
            ->record(
                fn(array $arguments): ?Lesson => Lesson::query()
                    ->where('ulid', $arguments['lesson'] ?? null)
                    ->first(),
            )
            ->fillForm(fn(Lesson $record): array => [
                ...$record->attributesToArray(),
                'type' => $record->type->value,
                'is_active' => $record->is_active->value,
            ])
            ->schema(fn(Lesson $record): array => [
                ELearningSchema::sharedCallout(ELearningSchema::lessonPlacements($record), 'lesson'),
                ...ELearningSchema::lessonForm(),
            ])
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, Lesson $record, Schema $schema): void {
                    UpdateLessonJob::dispatchSync($data, $record->ulid);
                    $schema->saveRelationships();

                    Notification::make()->success()->title('Lesson saved')->send();
                },
            )
            ->visible(fn(): bool => $this->canEditLessons());
    }

    public function previewLessonAction(): Action
    {
        return Action::make('previewLesson')
            ->label('Preview')
            ->icon('heroicon-m-eye')
            ->iconButton()
            ->color('gray')
            ->tooltip('Preview as a student')
            ->record(
                fn(array $arguments): ?Lesson => Lesson::query()
                    ->where('ulid', $arguments['lesson'] ?? null)
                    ->first(),
            )
            ->modalHeading(fn(Lesson $record): string => $record->name)
            ->modalDescription(fn(Lesson $record): string => (string) $record->description)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema(fn(Lesson $record): array => [
                Text::make(new HtmlString(view('filament.e-learning.lesson-preview', ['lesson' => $record])->render())),
            ]);
    }

    public function removeLessonAction(): Action
    {
        return Action::make('removeLesson')
            ->label('Remove')
            ->icon('heroicon-m-x-mark')
            ->iconButton()
            ->color('danger')
            ->tooltip('Remove from this module')
            ->requiresConfirmation()
            ->modalHeading('Remove this lesson from the module?')
            ->modalDescription(
                'It is only taken out of this module. The lesson stays in the library, and anywhere else it is used.',
            )
            ->modalSubmitActionLabel('Remove')
            ->action(function (array $arguments): void {
                $link = LessonModule::query()
                    ->where('ulid', $arguments['link'] ?? null)
                    ->firstOrFail();

                RemoveLessonJob::dispatchSync($link, $this->actor());

                Notification::make()->success()->title('Lesson removed from the module')->send();
            })
            ->visible(fn(): bool => $this->canChangeCurriculum());
    }

    /**
     * @param  array<mixed>  $arguments
     */
    protected function moduleFrom(array $arguments): Module
    {
        return Module::query()
            ->where('ulid', $arguments['module'] ?? null)
            ->firstOrFail();
    }

    /**
     * Lessons that could be added to the module, labelled with their type and how often they're used.
     *
     * @return array<string, string>
     */
    protected function libraryLessons(Module $module, ?string $search = null): array
    {
        return Lesson::query()
            ->whereDoesntHave('lessonModules', fn($query) => $query->where('module_id', $module->id))
            ->when(filled($search), fn($query) => $query->whereLike('name', "%{$search}%", caseSensitive: false))
            ->withCount('lessonModules')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn(Lesson $lesson): array => [
                $lesson->ulid => sprintf(
                    '%s <span style="opacity:.6">· %s%s%s</span>',
                    e($lesson->name),
                    $lesson->type->getLabel(),
                    $lesson->lesson_modules_count > 0 ? " · used in {$lesson->lesson_modules_count}" : '',
                    $lesson->is_active === PRFActiveStatus::ACTIVE ? '' : ' · hidden',
                ),
            ])
            ->all();
    }

    protected function actor(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @return array{icon: string, color: string, label: string}
     */
    public function lessonBadge(Lesson $lesson): array
    {
        return [
            'icon' => ELearningSchema::typeIcon($lesson->type),
            'color' => ELearningSchema::typeColor($lesson->type),
            'label' => $lesson->type->getLabel(),
        ];
    }
}
