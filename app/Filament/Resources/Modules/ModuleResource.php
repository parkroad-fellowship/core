<?php

namespace App\Filament\Resources\Modules;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ELearningSchema;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\Pages\ViewModule;
use App\Filament\Resources\Modules\RelationManagers\LessonMembersRelationManager;
use App\Filament\Resources\Modules\RelationManagers\LessonsRelationManager;
use App\Jobs\Module\UpdateJob;
use App\Models\Module;
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
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Module';

    protected static ?string $pluralModelLabel = 'Modules';

    protected static ?string $navigationLabel = 'Module library';

    protected static ?string $navigationTooltip = 'Every module, whichever courses use it';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ELearningSchema::sharedCallout([], 'module')
                ->visible(
                    fn(?Module $record): bool => (
                        $record !== null
                        && count(ELearningSchema::modulePlacements($record)) > 1
                    ),
                )
                ->description(fn(?Module $record): string => $record === null
                    ? ''
                    : 'Used in the courses: '
                        . implode('; ', ELearningSchema::modulePlacements($record))
                        . '. Changes you save here apply to all of them.'),
            Section::make()->columnSpanFull()->schema(ELearningSchema::moduleForm()),
            Section::make('Courses using this module')
                ->columnSpanFull()
                ->icon('heroicon-o-book-open')
                ->visibleOn('view')
                ->schema([
                    Text::make(fn(?Module $record): string => $record === null
                        || ELearningSchema::modulePlacements($record) === []
                            ? 'Not in any course yet. Add it from a course’s Curriculum tab.'
                            : implode(' · ', ELearningSchema::modulePlacements($record))),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')
                    ->label('')
                    ->collection(Module::THUMBNAILS)
                    ->imageWidth(72)
                    ->imageHeight(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                    ->grow(false),

                TextColumn::make('name')
                    ->label('Module')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn(Module $record): string => Str::limit((string) $record->description, 90))
                    ->wrap()
                    ->grow(),

                TextColumn::make('lesson_modules_count')
                    ->label('Lessons')
                    ->counts('lessonModules')
                    ->alignCenter()
                    ->formatStateUsing(fn(int $state): string => $state === 0 ? 'Empty' : (string) $state)
                    ->color(fn(int $state): string => $state === 0 ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('course_modules_count')
                    ->label('Used in')
                    ->counts('courseModules')
                    ->badge()
                    ->color(fn(int $state): string => $state === 0 ? 'gray' : ($state > 1 ? 'warning' : 'primary'))
                    ->formatStateUsing(fn(int $state): string => $state === 0
                        ? 'No course'
                        : trans_choice(':count course|:count courses', $state))
                    ->tooltip(
                        fn(Module $record): ?string => (
                            implode('; ', ELearningSchema::modulePlacements($record)) ?: null
                        ),
                    ),

                TextColumn::make('member_modules_count')
                    ->label('Students')
                    ->counts('memberModules')
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
                Filter::make('unused')
                    ->label('Not in any course')
                    ->query(fn(Builder $query): Builder => $query->doesntHave('courseModules'))
                    ->toggle(),

                Filter::make('empty')
                    ->label('No lessons yet')
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
            ->recordUrl(fn(Module $record): ?string => match (true) {
                userCan(Module::permission('edit')) => self::getUrl('edit', ['record' => $record]),
                userCan(Module::permission('view')) => self::getUrl('view', ['record' => $record]),
                default => null,
            })
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Lessons')
                        ->icon('heroicon-m-queue-list')
                        ->visible(fn() => userCan(Module::permission('edit'))),
                    ViewAction::make()->visible(fn() => userCan(Module::permission('view'))),
                    Action::make('toggle_status')
                        ->label(fn(Module $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'Hide'
                            : 'Publish')
                        ->icon(fn(Module $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'heroicon-m-eye-slash'
                            : 'heroicon-m-eye')
                        ->color(fn(Module $record): string => $record->is_active === PRFActiveStatus::ACTIVE
                            ? 'gray'
                            : 'success')
                        ->action(fn(Module $record) => UpdateJob::dispatchSync([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE
                                ? PRFActiveStatus::INACTIVE
                                : PRFActiveStatus::ACTIVE,
                        ], $record->ulid))
                        ->visible(fn() => userCan(Module::permission('edit'))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn() => userCan(Module::permission('delete'))),
                    ForceDeleteBulkAction::make()->visible(fn() => userCan(Module::permission('delete'))),
                    RestoreBulkAction::make()->visible(fn() => userCan(Module::permission('delete'))),
                    BulkAction::make('activate')
                        ->label('Publish selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn(Module $record) => UpdateJob::dispatchSync([
                                'is_active' => PRFActiveStatus::ACTIVE,
                            ], $record->ulid));
                        })
                        ->requiresConfirmation()
                        ->visible(fn() => userCan(Module::permission('edit'))),
                    BulkAction::make('deactivate')
                        ->label('Hide selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn(Module $record) => UpdateJob::dispatchSync([
                                'is_active' => PRFActiveStatus::INACTIVE,
                            ], $record->ulid));
                        })
                        ->requiresConfirmation()
                        ->visible(fn() => userCan(Module::permission('edit'))),
                ])->visible(fn() => userCan(Module::permission('delete'))),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No modules yet')
            ->emptyStateDescription(
                'Modules are usually created from a course’s Curriculum tab. You can also make one here and add it to courses later.',
            );
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
            LessonMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'view' => ViewModule::route('/{record}'),
            'edit' => EditModule::route('/{record}/edit'),
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
        return userCan(Module::permission('viewAny'));
    }
}
