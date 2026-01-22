<?php

namespace App\Filament\Resources\Professions;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Professions\Pages\CreateProfession;
use App\Filament\Resources\Professions\Pages\EditProfession;
use App\Filament\Resources\Professions\Pages\ListProfessions;
use App\Filament\Resources\Professions\Pages\ViewProfession;
use App\Models\Profession;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ProfessionResource extends Resource
{
    protected static ?string $model = Profession::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Professions';

    protected static ?string $modelLabel = 'Profession';

    protected static ?string $pluralModelLabel = 'Professions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profession Information')
                    ->description('Define professional categories for member profiles')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Profession Name',
                            placeholder: 'e.g., Software Engineer, Teacher, Doctor, Accountant',
                            helperText: 'The occupation or career field name displayed when members select their profession',
                        ),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            helperText: 'Only active professions will appear in dropdown menus',
                        ),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Profession')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-briefcase')
                    ->color(Color::Blue)
                    ->tooltip('Professional category'),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 3 => 'warning',
                        $state <= 10 => 'info',
                        default => 'success',
                    })
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of members in this profession'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Profession is active' : 'Profession is inactive'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date profession was created'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date profession was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active professions only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('is_active')
                    ->label('Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active Professions',
                        PRFActiveStatus::INACTIVE->value => 'Inactive Professions',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view profession')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit profession'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Profession updated!')
                                ->body('Profession information has been updated successfully.')
                        ),

                    Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => ! $record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("Profession has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit profession'))
                        ->requiresConfirmation(),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete profession')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete profession')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_professions')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Professions activated')
                                ->body("{$count} professions have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('deactivate_professions')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Professions deactivated')
                                ->body("{$count} professions have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete profession')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('Search professions...')
            ->emptyStateHeading('No professions found')
            ->emptyStateDescription('Start by adding your first profession to the system.')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->recordClasses(fn ($record) => match (true) {
                ! $record->is_active => 'bg-red-50 border-l-4 border-red-400',
                $record->trashed() => 'bg-gray-50 border-l-4 border-gray-400',
                default => null,
            });
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
            'index' => ListProfessions::route('/'),
            'create' => CreateProfession::route('/create'),
            'view' => ViewProfession::route('/{record}'),
            'edit' => EditProfession::route('/{record}/edit'),
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
        return userCan('viewAny profession');
    }
}
