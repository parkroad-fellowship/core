<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Department';

    protected static ?string $pluralModelLabel = 'Departments';

    protected static ?string $navigationTooltip = 'Manage organizational departments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department Information')
                    ->description('Define the department details')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Department Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the name of the department')
                            ->placeholder('e.g., Human Resources, Finance'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this department')
                            ->hiddenOn('create'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Department Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-office')
                    ->wrap(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of members in this department'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),

                Tables\Filters\Filter::make('with_members')
                    ->label('Departments with Members')
                    ->query(fn (Builder $query): Builder => $query->has('members')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('empty_departments')
                    ->label('Empty Departments')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('members')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view department'))
                    ->tooltip('View department details'),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit department'))
                    ->tooltip('Edit this department'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Department $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Department $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip('Toggle department status')
                    ->visible(fn () => userCan('edit department')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete department')),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit department')),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit department')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'view' => Pages\ViewDepartment::route('/{record}'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
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
