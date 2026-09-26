<?php

namespace App\Filament\Resources\Courses;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ELearningSchema;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\Pages\ViewCourse;
use App\Filament\Resources\Courses\RelationManagers\CourseGroupsRelationManager;
use App\Filament\Resources\Courses\RelationManagers\CurriculumRelationManager;
use App\Filament\Resources\Courses\RelationManagers\LessonMembersRelationManager;
use App\Jobs\Course\UpdateJob as UpdateCourseJob;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Course';

    protected static ?string $pluralModelLabel = 'Courses';

    protected static ?string $navigationTooltip = 'Build courses from modules and lessons';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ELearningSchema::courseForm());
    }

    /**
     * Publishing checks the course is complete first; anything missing is listed instead.
     */
    public static function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->icon('heroicon-m-eye')
            ->color('success')
            ->modalIcon('heroicon-o-eye')
            ->modalHeading(fn(Course $record): string => $record->publishProblems() === []
                ? "Publish “{$record->name}”?"
                : 'Not ready to publish yet')
            ->modalDescription(fn(Course $record): string => $record->publishProblems() === []
                ? 'Students will see this course in the app straight away.'
                : 'Fix these in the Curriculum tab first:')
            ->schema(fn(Course $record): array => (
                $record->publishProblems() === []
                    ? []
                    : [Text::make(
                        new HtmlString(
                            '<ul class="list-disc ps-5 text-sm">'
                            . collect($record->publishProblems())
                                ->map(fn(string $problem): string => '<li>' . e($problem) . '</li>')
                                ->implode('')
                            . '</ul>',
                        ),
                    )]
            ))
            ->modalSubmitActionLabel('Publish')
            ->modalSubmitAction(fn(Action $action, Course $record): Action|false => $record->publishProblems() === []
                ? $action
                : false)
            ->action(function (Course $record): void {
                abort_unless($record->publishProblems() === [], 422);

                UpdateCourseJob::dispatchSync(['is_active' => PRFActiveStatus::ACTIVE], $record->ulid);

                Notification::make()->success()->title('Course published')->send();
            })
            ->visible(
                fn(Course $record): bool => (
                    $record->is_active !== PRFActiveStatus::ACTIVE
                    && userCan(Course::permission('edit'))
                ),
            );
    }

    public static function hideAction(): Action
    {
        return Action::make('hide')
            ->label('Hide')
            ->icon('heroicon-m-eye-slash')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(fn(Course $record): string => "Hide “{$record->name}” from students?")
            ->modalDescription('Enrolled students keep their progress and see the course again when you publish it.')
            ->modalSubmitActionLabel('Hide')
            ->action(function (Course $record): void {
                UpdateCourseJob::dispatchSync(['is_active' => PRFActiveStatus::INACTIVE], $record->ulid);

                Notification::make()->success()->title('Course hidden')->send();
            })
            ->visible(
                fn(Course $record): bool => (
                    $record->is_active === PRFActiveStatus::ACTIVE
                    && userCan(Course::permission('edit'))
                ),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('')
                    ->collection(Course::THUMBNAILS)
                    ->imageWidth(72)
                    ->imageHeight(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->grow(false),

                TextColumn::make('name')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Course $record): string => str($record->description)->limit(90)->toString())
                    ->wrap()
                    ->grow(),

                TextColumn::make('is_active')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn(Course $record): string => ELearningSchema::statusLabel($record->is_active))
                    ->color(fn(Course $record): string => ELearningSchema::statusColor($record->is_active))
                    ->icon(fn(Course $record): string => ELearningSchema::statusIcon($record->is_active))
                    ->sortable(),

                TextColumn::make('course_modules_count')
                    ->label('Modules')
                    ->counts('courseModules')
                    ->alignCenter()
                    ->formatStateUsing(fn(int $state): string => $state === 0 ? 'Empty' : (string) $state)
                    ->color(fn(int $state): string => $state === 0 ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('course_members_count')
                    ->label('Students')
                    ->counts('courseMembers')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('course_groups_count')
                    ->label('Groups')
                    ->counts('courseGroups')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->dateTimeTooltip('j M Y, g:i A', Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->date('j M Y')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Visibility')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Published',
                        PRFActiveStatus::INACTIVE->value => 'Hidden',
                    ])
                    ->native(false),

                Filter::make('empty_courses')
                    ->label('No modules yet')
                    ->query(fn(Builder $query): Builder => $query->doesntHave('courseModules'))
                    ->toggle(),

                Filter::make('with_students')
                    ->label('Has students')
                    ->query(fn(Builder $query): Builder => $query->has('courseMembers'))
                    ->toggle(),

                TrashedFilter::make()->native(false),
            ])
            ->recordUrl(fn(Course $record): ?string => match (true) {
                userCan(Course::permission('edit')) => self::getUrl('edit', ['record' => $record]),
                userCan(Course::permission('view')) => self::getUrl('view', ['record' => $record]),
                default => null,
            })
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Build')
                        ->icon('heroicon-m-queue-list')
                        ->visible(fn() => userCan(Course::permission('edit'))),
                    ViewAction::make()->visible(fn() => userCan(Course::permission('view'))),
                    self::publishAction(),
                    self::hideAction(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => userCan(Course::permission('delete'))),

                    ForceDeleteBulkAction::make()->visible(fn() => userCan(Course::permission('delete'))),

                    RestoreBulkAction::make()->visible(fn() => userCan(Course::permission('delete'))),

                    BulkAction::make('bulk_publish')
                        ->label('Publish selected')
                        ->icon('heroicon-m-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription(
                            'Courses that aren’t complete yet (no modules, empty modules or lessons without content) are skipped.',
                        )
                        ->action(
                            /** @param Collection<int, Course> $records */
                            function (Collection $records): void {
                                $ready = $records
                                    ->whereInstanceOf(Course::class)
                                    ->filter(fn(Course $course): bool => $course->publishProblems() === []);
                                $ready->each(fn(Course $course) => UpdateCourseJob::dispatchSync([
                                    'is_active' => PRFActiveStatus::ACTIVE,
                                ], $course->ulid));

                                $skipped = $records->count() - $ready->count();

                                Notification::make()
                                    ->success()
                                    ->title("Published {$ready->count()} " . str('course')->plural($ready->count()))
                                    ->body(
                                        $skipped > 0
                                            ? "{$skipped} skipped: open them and check the Curriculum tab."
                                            : null,
                                    )
                                    ->send();
                            },
                        )
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn() => userCan(Course::permission('edit'))),

                    BulkAction::make('bulk_hide')
                        ->label('Hide selected')
                        ->icon('heroicon-m-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(
                            /** @param Collection<int, Course> $records */
                            fn(Collection $records) => $records
                                ->whereInstanceOf(Course::class)
                                ->each(fn(Course $course) => UpdateCourseJob::dispatchSync([
                                    'is_active' => PRFActiveStatus::INACTIVE,
                                ], $course->ulid)),
                        )
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn() => userCan(Course::permission('edit'))),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No courses yet')
            ->emptyStateDescription('Create a course, then build it from modules and lessons.');
    }

    public static function getRelations(): array
    {
        return [
            CurriculumRelationManager::class,
            LessonMembersRelationManager::class,
            CourseGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'view' => ViewCourse::route('/{record}'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan(Course::permission('viewAny'));
    }
}
