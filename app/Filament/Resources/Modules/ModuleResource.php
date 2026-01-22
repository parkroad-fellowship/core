<?php

namespace App\Filament\Resources\Modules;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\MediaSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Modules\Pages\CreateModule;
use App\Filament\Resources\Modules\Pages\EditModule;
use App\Filament\Resources\Modules\Pages\ListModules;
use App\Filament\Resources\Modules\Pages\ViewModule;
use App\Filament\Resources\Modules\RelationManagers\LessonMembersRelationManager;
use App\Filament\Resources\Modules\RelationManagers\LessonModulesRelationManager;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Module';

    protected static ?string $pluralModelLabel = 'Modules';

    protected static ?string $navigationTooltip = 'Manage educational modules and lesson containers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module Information')
                    ->description('Enter the basic details about this module')
                    ->icon('heroicon-o-cube')
                    ->collapsible()
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Module Name',
                            placeholder: 'e.g., Prayer Fundamentals, Bible Study Methods',
                            helperText: 'Choose a clear name that describes the topic or theme of this module',
                        ),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Module Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            helperText: 'Active modules are visible to students. Set to Inactive to hide the module temporarily.',
                        ),
                    ])
                    ->columns(2),

                Section::make('Module Description')
                    ->description('Provide a detailed overview of the module content')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Module Description',
                            rows: 4,
                            required: true,
                            placeholder: 'Describe the module content, learning objectives, and what students will achieve...',
                            helperText: 'Write a comprehensive description that explains what lessons are included and what students will learn',
                        ),
                    ]),

                Section::make('Module Images')
                    ->description('Add visual content to represent this module')
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        MediaSchema::uploadField(
                            collection: Module::THUMBNAILS,
                            label: 'Module Thumbnails',
                            multiple: true,
                            maxFiles: 10,
                            acceptedFileTypes: ['image/*'],
                            helperText: 'Upload images that represent this module. The first image will be used as the main thumbnail. Recommended size: 800x450 pixels.',
                        ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Module Name')
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 80) : null)
                    ->icon('heroicon-o-cube')
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lesson_modules_count')
                    ->label('Lessons')
                    ->counts('lessonModules')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-academic-cap')
                    ->tooltip('Number of lessons in this module'),

                TextColumn::make('lesson_members_count')
                    ->label('Students')
                    ->counts('lessonMembers')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of students enrolled in this module'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Added: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->placeholder('All Statuses'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view module')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit module')),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit module')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete module')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete module')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete module')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit module')),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit module')),
                ])->visible(fn () => userCan('delete module')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LessonModulesRelationManager::class,
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
        return userCan('viewAny module');
    }
}
