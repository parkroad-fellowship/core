<?php

namespace App\Filament\Resources\Lessons;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Filament\Forms\Schemas\ELearningSchema;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Lessons\Pages\ViewLesson;
use App\Filament\Resources\Lessons\RelationManagers\LessonMembersRelationManager;
use App\Jobs\Lesson\UpdateJob;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Lesson';

    protected static ?string $pluralModelLabel = 'Lessons';

    protected static ?string $navigationLabel = 'Lesson library';

    protected static ?string $navigationTooltip = 'Every lesson, whichever modules use it';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ELearningSchema::sharedCallout([], 'lesson')
                ->visible(
                    fn(?Lesson $record): bool => (
                        $record !== null
                        && count(ELearningSchema::lessonPlacements($record)) > 1
                    ),
                )
                ->description(fn(?Lesson $record): string => $record === null
                    ? ''
                    : 'Used in: '
                        . implode('; ', ELearningSchema::lessonPlacements($record))
                        . '. Changes you save here apply everywhere.'),
            Section::make()->columnSpanFull()->schema(ELearningSchema::lessonForm()),
            Section::make('Where this lesson is used')
                ->columnSpanFull()
                ->icon('heroicon-o-queue-list')
                ->visibleOn('view')
                ->schema([
                    Text::make(fn(?Lesson $record): string => $record === null
                        || ELearningSchema::lessonPlacements($record) === []
                            ? 'Not in any module yet. Add it from a module’s Lessons tab or a course’s Curriculum tab.'
                            : implode(' · ', ELearningSchema::lessonPlacements($record))),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Lesson')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Lesson $record): string => Str::limit((string) $record->description, 90))
                    ->wrap()
                    ->grow(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(?PRFLessonType $state): string => $state?->getLabel() ?? '')
                    ->color(fn(?PRFLessonType $state): string => ELearningSchema::typeColor($state))
                    ->icon(fn(?PRFLessonType $state): string => ELearningSchema::typeIcon($state))
                    ->sortable(),

                IconColumn::make('has_content')
                    ->label('Content')
                    ->state(fn(Lesson $record): bool => $record->hasContent())
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-exclamation-triangle')
                    ->falseColor('danger')
                    ->tooltip(fn(Lesson $record): string => $record->hasContent()
                        ? 'Ready'
                        : 'No content yet: students would see an empty lesson')
                    ->alignCenter(),

                TextColumn::make('lesson_modules_count')
                    ->label('Used in')
                    ->counts('lessonModules')
                    ->badge()
                    ->color(fn(int $state): string => $state === 0 ? 'gray' : ($state > 1 ? 'warning' : 'primary'))
                    ->formatStateUsing(fn(int $state): string => $state === 0
                        ? 'No module'
                        : trans_choice(':count module|:count modules', $state))
                    ->tooltip(
                        fn(Lesson $record): ?string => (
                            implode('; ', ELearningSchema::lessonPlacements($record)) ?: null
                        ),
                    ),

                TextColumn::make('lesson_members_count')
                    ->label('Completed by')
                    ->counts('lessonMembers')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('is_active')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn(?PRFActiveStatus $state): string => ELearningSchema::statusLabel($state))
                    ->color(fn(?PRFActiveStatus $state): string => ELearningSchema::statusColor($state))
                    ->icon(fn(?PRFActiveStatus $state): string => ELearningSchema::statusIcon($state))
                    ->sortable(),

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
                SelectFilter::make('type')->label('Type')->options(PRFLessonType::getOptions())->native(false),

                Filter::make('unused')
                    ->label('Not in any module')
                    ->query(fn(Builder $query): Builder => $query->doesntHave('lessonModules'))
                    ->toggle(),

                SelectFilter::make('is_active')
                    ->label('Visibility')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Published',
                        PRFActiveStatus::INACTIVE->value => 'Hidden',
                    ])
                    ->native(false),

                TrashedFilter::make()->native(false),
            ])
            ->recordUrl(fn(Lesson $record): ?string => match (true) {
                userCan(Lesson::permission('edit')) => self::getUrl('edit', ['record' => $record]),
                userCan(Lesson::permission('view')) => self::getUrl('view', ['record' => $record]),
                default => null,
            })
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-m-pencil-square')
                        ->visible(fn() => userCan(Lesson::permission('edit'))),
                    ViewAction::make()->visible(fn() => userCan(Lesson::permission('view'))),
                    Action::make('toggle_status')
                        ->label(fn(Lesson $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'Hide'
                            : 'Publish')
                        ->icon(fn(Lesson $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'heroicon-m-eye-slash'
                            : 'heroicon-m-eye')
                        ->color(fn(Lesson $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'gray'
                            : 'success')
                        ->action(fn(Lesson $record) => UpdateJob::dispatchSync([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE
                                ? PRFActiveStatus::INACTIVE
                                : PRFActiveStatus::ACTIVE,
                        ], $record->ulid))
                        ->visible(fn() => userCan(Lesson::permission('edit'))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => userCan(Lesson::permission('delete'))),
                    ForceDeleteBulkAction::make()->visible(fn() => userCan(Lesson::permission('delete'))),
                    RestoreBulkAction::make()->visible(fn() => userCan(Lesson::permission('delete'))),
                    BulkAction::make('activate')
                        ->label('Publish selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn(Lesson $record) => UpdateJob::dispatchSync([
                                'is_active' => PRFActiveStatus::ACTIVE,
                            ], $record->ulid));
                        })
                        ->requiresConfirmation()
                        ->visible(fn() => userCan(Lesson::permission('edit'))),
                    BulkAction::make('deactivate')
                        ->label('Hide selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn(Lesson $record) => UpdateJob::dispatchSync([
                                'is_active' => PRFActiveStatus::INACTIVE,
                            ], $record->ulid));
                        })
                        ->requiresConfirmation()
                        ->visible(fn() => userCan(Lesson::permission('edit'))),
                ])->visible(fn() => userCan(Lesson::permission('delete'))),
            ])
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with('media'))
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No lessons yet')
            ->emptyStateDescription(
                'Lessons are usually written from a module or a course’s Curriculum tab, so they land in the right place.',
            );
    }

    public static function getRelations(): array
    {
        return [
            LessonMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessons::route('/'),
            'create' => CreateLesson::route('/create'),
            'view' => ViewLesson::route('/{record}'),
            'edit' => EditLesson::route('/{record}/edit'),
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
        return userCan(Lesson::permission('viewAny'));
    }
}
