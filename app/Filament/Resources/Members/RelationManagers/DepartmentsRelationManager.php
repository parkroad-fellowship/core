<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DepartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'departments';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $label = 'Department';

    protected static ?string $pluralLabel = 'Departments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🏢 Department Information')
                    ->description('Ministry department details and involvement')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label('🏢 Department Name')
                                    ->helperText('Name of the ministry department')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Youth Ministry, Worship Team'),

                                Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Current status of the department')
                                    ->options(PRFActiveStatus::getOptions())
                                    ->default(PRFActiveStatus::ACTIVE)
                                    ->required()
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('🏢 Department')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip('Department name'),

                TextColumn::make('is_active')
                    ->badge()
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->name)
                    ->color(fn ($state) => PRFActiveStatus::fromValue($state)->getColor())
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->tooltip('Department status'),

                TextColumn::make('members_count')
                    ->badge()
                    ->label('👥 Members')
                    ->counts('members')
                    ->color('info')
                    ->tooltip('Number of members in this department'),

                TextColumn::make('created_at')
                    ->label('📅 Created')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date department was created'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                PRFActiveStatus::getTernaryFilter(),

                Filter::make('has_head')
                    ->label('Has Department Head')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('head_of_department'))
                    ->toggle(),

                Filter::make('members_count')
                    ->label('Member Count')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('min_members')
                                    ->label('Minimum Members')
                                    ->numeric()
                                    ->placeholder('e.g., 5'),
                                TextInput::make('max_members')
                                    ->label('Maximum Members')
                                    ->numeric()
                                    ->placeholder('e.g., 50'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_members'],
                                fn (Builder $query, $count): Builder => $query->has('members', '>=', $count),
                            )
                            ->when(
                                $data['max_members'],
                                fn (Builder $query, $count): Builder => $query->has('members', '<=', $count),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_members'] ?? null) {
                            $indicators[] = 'Min members: '.$data['min_members'];
                        }
                        if ($data['max_members'] ?? null) {
                            $indicators[] = 'Max members: '.$data['max_members'];
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Department created')
                            ->body("New department '{$record->name}' has been created.")
                            ->success()
                            ->send();
                    }),

                AttachAction::make()
                    ->icon('heroicon-o-link')
                    ->color(Color::Blue)
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE))
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Department attached')
                            ->body("Member has been added to '{$record->name}' department.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('view_members')
                    ->label('View Members')
                    ->icon('heroicon-o-users')
                    ->color(Color::Gray)
                    ->action(function ($record) {
                        // Logic to view department members
                        Notification::make()
                            ->title('Department members')
                            ->body("Viewing members of '{$record->name}' department.")
                            ->info()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->members_count > 0)
                    ->tooltip('View all members in this department'),

                EditAction::make()
                    ->color(Color::Orange)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Department updated')
                            ->success()
                            ->send();
                    }),

                DetachAction::make()
                    ->color(Color::Red)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Department detached')
                            ->body("Member has been removed from '{$record->name}' department.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_departments')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE]));

                            Notification::make()
                                ->title('Departments activated')
                                ->body("{$count} departments have been activated.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('deactivate_departments')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Orange)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE]));

                            Notification::make()
                                ->title('Departments deactivated')
                                ->body("{$count} departments have been deactivated.")
                                ->success()
                                ->send();
                        }),

                    DetachBulkAction::make()
                        ->color(Color::Red),

                    DeleteBulkAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => $this->canCreate()),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    protected function canCreate(): bool
    {
        return userCan('create department');
    }
}
