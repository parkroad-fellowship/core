<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFMorphType;
use App\Enums\PRFPaymentMethod;
use App\Enums\PRFResponsibleDesk;
use App\Jobs\Requisition\RecallJob;
use App\Jobs\Requisition\RequestReviewJob;
use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\Requisition;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class RequisitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'requisitions';

    protected static ?string $title = '📝 Requisitions';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->requisitions()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📋 Requisition Details')
                    ->description('Basic information about this requisition')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(4)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('member_id')
                                    ->label('👤 Requested By')
                                    ->relationship('member', 'full_name')
                                    ->default(Member::current()?->id)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select member')
                                    ->helperText('Choose who is making this requisition'),

                                DatePicker::make('requisition_date')
                                    ->label('📅 Requisition Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('When this requisition was made'),

                                Select::make('responsible_desk')
                                    ->label('🏢 Responsible Desk')
                                    ->options(PRFResponsibleDesk::getOptions())
                                    ->default(PRFResponsibleDesk::MISSIONS_DESK->value)
                                    ->required()
                                    ->placeholder('Select desk')
                                    ->helperText('Department or desk making the request'),

                                Select::make('appointed_approver_id')
                                    ->label('👨‍💼 Appointed Approver')
                                    ->relationship('appointedApprover', 'full_name')
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->placeholder('Select appointed approver')
                                    ->helperText('Choose who should approve this requisition'),
                            ]),

                        Textarea::make('remarks')
                            ->label('📝 Remarks/Notes')
                            ->placeholder('Add any additional notes or remarks...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Optional notes about this requisition'),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->columnSpanFull(),

                Tabs::make('Requisition Details')
                    ->tabs([
                        Tab::make('🛒 Items')
                            ->icon('heroicon-o-shopping-cart')
                            ->badge(fn ($get) => count($get('requisitionItems') ?? []))
                            ->schema([
                                Repeater::make('requisitionItems')
                                    ->label('📦 Requisition Items')
                                    ->relationship('requisitionItems')
                                    ->schema([
                                        Grid::make(4)
                                            ->columnSpanFull()
                                            ->schema([
                                                Select::make('expense_category_id')
                                                    ->label('🏷️ Category')
                                                    ->relationship('expenseCategory', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->placeholder('Select category')
                                                    ->helperText('Choose expense category'),

                                                TextInput::make('item_name')
                                                    ->label('📝 Item Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Enter item name')
                                                    ->helperText('Describe the item clearly'),

                                                TextInput::make('unit_price')
                                                    ->label('💰 Unit Price')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->prefix('KES')
                                                    ->placeholder('0.00')
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $set('total_price', $state * $quantity);
                                                    })
                                                    ->helperText('Price per unit'),

                                                TextInput::make('quantity')
                                                    ->label('📊 Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->placeholder('1')
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(function ($state, $get, $set) {
                                                        $unitPrice = $get('unit_price') ?? 0;
                                                        $set('total_price', $unitPrice * $state);
                                                    })
                                                    ->helperText('Number of items'),
                                            ]),

                                        TextInput::make('total_price')
                                            ->label('💵 Total Price')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->prefix('KES')
                                            ->disabled()
                                            ->dehydrated()
                                            ->placeholder('0.00')
                                            ->helperText('Automatically calculated')
                                            ->extraAttributes(['class' => 'font-bold']),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['item_name'] ?? 'New Item')
                                    ->collapsed()
                                    ->cloneable()
                                    ->reorderable()
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->addActionLabel('➕ Add Item')
                                    ->deleteAction(
                                        fn ($action) => $action->requiresConfirmation()
                                    )
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        $data['total_price'] = ($data['unit_price'] ?? 0) * ($data['quantity'] ?? 1);

                                        return $data;
                                    }),
                            ]),

                        Tab::make('💳 Payment Instructions')
                            ->icon('heroicon-o-credit-card')
                            ->badge(fn ($get) => count($get('paymentInstruction') ?? []))
                            ->schema([
                                Repeater::make('paymentInstruction')
                                    ->label('💰 Payment Instructions')
                                    ->relationship('paymentInstruction')
                                    ->schema([
                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                Select::make('payment_method')
                                                    ->label('💳 Payment Method')
                                                    ->options(PRFPaymentMethod::getOptions())
                                                    ->required()
                                                    ->live()
                                                    ->placeholder('Select payment method')
                                                    ->helperText('Choose how payment should be made'),

                                                TextInput::make('recipient_name')
                                                    ->label('👤 Recipient Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Enter recipient name')
                                                    ->helperText('Full name of the payment recipient'),
                                            ]),

                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('amount')
                                                    ->label('💵 Amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->prefix('KES')
                                                    ->placeholder('0.00')
                                                    ->live()
                                                    ->afterStateHydrated(function ($state, $get, $set) {
                                                        $items = $get('../../requisitionItems') ?? [];
                                                        $totalAmount = collect($items)->sum('total_price');
                                                        if ($totalAmount > 0 && empty($state)) {
                                                            $set('amount', $totalAmount);
                                                        }
                                                    })
                                                    ->live(onBlur: true)
                                                    ->hint(function (Get $get) {
                                                        $items = $get('../../requisitionItems') ?? [];
                                                        $totalAmount = collect($items)->sum('total_price');

                                                        return $totalAmount > 0 ? '💡 Total items: KES '.number_format($totalAmount) : '';
                                                    })
                                                    ->helperText('Amount to be paid'),

                                                TextInput::make('reference')
                                                    ->label('📝 Reference/Description')
                                                    ->maxLength(255)
                                                    ->placeholder('Payment reference or description')
                                                    ->helperText('Optional payment reference'),
                                            ]),

                                        // MPESA Payment Fields
                                        Grid::make(1)
                                            ->columnSpanFull()
                                            ->schema([
                                                PhoneInput::make('mpesa_phone_number')
                                                    ->label('📱 MPESA Phone Number')
                                                    ->placeholder('+254 7XX XXX XXX')
                                                    ->helperText('Enter the MPESA phone number')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::MPESA->value),
                                            ])
                                            ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::MPESA->value),

                                        // Paybill Payment Fields
                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('paybill_number')
                                                    ->label('🏪 Paybill Number')
                                                    ->numeric()
                                                    ->placeholder('Enter paybill number')
                                                    ->helperText('Business paybill number')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::PAYBILL->value),

                                                TextInput::make('paybill_account_number')
                                                    ->label('🔢 Account Number')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter account number')
                                                    ->helperText('Paybill account number')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::PAYBILL->value),
                                            ])
                                            ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::PAYBILL->value),

                                        // Till Number Payment Fields
                                        Grid::make(1)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('till_number')
                                                    ->label('🏪 Till Number')
                                                    ->numeric()
                                                    ->placeholder('Enter till number')
                                                    ->helperText('Business till number')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::TILL_NUMBER->value),
                                            ])
                                            ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::TILL_NUMBER->value),

                                        // Bank Transfer Payment Fields
                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('bank_name')
                                                    ->label('🏦 Bank Name')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter bank name')
                                                    ->helperText('Name of the bank')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),

                                                TextInput::make('bank_account_number')
                                                    ->label('🔢 Account Number')
                                                    ->numeric()
                                                    ->placeholder('Enter account number')
                                                    ->helperText('Bank account number')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),

                                                TextInput::make('bank_account_name')
                                                    ->label('👤 Account Holder Name')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter account holder name')
                                                    ->helperText('Name on the bank account')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),

                                                TextInput::make('bank_branch')
                                                    ->label('🏢 Branch')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter branch name')
                                                    ->helperText('Bank branch name')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),

                                                TextInput::make('bank_swift_code')
                                                    ->label('🌐 SWIFT Code')
                                                    ->maxLength(255)
                                                    ->placeholder('Enter SWIFT code')
                                                    ->helperText('International bank code (if applicable)')
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),
                                            ])
                                            ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),
                                    ])
                                    ->itemLabel(
                                        fn (array $state): ?string => ($state['recipient_name'] ?? 'New Payment').
                                            (isset($state['amount']) ? ' - KES '.number_format($state['amount']) : '')
                                    )
                                    ->collapsed()
                                    ->cloneable()
                                    ->reorderable()
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->addActionLabel('➕ Add Payment Instruction')
                                    ->deleteAction(
                                        fn ($action) => $action->requiresConfirmation()
                                    ),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('responsible_desk')
                    ->label('🏢 Desk')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFResponsibleDesk::fromValue((int) $record->responsible_desk)->getLabel())
                    ->color(fn ($record) => PRFResponsibleDesk::fromValue((int) $record->responsible_desk)->getColor())
                    ->sortable(),

                TextColumn::make('member.full_name')
                    ->label('👤 Requested By')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),

                TextColumn::make('requisition_date')
                    ->label('📅 Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),

                TextColumn::make('requisition_items_count')
                    ->label('📦 Items')
                    ->counts('requisitionItems')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-shopping-cart'),

                TextColumn::make('total_amount')
                    ->label('💰 Total Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()->money('KES')->label('Total'))
                    ->icon('heroicon-o-banknotes')
                    ->weight('bold'),

                TextColumn::make('approval_status')
                    ->label('📊 Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state !== null ? PRFApprovalStatus::fromValue((int) $state)->getLabel() : 'Pending')
                    ->color(fn ($state) => $state !== null ? PRFApprovalStatus::fromValue((int) $state)->getColor() : 'warning')
                    ->icon(fn ($state) => $state !== null ? PRFApprovalStatus::fromValue((int) $state)->getIcon() : 'heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('remarks')
                    ->label('📝 Remarks')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('🕒 Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-clock'),

                TextColumn::make('updated_at')
                    ->label('🔄 Last Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('responsible_desk')
                    ->label('🏢 Desk')
                    ->options(PRFResponsibleDesk::getFilterOptions())
                    ->multiple()
                    ->placeholder('All Desks'),

                SelectFilter::make('member')
                    ->label('👤 Requested By')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Members'),

                Filter::make('requisition_date')
                    ->label('📅 Date Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->placeholder('Select start date')
                                    ->native(false),
                                DatePicker::make('until_date')
                                    ->label('Until Date')
                                    ->placeholder('Select end date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'From: '.Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['until_date'] ?? null) {
                            $indicators['until_date'] = 'Until: '.Carbon::parse($data['until_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TrashedFilter::make()
                    ->label('🗑️ Deleted Records'),
            ])
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    ->label('New Requisition')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->size('md')
                    ->mutateDataUsing(fn (array $data) => $this->appendExtraData($data)),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn (Requisition $record) => $record->approval_status === null || $record->approval_status === PRFApprovalStatus::PENDING->value),
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Requisition')
                        ->modalDescription('Are you sure you want to delete this requisition? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete it')
                        ->visible(fn (Requisition $record) => $record->approval_status === null || $record->approval_status === PRFApprovalStatus::PENDING->value),
                    ForceDeleteAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Requisition')
                        ->modalDescription('Are you sure you want to permanently delete this requisition? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently'),
                    RestoreAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->color('success'),
                    Action::make('requestReview')
                        ->label('Request Review')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->visible(fn (Requisition $record) => $record->approval_status === PRFApprovalStatus::PENDING->value &&
                            $record->appointed_approver_id
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Request Review')
                        ->modalDescription(fn (Requisition $record) => "This will send a review request to {$record->appointedApprover?->full_name} and notify them to review this requisition.")
                        ->action(function (Requisition $record): void {
                            if ($record->requisitionItems()->doesntExist()) {
                                Notification::make()
                                    ->title('Cannot request review')
                                    ->body('A requisition must have at least one line item.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($record->paymentInstruction()->doesntExist()) {
                                Notification::make()
                                    ->title('Cannot request review')
                                    ->body('You must provide a payment instruction for this requisition.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            RequestReviewJob::dispatchSync(
                                $record->ulid,
                                [
                                    'appointed_approver_ulid' => $record->appointedApprover->ulid,
                                ],
                            );
                        })
                        ->successNotificationTitle('Review requested successfully'),
                    Action::make('recall')
                        ->label('Recall')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (Requisition $record) => userCan('recall requisition') && $record->canBeRecalled())
                        ->requiresConfirmation()
                        ->modalHeading('Recall Requisition')
                        ->modalDescription(fn (Requisition $record) => "Are you sure you want to recall requisition {$record->ulid}? All approvers and desk members will be notified not to take any action on this requisition."
                        )
                        ->action(function (Requisition $record): void {
                            RecallJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => 'Requisition recalled by requester',
                                ],
                                Auth::id());
                        })
                        ->successNotificationTitle('Requisition recalled successfully'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Requisitions')
                        ->modalDescription('Are you sure you want to delete the selected requisitions? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    ForceDeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Selected Requisitions')
                        ->modalDescription('Are you sure you want to permanently delete the selected requisitions? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently'),
                    RestoreBulkAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->color('success'),
                ])
                    ->label('Bulk Actions')
                    ->color('gray'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create your first requisition')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->mutateDataUsing(fn (array $data) => $this->appendExtraData($data)),
            ])
            ->emptyStateHeading('No requisitions yet')
            ->emptyStateDescription('Get started by creating your first requisition for this mission.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    private function appendExtraData(array $data): array
    {
        $mission = $this->ownerRecord;

        $accountingEvent = AccountingEvent::query()
            ->where([
                'accounting_eventable_id' => $mission->id,
                'accounting_eventable_type' => PRFMorphType::MISSION,
            ])
            ->first();

        if ($accountingEvent) {
            $data['accounting_event_id'] = $accountingEvent->id;
        } else {
            $accountingEvent = AccountingEvent::create([
                'accounting_eventable_id' => $mission->id,
                'accounting_eventable_type' => PRFMorphType::MISSION,
                'name' => sprintf('%s: %s - %s', $mission->start_date->format('d-m-Y'), $mission->school->name, $mission->missionType->name),
                'due_date' => $mission->start_date->subDays(1),
                'responsible_desk' => PRFResponsibleDesk::MISSIONS_DESK,
            ]);
            $data['accounting_event_id'] = $accountingEvent->id;
        }

        return $data;
    }
}
