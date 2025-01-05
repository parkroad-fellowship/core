<?php

namespace App\Filament\Resources;

use App\Enums\PRFChannelType;
use App\Enums\PRFMpesaTransactionType;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\Mission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Assume that for now, we only have mission expenses. Once the scope expands to other areas
                // we can consider adding the morph field for the mission
                Forms\Components\Select::make('expenseable_id')
                    ->required()
                    ->relationship('school', 'name'),
                Forms\Components\Select::make('expense_category_id')
                    ->required()
                    ->relationship('expenseCategory', 'name'),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->required()
                            ->relationship('member', 'first_name'),
                        Forms\Components\Select::make('channel_type')
                            ->label('Mode of Payment')
                            ->required()
                            ->options(PRFChannelType::getOptions()),
                        Forms\Components\Select::make('charge_type')
                            ->label('Applicable Charge')
                            ->required()
                            ->options(PRFMpesaTransactionType::getOptions()),
                    ])
                    ->columns(3),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Unit Cost')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('line_total')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('charge')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(4),
                Forms\Components\Textarea::make('confirmation_message')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expenseable.school.name')
                    ->label('Mission')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->numeric()
                    ->sortable()
                    ->label('Added By')
                    ->wrap(),
                Tables\Columns\TextColumn::make('expenseCategory.name')
                    ->label('Category')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Unit Cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('Line Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Added On'),
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
                Tables\Actions\ViewAction::make()->visible(fn() => userCan('view expense')),
                Tables\Actions\EditAction::make()->visible(fn() => userCan('edit expense')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn() => userCan('delete expense')),
            ]);
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
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
        return userCan('viewAny expense');
    }
}
