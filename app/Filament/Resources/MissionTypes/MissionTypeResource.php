<?php

namespace App\Filament\Resources\MissionTypes;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\MissionTypes\Pages\CreateMissionType;
use App\Filament\Resources\MissionTypes\Pages\EditMissionType;
use App\Filament\Resources\MissionTypes\Pages\ListMissionTypes;
use App\Filament\Resources\MissionTypes\Pages\ViewMissionType;
use App\Models\MissionType;
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

class MissionTypeResource extends Resource
{
    protected static ?string $model = MissionType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Mission Type';

    protected static ?string $pluralModelLabel = 'Mission Types';

    protected static ?string $navigationTooltip = 'Manage different types of missions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Enter the essential details for this mission type. Mission types help categorize and organize your missions effectively.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Mission Type Name',
                            placeholder: 'e.g., School Mission, Community Outreach, Conference',
                            required: true,
                            helperText: 'Choose a clear, descriptive name that identifies the type of mission. This name will be displayed when creating new missions.',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Status Settings')
                    ->description('Control the visibility and availability of this mission type in the system.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            required: true,
                            hiddenOnCreate: true,
                            helperText: 'Active mission types can be selected when creating new missions. Inactive types are hidden from selection but existing missions remain unaffected.',
                        ),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Mission Type')
                    ->icon('heroicon-o-tag')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('missions_count')
                    ->label('Missions Count')
                    ->counts('missions')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-academic-cap')
                    ->tooltip('Number of missions using this type'),

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
                        ->visible(fn () => userCan('view mission type')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission type')),
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
                        ->visible(fn () => userCan('edit mission type')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission type')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission type')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete mission type')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit mission type')),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit mission type')),
                ])->visible(fn () => userCan('delete mission type')),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissionTypes::route('/'),
            'create' => CreateMissionType::route('/create'),
            'view' => ViewMissionType::route('/{record}'),
            'edit' => EditMissionType::route('/{record}/edit'),
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
        return userCan('viewAny mission type');
    }
}
