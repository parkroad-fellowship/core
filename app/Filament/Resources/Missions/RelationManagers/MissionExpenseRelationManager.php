<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
use App\Jobs\MissionExpense\GenerateSummaryJob;
use App\Models\MissionExpense;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MissionExpenseRelationManager extends RelationManager
{
    protected static string $relationship = 'missionExpense';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $label = 'Mission Expense';

    protected static ?string $pluralLabel = 'Mission Expenses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('💰 Budget Overview')
                            ->schema([
                                Section::make('💰 Budget & Payments')
                                    ->description('Manage mission budget, payments and tokens')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('amount_received')
                                                    ->label('Amount Received')
                                                    ->helperText('Total amount received for the mission')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true),

                                                TextInput::make('token_amount')
                                                    ->label('Token Amount')
                                                    ->helperText('Token amount given by the school')
                                                    ->default(0)
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true),

                                                TextInput::make('amount_refunded')
                                                    ->label('Amount Refunded')
                                                    ->helperText('Amount already refunded')
                                                    ->default(0)
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true),

                                                Toggle::make('is_refunded')
                                                    ->label('Mark as Refunded')
                                                    ->helperText('Toggle to mark this expense as refunded')
                                                    ->live()
                                                    ->onIcon('heroicon-m-check')
                                                    ->offIcon('heroicon-m-x-mark'),
                                            ]),
                                    ])->collapsible(),

                                Section::make('📊 Calculated Amounts')
                                    ->description('Auto-calculated amounts based on expenses')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('amount_spent')
                                                    ->label('Total Amount Spent')
                                                    ->helperText('Auto-calculated from individual expenses')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('balance')
                                                    ->label('Balance')
                                                    ->helperText('Remaining balance after expenses')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('amount_to_refund')
                                                    ->label('Amount to Refund')
                                                    ->helperText('Total amount eligible for refund')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('refund_charge')
                                                    ->label('Refund Charge')
                                                    ->helperText('Transaction charges for refund')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ]),
                                    ])->collapsible(),
                            ]),

                        Tab::make('📊 Expense Items')
                            ->schema([
                                Repeater::make('expenses')
                                    ->relationship('expenses')
                                    ->label('Expense Items')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('expense_category_id')
                                                    ->label('Expense Category')
                                                    ->helperText('Select the appropriate expense category')
                                                    ->relationship('expenseCategory', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),

                                                Select::make('charge_type')
                                                    ->label('Charge Type')
                                                    ->helperText('Select how this expense is charged')
                                                    ->options(PRFTransactionType::getOptions())
                                                    ->required()
                                                    ->live(),

                                                Select::make('member_id')
                                                    ->label('Added By')
                                                    ->helperText('Mission member who added this expense')
                                                    ->relationship(
                                                        name: 'member',
                                                        titleAttribute: 'full_name',
                                                        modifyQueryUsing: fn (Builder $query) => $query
                                                            ->whereHas('missionSubscriptions', fn (Builder $query) => $query
                                                                ->where('mission_id', $this->ownerRecord->id)),
                                                    )
                                                    ->searchable()
                                                    ->preload(),
                                            ]),

                                        Textarea::make('narration')
                                            ->label('Expense Description')
                                            ->helperText('Detailed description of what this expense covers')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Textarea::make('confirmation_message')
                                            ->label('Confirmation Message')
                                            ->helperText('Any confirmation or reference message for this expense')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('unit_cost')
                                                    ->label('Unit Cost')
                                                    ->helperText('Cost per individual item')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $set('line_total', $state * $quantity);
                                                    }),

                                                TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->helperText('Number of items')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $unitCost = $get('unit_cost') ?? 0;
                                                        $set('line_total', $state * $unitCost);
                                                    }),

                                                TextInput::make('charge')
                                                    ->label('Transaction Charge')
                                                    ->helperText('Additional transaction fees')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->default(0),

                                                TextInput::make('line_total')
                                                    ->label('Line Total')
                                                    ->helperText('Automatically calculated from unit cost × quantity')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(true),
                                            ]),

                                        TextInput::make('line_total')
                                            ->label('Line Total')
                                            ->helperText('Automatically calculated from unit cost × quantity')
                                            ->numeric()
                                            ->prefix('KES')
                                            ->disabled()
                                            ->dehydrated(true),

                                        SpatieMediaLibraryFileUpload::make('receipts')
                                            ->label('Receipt Images')
                                            ->helperText('Upload photos or scans of receipts for this expense')
                                            ->multiple()
                                            ->collection('receipts')
                                            ->disk(config('filament.default_filesystem_disk'))
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])
                                            ->maxSize(5120)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->itemLabel(fn (array $state): ?string => $state['narration'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Expense Item')
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                                        return $data;
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount_received')
            ->columns([
                TextColumn::make('amount_received')
                    ->label('💸 Received')
                    ->money('KES')
                    ->sortable()
                    ->tooltip('Total amount received for the mission'),

                TextColumn::make('amount_spent')
                    ->label('💰 Spent')
                    ->money('KES')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? Color::Orange : Color::Gray)
                    ->tooltip('Total amount spent on expenses'),

                TextColumn::make('balance')
                    ->label('💵 Balance')
                    ->money('KES')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 0 => Color::Green,
                        $state < 0 => Color::Red,
                        default => Color::Gray,
                    })
                    ->tooltip('Remaining balance'),

                TextColumn::make('token_amount')
                    ->label('🎫 Token')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Token amount from school'),

                TextColumn::make('amount_to_refund')
                    ->label('↩️ To Refund')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Blue)
                    ->tooltip('Amount eligible for refund'),

                TextColumn::make('amount_refunded')
                    ->label('✅ Refunded')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Green)
                    ->tooltip('Amount already refunded'),

                TextColumn::make('refund_charge')
                    ->label('💳 Charges')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Transaction charges for refund'),

                IconColumn::make('is_refunded')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($state) => $state ? 'Refunded' : 'Not refunded'),
            ])
            ->filters([
                TrashedFilter::make(),

                TernaryFilter::make('is_refunded')
                    ->label('Refund Status')
                    ->placeholder('All expenses')
                    ->trueLabel('Refunded')
                    ->falseLabel('Not refunded'),

                Filter::make('balance_range')
                    ->label('Balance Range')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('balance_from')
                                    ->label('From')
                                    ->numeric()
                                    ->prefix('KES'),
                                TextInput::make('balance_to')
                                    ->label('To')
                                    ->numeric()
                                    ->prefix('KES'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_from'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '>=', $balance),
                            )
                            ->when(
                                $data['balance_to'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '<=', $balance),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['balance_from'] ?? null) {
                            $indicators[] = 'Balance from: KES '.number_format($data['balance_from']);
                        }
                        if ($data['balance_to'] ?? null) {
                            $indicators[] = 'Balance to: KES '.number_format($data['balance_to']);
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Mission expense created successfully')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->doesntExist()),
            ])
            ->recordActions([
                Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-m-arrow-path')
                    ->color(Color::Blue)
                    ->requiresConfirmation()
                    ->modalDescription('This will recalculate all expense summaries and balances.')
                    ->action(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Recalculation started')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Recalculate expense summaries'),

                ViewAction::make()
                    ->color(Color::Gray),

                EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Mission expense updated')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->color(Color::Red),

                ForceDeleteAction::make()
                    ->color(Color::Red),

                RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    BulkAction::make('mark_refunded')
                        ->label('Mark as Refunded')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_refunded' => true]);
                                GenerateSummaryJob::dispatch($record);
                            });

                            Notification::make()
                                ->title('Expenses marked as refunded')
                                ->body(count($records).' expenses have been marked as refunded.')
                                ->success()
                                ->send();
                        }),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
