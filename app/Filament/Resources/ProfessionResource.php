<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\ProfessionResource\Pages;
use App\Models\Profession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ProfessionResource extends Resource
{
    protected static ?string $model = Profession::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = '⚙️ System Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Professions';

    protected static ?string $modelLabel = 'Profession';

    protected static ?string $pluralModelLabel = 'Professions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💼 Profession Information')
                    ->description('Define professional categories for member profiles')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('💼 Profession Name')
                                    ->helperText('Enter the profession or career field name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Software Engineer, Teacher, Doctor')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-briefcase'),

                                Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Set profession availability status')
                                    ->required()
                                    ->options(PRFActiveStatus::getOptions())
                                    ->default(PRFActiveStatus::ACTIVE->value)
                                    ->hiddenOn('create')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-check-circle'),
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
                Tables\Columns\TextColumn::make('name')
                    ->label('💼 Profession')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-briefcase')
                    ->color(Color::Blue)
                    ->tooltip('Professional category'),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('👥 Members')
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

                Tables\Columns\IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Profession is active' : 'Profession is inactive'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date profession was created'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date profession was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active professions only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Professions',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Professions',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view profession')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit profession'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Profession updated!')
                                ->body('Profession information has been updated successfully.')
                        ),

                    Tables\Actions\Action::make('toggle_status')
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

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete profession')),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete profession')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_professions')
                        ->label('✅ Activate Selected')
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

                    Tables\Actions\BulkAction::make('deactivate_professions')
                        ->label('❌ Deactivate Selected')
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

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete profession')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('🔍 Search professions...')
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
            'index' => Pages\ListProfessions::route('/'),
            'create' => Pages\CreateProfession::route('/create'),
            'view' => Pages\ViewProfession::route('/{record}'),
            'edit' => Pages\EditProfession::route('/{record}/edit'),
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
