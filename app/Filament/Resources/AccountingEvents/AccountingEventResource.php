<?php

namespace App\Filament\Resources\AccountingEvents;

use App\Filament\Resources\AccountingEvents\Pages\CreateAccountingEvent;
use App\Filament\Resources\AccountingEvents\Pages\EditAccountingEvent;
use App\Filament\Resources\AccountingEvents\Pages\ListAccountingEvents;
use App\Filament\Resources\AccountingEvents\Pages\ViewAccountingEvent;
use App\Filament\Resources\AccountingEvents\RelationManagers\RequisitionsRelationManager;
use App\Models\AccountingEvent;
use App\Models\Mission;
use App\Models\PRFEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingEventResource extends Resource
{
    protected static ?string $model = AccountingEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MorphToSelect::make('accountingEventable')
                    ->preload()
                    ->label('ABC')
                    ->columnSpanFull()
                    ->types([
                        Type::make(Mission::class)
                            ->titleAttribute('ulid')
                            ->label('Mission'),
                        Type::make(PRFEvent::class)
                            ->titleAttribute('name')
                            ->label('Event'),
                    ]),
                TextInput::make('responsible_desk')
                    ->required()
                    ->numeric(),
                Textarea::make('name')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                DatePicker::make('due_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ulid')
                    ->searchable(),
                TextColumn::make('accountingEventable.name')
                    ->label('Accounting Eventable')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('responsible_desk')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RequisitionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountingEvents::route('/'),
            'create' => CreateAccountingEvent::route('/create'),
            'view' => ViewAccountingEvent::route('/{record}'),
            'edit' => EditAccountingEvent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
