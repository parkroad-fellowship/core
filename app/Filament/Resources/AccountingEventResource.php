<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingEventResource\Pages;
use App\Filament\Resources\AccountingEventResource\RelationManagers;
use App\Models\AccountingEvent;
use App\Models\Mission;
use App\Models\PRFEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingEventResource extends Resource
{
    protected static ?string $model = AccountingEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Treasurer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\MorphToSelect::make('accountingEventable')
                    ->preload()
                    ->label('ABC')
                    ->columnSpanFull()
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(Mission::class)
                            ->titleAttribute('ulid')
                            ->label('Mission'),
                        Forms\Components\MorphToSelect\Type::make(PRFEvent::class)
                            ->titleAttribute('name')
                            ->label('Event'),
                        Forms\Components\MorphToSelect\Type::make(AccountingEvent::class)
                            ->titleAttribute('name')
                            ->label('Accounting Event'),
                    ]),
                Forms\Components\TextInput::make('requisition_desk')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('name')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ulid')
                    ->searchable(),
                Tables\Columns\TextColumn::make('accountingEventable.name')
                    ->label('Accounting Eventable')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responsible_desk')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RequisitionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingEvents::route('/'),
            'create' => Pages\CreateAccountingEvent::route('/create'),
            'view' => Pages\ViewAccountingEvent::route('/{record}'),
            'edit' => Pages\EditAccountingEvent::route('/{record}/edit'),
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
