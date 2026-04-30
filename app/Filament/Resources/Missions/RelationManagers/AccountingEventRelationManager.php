<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class AccountingEventRelationManager extends RelationManager
{
    protected static string $relationship = 'accountingEvent';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $title = '💰 Accounting';

    protected static ?string $label = 'Accounting Event';

    protected static ?string $pluralLabel = 'Accounting Events';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $event = $ownerRecord->accountingEvent;
        if (! $event) {
            return null;
        }

        $total = $event->transactions()->sum('amount');

        return $total > 0 ? 'KES '.number_format($total) : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'success';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Accounting Event')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('📋 Overview')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(3)
                                    ->columnSpanFull()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('📋 Event Name')
                                            ->helperText('A descriptive name for this accounting event')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g., Mission to ABC School - Transport')
                                            ->columnSpan(2),

                                        Select::make('status')
                                            ->label('📊 Status')
                                            ->helperText('Current progress status')
                                            ->options([
                                                'pending' => '⏳ Pending',
                                                'in_progress' => '🔄 In Progress',
                                                'completed' => '✅ Completed',
                                            ])
                                            ->default('pending')
                                            ->native(false)
                                            ->columnSpan(1),
                                    ]),

                                Grid::make(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        DatePicker::make('due_date')
                                            ->label('📅 Due Date')
                                            ->helperText('When should this be completed?')
                                            ->native(false)
                                            ->displayFormat('M j, Y'),

                                        Select::make('responsible_desk')
                                            ->label('👤 Responsible Desk')
                                            ->helperText('Department handling this event')
                                            ->options(PRFResponsibleDesk::getOptions())
                                            ->native(false)
                                            ->searchable(),
                                    ]),

                                Textarea::make('description')
                                    ->label('📝 Description')
                                    ->helperText('Additional details or notes about this event')
                                    ->maxLength(1000)
                                    ->rows(3)
                                    ->placeholder('Optional description...')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('💵 Financial Summary')
                            ->icon('heroicon-o-banknotes')
                            ->badge(fn ($record) => $record?->balance ? 'KES '.number_format($record->balance) : null)
                            ->schema([
                                Placeholder::make('financial_overview')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(
                                        $record ? static::buildFinancialSummaryHtml($record) : '<div class="text-gray-500">Save the event first to see financial summary.</div>'
                                    ))
                                    ->columnSpanFull(),

                                Section::make('💰 Calculated Totals')
                                    ->description('These values are automatically calculated from allocation entries')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(3)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('balance')
                                                    ->label('💵 Balance')
                                                    ->helperText('Credits minus debits')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('refund_charge')
                                                    ->label('💳 Refund Charges')
                                                    ->helperText('Transaction fees for refunds')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('amount_to_refund')
                                                    ->label('↩️ Amount to Refund')
                                                    ->helperText('Available for refund')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ]),
                                    ])
                                    // ->collapsible()
                                    ->collapsed(),
                            ]),

                        Tab::make('📊 Entries')
                            ->icon('heroicon-o-list-bullet')
                            ->badge(fn ($record) => $record?->allocationEntries?->count() ?: null)
                            ->schema([
                                Placeholder::make('entries_help')
                                    ->label('')
                                    ->content(new HtmlString(
                                        '<div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                            <p>💡 <strong>Credits</strong> = Money received (e.g., budget allocation)</p>
                                            <p>💡 <strong>Debits</strong> = Money spent (e.g., purchases, transport)</p>
                                        </div>'
                                    ))
                                    ->columnSpanFull(),

                                Repeater::make('allocationEntries')
                                    ->relationship('allocationEntries')
                                    ->label('')
                                    ->schema([
                                        Grid::make(4)
                                            ->columnSpanFull()
                                            ->schema([
                                                Select::make('entry_type')
                                                    ->label('📈 Type')
                                                    ->options(PRFEntryType::getOptions())
                                                    ->required()
                                                    ->native(false)
                                                    ->live(),

                                                TextInput::make('amount')
                                                    ->label('💰 Amount')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->placeholder('0.00'),

                                                Select::make('expense_category_id')
                                                    ->label('🏷️ Category')
                                                    ->relationship('expenseCategory', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Select category'),

                                                Select::make('member_id')
                                                    ->label('👤 Added By')
                                                    ->relationship('member', 'full_name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Select member'),
                                            ]),

                                        Textarea::make('narration')
                                            ->label('📝 Description')
                                            ->required()
                                            ->rows(2)
                                            ->placeholder('What is this entry for?')
                                            ->columnSpanFull(),

                                        Fieldset::make('📦 Item Details')
                                            ->schema([
                                                TextInput::make('unit_cost')
                                                    ->label('Unit Cost')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->placeholder('0.00')
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        if ($state && $quantity) {
                                                            $set('amount', $state * $quantity);
                                                        }
                                                    }),

                                                TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                        $unitCost = $get('unit_cost') ?? 0;
                                                        if ($state && $unitCost) {
                                                            $set('amount', $unitCost * $state);
                                                        }
                                                    }),

                                                TextInput::make('charge')
                                                    ->label('Transaction Fee')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->placeholder('0.00'),

                                                Select::make('charge_type')
                                                    ->label('Fee Type')
                                                    ->options(PRFTransactionType::getOptions())
                                                    ->native(false)
                                                    ->placeholder('Select type'),
                                            ])
                                            ->columns(4)
                                        // ->collapsible()
                                        // ->collapsed()
                                        ,

                                        Fieldset::make('📎 Attachments')
                                            ->schema([
                                                Textarea::make('confirmation_message')
                                                    ->label('Confirmation/Reference')
                                                    ->rows(2)
                                                    ->placeholder('M-Pesa confirmation, receipt number, etc.')
                                                    ->columnSpan(1),

                                                SpatieMediaLibraryFileUpload::make('receipts')
                                                    ->label('Receipt Images')
                                                    ->collection('allocation-entry-receipts')
                                                    ->multiple()
                                                    ->image()
                                                    ->imagePreviewHeight('100')
                                                    ->preserveFilenames()
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(2)
                                        // ->collapsible()
                                        // ->collapsed()
                                        ,
                                    ])
                                    ->columns(1)
                                    ->itemLabel(fn (array $state): ?string => sprintf(
                                        '%s %s - KES %s',
                                        match ($state['entry_type'] ?? null) {
                                            1, '1' => '📥',
                                            2, '2' => '📤',
                                            default => '📋',
                                        },
                                        match ((int) ($state['entry_type'] ?? 0)) {
                                            1 => 'Credit',
                                            2 => 'Debit',
                                            default => 'Entry',
                                        },
                                        number_format((float) ($state['amount'] ?? 0))
                                    ))
                                    ->defaultItems(0)
                                    ->addActionLabel('➕ Add Entry')
                                    ->reorderable()
                                    ->collapsible()
                                    ->cloneable()
                                    ->deleteAction(fn ($action) => $action->requiresConfirmation()),
                            ]),
                    ]),
            ]);
    }

    /**
     * Build financial summary HTML for the placeholder
     */
    protected static function buildFinancialSummaryHtml($record): string
    {
        $credits = $record->allocationEntries?->where('entry_type', 1)->sum('amount') ?? 0;
        $debits = $record->allocationEntries?->where('entry_type', 2)->sum('amount') ?? 0;
        $balance = $record->balance ?? ($credits - $debits);
        $entryCount = $record->allocationEntries?->count() ?? 0;

        $balanceColor = $balance > 0 ? 'text-green-600' : ($balance < 0 ? 'text-red-600' : 'text-gray-600');

        return "
            <div class='grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                <div class='text-center p-3'>
                    <div class='text-2xl font-bold text-green-600'>KES ".number_format($credits)."</div>
                    <div class='text-sm text-gray-500'>📥 Total Credits</div>
                </div>
                <div class='text-center p-3'>
                    <div class='text-2xl font-bold text-red-600'>KES ".number_format($debits)."</div>
                    <div class='text-sm text-gray-500'>📤 Total Debits</div>
                </div>
                <div class='text-center p-3'>
                    <div class='text-2xl font-bold {$balanceColor}'>KES ".number_format($balance)."</div>
                    <div class='text-sm text-gray-500'>💵 Balance</div>
                </div>
                <div class='text-center p-3'>
                    <div class='text-2xl font-bold text-blue-600'>{$entryCount}</div>
                    <div class='text-sm text-gray-500'>📊 Entries</div>
                </div>
            </div>
        ";
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('📋 Event Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->tooltip('Accounting event name'),

                TextColumn::make('due_date')
                    ->label('📅 Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() ? Color::Red : null)
                    ->description(fn ($record) => $record->due_date?->diffForHumans())
                    ->tooltip('Event due date'),

                TextColumn::make('status')
                    ->label('📊 Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => '⏳ Pending',
                        'in_progress' => '🔄 In Progress',
                        'completed' => '✅ Completed',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => Color::Yellow,
                        'in_progress' => Color::Blue,
                        'completed' => Color::Green,
                        default => Color::Gray,
                    })
                    ->sortable()
                    ->tooltip('Current status'),

                TextColumn::make('responsible_desk')
                    ->label('👤 Desk')
                    ->formatStateUsing(fn ($state) => PRFResponsibleDesk::tryFrom((int) $state)?->getLabel() ?? $state)
                    ->badge()
                    ->color(Color::Blue)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Responsible desk'),

                TextColumn::make('balance')
                    ->label('💵 Balance')
                    ->money('KES')
                    ->badge()
                    ->weight('bold')
                    ->color(fn ($state) => match (true) {
                        $state > 0 => Color::Green,
                        $state < 0 => Color::Red,
                        default => Color::Gray,
                    })
                    ->tooltip('Current balance'),

                TextColumn::make('allocationEntries_count')
                    ->label('📝 Entries')
                    ->counts('allocationEntries')
                    ->badge()
                    ->color(Color::Blue)
                    ->tooltip('Number of allocation entries'),

                TextColumn::make('refund_charge')
                    ->label('💳 Charges')
                    ->money('KES')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Transaction charges'),

                TextColumn::make('amount_to_refund')
                    ->label('↩️ To Refund')
                    ->money('KES')
                    ->toggleable()
                    ->color(Color::Blue)
                    ->tooltip('Amount available for refund'),

                TextColumn::make('description')
                    ->label('📄 Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->description),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('status')
                    ->label('📊 Status')
                    ->options([
                        'pending' => '⏳ Pending',
                        'in_progress' => '🔄 In Progress',
                        'completed' => '✅ Completed',
                    ]),

                TernaryFilter::make('has_balance')
                    ->label('💵 Balance Status')
                    ->placeholder('All events')
                    ->trueLabel('Has positive balance')
                    ->falseLabel('Has negative/zero balance')
                    ->queries(
                        true: fn ($query) => $query->where('balance', '>', 0),
                        false: fn ($query) => $query->where('balance', '<=', 0),
                    ),

                TernaryFilter::make('overdue')
                    ->label('📅 Due Date')
                    ->placeholder('All events')
                    ->trueLabel('Overdue')
                    ->falseLabel('Not overdue')
                    ->queries(
                        true: fn ($query) => $query->whereDate('due_date', '<', now())->whereNot('status', 'completed'),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereDate('due_date', '>=', now())->orWhere('status', 'completed')),
                    ),
            ])
            ->headerActions([

            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('mark_completed')
                        ->label('Mark Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($record) {
                            $record->update(['status' => PRFAccountEventStatus::COMPLETED]);
                            Notification::make()
                                ->title('Event marked as completed')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record?->status !== PRFAccountEventStatus::COMPLETED)
                        ->requiresConfirmation(),

                    Action::make('mark_pending')
                        ->label('Mark Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color(Color::Blue)
                        ->action(function ($record) {
                            $record->update(['status' => PRFAccountEventStatus::PENDING]);
                            Notification::make()
                                ->title('Event marked as pending')
                                ->info()
                                ->send();
                        })
                        ->visible(fn ($record) => $record?->status === PRFAccountEventStatus::COMPLETED),

                    ViewAction::make()
                        ->color(Color::Gray),

                    EditAction::make()
                        ->color(Color::Orange),

                    DeleteAction::make()
                        ->color(Color::Red),

                    ForceDeleteAction::make()
                        ->color(Color::Red),

                    RestoreAction::make()
                        ->color(Color::Green),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => 'completed']));
                            Notification::make()
                                ->title(count($records).' events marked as completed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->defaultSort('due_date', 'asc')
            ->striped()
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount('allocationEntries')
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
            );
    }
}
