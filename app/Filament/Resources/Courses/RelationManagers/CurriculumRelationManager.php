<?php

namespace App\Filament\Resources\Courses\RelationManagers;

use App\Filament\Concerns\BuildsCurriculum;
use App\Filament\Forms\Schemas\ELearningSchema;
use App\Jobs\CourseModule\CreateJob as AddModuleJob;
use App\Jobs\CourseModule\DeleteJob as RemoveModuleJob;
use App\Jobs\CourseModule\ReorderJob as ReorderModulesJob;
use App\Jobs\Module\CreateJob as CreateModuleJob;
use App\Jobs\Module\UpdateJob as UpdateModuleJob;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Module;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * The course builder: the course's modules as cards with their lessons inside, in order.
 * Drag to reorder (lessons can be dragged between modules), and add or create modules and
 * lessons without leaving the page.
 */
class CurriculumRelationManager extends RelationManager
{
    use BuildsCurriculum;

    protected static string $relationship = 'courseModules';

    protected static ?string $title = 'Curriculum';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-queue-list';

    protected string $view = 'filament.e-learning.curriculum';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord instanceof Course ? (string) $ownerRecord->courseModules()->count() : null;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function course(): Course
    {
        $course = $this->getOwnerRecord();

        assert($course instanceof Course);

        return $course;
    }

    /**
     * @return Collection<int, CourseModule>
     */
    public function modules(): Collection
    {
        return $this
            ->course()
            ->courseModules()
            ->orderBy('order')
            ->with([
                'module' => fn(Relation $query) => $query->withCount('courseModules'),
                'module.lessonModules' => fn(Relation $query) => $query->orderBy('order'),
                'module.lessonModules.lesson' => fn(Relation $query) => $query
                    ->withCount('lessonModules')
                    ->with('media'),
            ])
            ->get();
    }

    /**
     * wire:sort handler for the module cards.
     */
    public function sortModules(string $linkULID, int $position): void
    {
        abort_unless(userCan(CourseModule::permission('edit')), 403);

        $ulids = $this
            ->course()
            ->courseModules()
            ->where('ulid', '!=', $linkULID)
            ->orderBy('order')
            ->get()
            ->map(fn(CourseModule $link): string => $link->ulid)
            ->all();

        array_splice($ulids, max(0, $position), 0, [$linkULID]);

        ReorderModulesJob::dispatchSync($this->course(), $ulids);
    }

    public function newModuleAction(): Action
    {
        return Action::make('newModule')
            ->label('New module')
            ->icon('heroicon-m-plus')
            ->button()
            ->slideOver()
            ->modalWidth(Width::ExtraLarge)
            ->modalHeading('New module')
            ->modalDescription('A module is a chapter of the course. You’ll add its lessons next.')
            ->modalSubmitActionLabel('Add module')
            ->model(Module::class)
            ->schema(ELearningSchema::moduleForm())
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, Schema $schema): void {
                    DB::transaction(function () use ($data, $schema): void {
                        $module = CreateModuleJob::dispatchSync($data);
                        assert($module instanceof Module);
                        $schema->model($module)->saveRelationships();

                        AddModuleJob::dispatchSync([
                            'course_ulid' => $this->course()->ulid,
                            'module_ulid' => $module->ulid,
                        ]);
                    });

                    Notification::make()->success()->title('Module added')->body('Now add its lessons.')->send();
                },
            )
            ->visible(
                fn(): bool => userCan(CourseModule::permission('create')) && userCan(Module::permission('create')),
            );
    }

    public function addModuleAction(): Action
    {
        return Action::make('addModule')
            ->label('Add existing module')
            ->icon('heroicon-m-square-2-stack')
            ->button()
            ->outlined()
            ->color('gray')
            ->modalHeading('Add an existing module')
            ->modalDescription('The module and its lessons are shared with the other courses that use it.')
            ->modalSubmitActionLabel('Add')
            ->schema([
                Select::make('module_ulid')
                    ->label('Module')
                    ->searchable()
                    ->required()
                    ->options(
                        fn(): array => Module::query()
                            ->whereDoesntHave('courseModules', fn($query) => $query->where(
                                'course_id',
                                $this->course()->id,
                            ))
                            ->withCount('lessonModules')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn(Module $module): array => [
                                $module->ulid => "{$module->name} · {$module->lesson_modules_count} lessons",
                            ])
                            ->all(),
                    ),
            ])
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data): void {
                    AddModuleJob::dispatchSync([
                        'course_ulid' => $this->course()->ulid,
                        'module_ulid' => $data['module_ulid'],
                    ]);

                    Notification::make()->success()->title('Module added')->send();
                },
            )
            ->visible(fn(): bool => userCan(CourseModule::permission('create')));
    }

    public function editModuleAction(): Action
    {
        return Action::make('editModule')
            ->label('Edit')
            ->icon('heroicon-m-pencil-square')
            ->link()
            ->tooltip('Edit the module’s name, summary and cover')
            ->slideOver()
            ->modalWidth(Width::ExtraLarge)
            ->modalHeading(fn(Module $record): string => "Edit “{$record->name}”")
            ->modalSubmitActionLabel('Save module')
            ->record(
                fn(array $arguments): ?Module => Module::query()
                    ->where('ulid', $arguments['module'] ?? null)
                    ->first(),
            )
            ->fillForm(fn(Module $record): array => [
                ...$record->attributesToArray(),
                'is_active' => $record->is_active->value,
            ])
            ->schema(fn(Module $record): array => [
                ELearningSchema::sharedCallout(ELearningSchema::modulePlacements($record), 'module'),
                ...ELearningSchema::moduleForm(),
            ])
            ->action(
                /** @param array<string, mixed> $data */
                function (array $data, Module $record, Schema $schema): void {
                    UpdateModuleJob::dispatchSync($data, $record->ulid);
                    $schema->saveRelationships();

                    Notification::make()->success()->title('Module saved')->send();
                },
            )
            ->visible(fn(): bool => userCan(Module::permission('edit')));
    }

    public function removeModuleAction(): Action
    {
        return Action::make('removeModule')
            ->label('Remove')
            ->icon('heroicon-m-x-mark')
            ->link()
            ->color('danger')
            ->tooltip('Take this module out of the course (it stays in the library)')
            ->requiresConfirmation()
            ->modalHeading('Remove this module from the course?')
            ->modalDescription(
                'The module and its lessons stay in the library, and in any other course that uses them. Students’ progress in this course is recalculated.',
            )
            ->modalSubmitActionLabel('Remove')
            ->action(function (array $arguments): void {
                $link = CourseModule::query()
                    ->where('ulid', $arguments['link'] ?? null)
                    ->firstOrFail();

                RemoveModuleJob::dispatchSync($link, $this->actor());

                Notification::make()->success()->title('Module removed from the course')->send();
            })
            ->visible(fn(): bool => userCan(CourseModule::permission('delete')));
    }

    public function canReorderModules(): bool
    {
        return userCan(CourseModule::permission('edit'));
    }
}
