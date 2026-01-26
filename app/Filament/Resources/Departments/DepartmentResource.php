<?php

namespace App\Filament\Resources\Departments;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Pages\ViewDepartment;
use App\Models\Department;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Department';

    protected static ?string $pluralModelLabel = 'Departments';

    protected static ?string $navigationTooltip = 'Manage organizational departments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Department Information')
                    ->description('Create or update a department within your organization')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Department Name',
                                    placeholder: 'e.g., Human Resources, Finance, Marketing',
                                    helperText: 'Enter a clear name that identifies this department',
                                )
                                    ->prefixIcon('heroicon-o-building-office'),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    helperText: 'Active departments can have members assigned; inactive departments are archived',
                                ),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Department Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-office')
                    ->wrap()
                    ->tooltip('The name of this organizational department'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value
                        ? 'This department is active and accepting members'
                        : 'This department is inactive and archived'),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Total number of members assigned to this department'),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable()
                    ->tooltip('When this department was first created'),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable()
                    ->tooltip('When this department was last updated'),

                TextColumn::make('deleted_at')
                    ->label('Date Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this department was removed'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false)
                    ->label('Show Deleted')
                    ->placeholder('Active departments only'),

                SelectFilter::make('is_active')
                    ->label('Filter by Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false)
                    ->placeholder('All statuses'),

                Filter::make('with_members')
                    ->label('Has Members')
                    ->query(fn (Builder $query): Builder => $query->has('members'))
                    ->toggle()
                    ->indicator('Has Members'),

                Filter::make('empty_departments')
                    ->label('No Members')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('members'))
                    ->toggle()
                    ->indicator('Empty'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view department'))
                    ->tooltip('View full department details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit department'))
                    ->tooltip('Make changes to this department')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Department updated')
                            ->body('The department information has been saved successfully.')
                    ),

                Action::make('toggle_status')
                    ->label(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Department $record) {
                        $newStatus = $record->is_active === PRFActiveStatus::ACTIVE->value
                            ? PRFActiveStatus::INACTIVE->value
                            : PRFActiveStatus::ACTIVE->value;
                        $record->update(['is_active' => $newStatus]);

                        $statusLabel = $newStatus === PRFActiveStatus::ACTIVE->value ? 'activated' : 'deactivated';
                        Notification::make()
                            ->success()
                            ->title('Status updated')
                            ->body("The department has been {$statusLabel} successfully.")
                            ->send();
                    })
                    ->tooltip('Change department status')
                    ->visible(fn () => userCan('edit department'))
                    ->requiresConfirmation()
                    ->modalHeading('Change Department Status')
                    ->modalDescription('Are you sure you want to change the status of this department?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Departments activated')
                                ->body("{$count} departments have been activated successfully.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit department'))
                        ->requiresConfirmation(),

                    BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Departments deactivated')
                                ->body("{$count} departments have been deactivated successfully.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit department'))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->searchPlaceholder('Search departments...')
            ->emptyStateHeading('No departments found')
            ->emptyStateDescription('Create your first department to start organizing your team structure.')
            ->emptyStateIcon('heroicon-o-building-office');
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
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'view' => ViewDepartment::route('/{record}'),
            'edit' => EditDepartment::route('/{record}/edit'),
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
        return userCan('viewAny department');
    }
}
