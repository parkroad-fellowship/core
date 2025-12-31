<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFEntryType;
use App\Enums\PRFTransactionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingEventRelationManager extends RelationManager
{
    protected static string $relationship = 'accountingEvent';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $label = 'Accounting Event';

    protected static ?string $pluralLabel = 'Accounting Events';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('📋 Event Details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Event Name')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                    ])
                                    ->native(false),

                                Forms\Components\TextInput::make('responsible_desk')
                                    ->label('Responsible Desk')
                                    ->maxLength(255),

                                Forms\Components\Section::make('💵 Financial Summary')
                                    ->description('Auto-calculated amounts based on allocation entries')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('balance')
                                                    ->label('Balance')
                                                    ->helperText('Current balance of credits and debits')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                Forms\Components\TextInput::make('refund_charge')
                                                    ->label('Refund Charge')
                                                    ->helperText('Transaction charges for refund')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                Forms\Components\TextInput::make('amount_to_refund')
                                                    ->label('Amount to Refund')
                                                    ->helperText('Total amount eligible for refund')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ]),
                                    ])->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('📊 Allocation Entries')
                            ->schema([
                                Forms\Components\Repeater::make('allocationEntries')
                                    ->relationship('allocationEntries')
                                    ->label('Allocation Entries')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\Select::make('entry_type')
                                                    ->label('Entry Type')
                                                    ->helperText('Whether this is a credit or debit entry')
                                                    ->options(PRFEntryType::getOptions())
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\Select::make('expense_category_id')
                                                    ->label('Expense Category')
                                                    ->helperText('Select the appropriate expense category')
                                                    ->relationship('expenseCategory', 'name')
                                                    ->searchable()
                                                    ->preload(),

                                                Forms\Components\Select::make('member_id')
                                                    ->label('Added By')
                                                    ->helperText('Member who added this entry')
                                                    ->relationship('member', 'full_name')
                                                    ->searchable()
                                                    ->preload(),

                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Amount')
                                                    ->helperText('Amount for this entry')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0),
                                            ]),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('unit_cost')
                                                    ->label('Unit Cost')
                                                    ->helperText('Cost per individual item')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->helperText('Number of items')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->live(onBlur: true),

                                                Forms\Components\TextInput::make('charge')
                                                    ->label('Charge')
                                                    ->helperText('Transaction or processing charge')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0),

                                                Forms\Components\Select::make('charge_type')
                                                    ->options(PRFTransactionType::getOptions())
                                                    ->label('Charge Type')
                                                    ->helperText('Type of charge for this entry')
                                                    ->native(false),
                                            ]),

                                        Forms\Components\Textarea::make('narration')
                                            ->label('Narration')
                                            ->helperText('Detailed description of this allocation entry')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('confirmation_message')
                                            ->label('Confirmation Message')
                                            ->helperText('Any confirmation or reference message for this entry')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\SpatieMediaLibraryFileUpload::make('receipts')
                                            ->label('Receipts')
                                            ->helperText('Upload receipt documents or images')
                                            ->collection('allocation-entry-receipts')
                                            ->multiple()
                                            ->preserveFilenames()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->itemLabel(fn (array $state): ?string => ($state['entry_type'] ?? 'Entry').' - KES '.number_format($state['amount'] ?? 0))
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Allocation Entry'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('📋 Event Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('📅 Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('responsible_desk')
                    ->label('👤 Responsible Desk')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('💵 Balance')
                    ->money('KES')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 0 => Color::Green,
                        $state < 0 => Color::Red,
                        default => Color::Gray,
                    }),

                Tables\Columns\TextColumn::make('refund_charge')
                    ->label('💳 Charges')
                    ->money('KES')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount_to_refund')
                    ->label('↩️ To Refund')
                    ->money('KES')
                    ->toggleable()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\ForceDeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
