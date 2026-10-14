<?php

namespace App\Filament\Resources\Requisitions;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Requisitions\Pages\CreateRequisition;
use App\Filament\Resources\Requisitions\Pages\EditRequisition;
use App\Filament\Resources\Requisitions\Pages\ListRequisitions;
use App\Filament\Resources\Requisitions\Pages\ViewRequisition;
use App\Filament\Resources\Requisitions\RelationManagers\RequisitionItemsRelationManager;
use App\Helpers\Utils;
use App\Jobs\Requisition\ApproveJob;
use App\Jobs\Requisition\RecallJob;
use App\Jobs\Requisition\RecordDisbursementJob;
use App\Jobs\Requisition\RejectJob;
use App\Jobs\Requisition\RequestReviewJob;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use App\Models\Requisition;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RequisitionResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Requisitions';

    protected static ?string $modelLabel = 'Requisition';

    protected static ?string $pluralModelLabel = 'Requisitions';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationTooltip = 'Manage expense requisitions and approvals';

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->ulid;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Member' => $record->member?->full_name ?? 'Unknown Member',
            'Amount' => 'KES ' . number_format($record->total_amount, 2),
            'Status' => $record->approval_status?->getLabel(),
            'Date' => $record->requisition_date->format('M j, Y'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ulid', 'member.full_name', 'remarks', 'approval_notes'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', PRFApprovalStatus::PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getNavigationBadge();

        return $count > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count . ' pending requisition' . ($count !== 1 ? 's' : '');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->columnSpanFull()
                ->description('Enter the essential details for this expense request')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('ulid')
                                ->label('Requisition ID')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Will be generated automatically')
                                ->helperText('A unique reference number assigned when you save'),

                            StatusSchema::relationshipSelect(
                                name: 'member_id',
                                label: 'Requesting Member',
                                relationship: 'member',
                                titleAttribute: 'full_name',
                                required: true,
                                helperText: 'The person submitting this expense request',
                            )
                                ->placeholder('Search by name...')
                                ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name ?? 'Unknown Member'),

                            StatusSchema::relationshipSelect(
                                name: 'accounting_event_id',
                                label: 'Budget Line',
                                relationship: 'accountingEvent',
                                titleAttribute: 'name',
                                required: true,
                                helperText: 'Which budget or event will cover this expense',
                            )
                                ->placeholder('e.g., Youth Conference 2024')
                                ->getOptionLabelFromRecordUsing(fn($record) => $record->name ?? 'Unknown Event'),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            DatePicker::make('requisition_date')
                                ->label('Request Date')
                                ->required()
                                ->default(today())
                                ->native(false)
                                ->placeholder('Select date...')
                                ->helperText('When this expense request is being made'),

                            StatusSchema::enumSelect(
                                name: 'responsible_desk',
                                label: 'Responsible Department',
                                enumClass: PRFResponsibleDesk::class,
                                required: true,
                                hiddenOnCreate: false,
                                helperText: 'The department or desk responsible for this expense',
                            )->placeholder('e.g., Treasurer Desk'),
                        ]),
                ])
                ->collapsible()
                ->columns(1),

            Section::make('Approval Workflow')
                ->columnSpanFull()
                ->description('Track the approval process for this expense request')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            StatusSchema::enumSelect(
                                name: 'approval_status',
                                label: 'Approval Status',
                                enumClass: PRFApprovalStatus::class,
                                default: PRFApprovalStatus::PENDING->value,
                                required: true,
                                hiddenOnCreate: false,
                                helperText: 'Current stage in the approval process',
                            )->live(),

                            StatusSchema::relationshipSelect(
                                name: 'appointed_approver_id',
                                label: 'Assigned Approver',
                                relationship: 'appointedApprover',
                                titleAttribute: 'full_name',
                                required: false,
                                helperText: 'The person designated to review and approve this request',
                            )
                                ->placeholder('Select an approver...')
                                ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name ?? 'Unknown Member'),

                            StatusSchema::relationshipSelect(
                                name: 'approved_by',
                                label: 'Approved/Rejected By',
                                relationship: 'approvedBy',
                                titleAttribute: 'full_name',
                                required: false,
                                helperText: 'Who made the final approval or rejection decision',
                            )
                                ->placeholder('Auto-filled when approved/rejected')
                                ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name ?? 'Unknown Member')
                                ->visible(fn(callable $get) => in_array($get('approval_status'), [
                                    PRFApprovalStatus::APPROVED->value,
                                    PRFApprovalStatus::REJECTED->value,
                                ])),
                        ]),

                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            DateTimePicker::make('review_requested_at')
                                ->label('Review Requested On')
                                ->native(false)
                                ->placeholder('Not yet requested')
                                ->helperText('When the request was sent for review'),

                            DateTimePicker::make('approved_at')
                                ->label('Approved On')
                                ->native(false)
                                ->placeholder('Not yet approved')
                                ->helperText('Date and time of approval')
                                ->visible(
                                    fn(callable $get) => $get('approval_status') == PRFApprovalStatus::APPROVED->value,
                                ),

                            DateTimePicker::make('rejected_at')
                                ->label('Rejected On')
                                ->native(false)
                                ->placeholder('Not applicable')
                                ->helperText('Date and time of rejection')
                                ->visible(
                                    fn(callable $get) => $get('approval_status') == PRFApprovalStatus::REJECTED->value,
                                ),
                        ]),

                    ContentSchema::descriptionField(
                        name: 'approval_notes',
                        label: 'Approval Notes',
                        rows: 3,
                        required: false,
                        placeholder: 'e.g., Approved for urgent ministry needs...',
                        helperText: 'Comments from the approver explaining their decision',
                    ),
                ])
                ->collapsible()
                ->columns(1),

            Section::make('Financial Details')
                ->columnSpanFull()
                ->description('Enter the amount being requested')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    TextInput::make('total_amount')
                        ->label('Total Amount Requested')
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->prefix('KES')
                        ->step(0.01)
                        ->placeholder('e.g., 25,000')
                        ->helperText('The total amount in Kenyan Shillings needed for this expense')
                        ->formatStateUsing(fn(?int $state) => $state ? $state : 0)
                        ->dehydrateStateUsing(fn(?string $state) => $state ? (int) $state : 0),
                ])
                ->collapsible()
                ->columns(1),

            Section::make('Additional Information')
                ->columnSpanFull()
                ->description('Provide any extra details or justification for this request')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->schema([
                    ContentSchema::descriptionField(
                        name: 'remarks',
                        label: 'Remarks and Justification',
                        rows: 4,
                        required: false,
                        placeholder: 'e.g., This expense is needed for purchasing refreshments for the upcoming youth conference with 200 expected attendees...',
                        helperText: 'Explain why this expense is needed and any relevant details',
                    ),
                ])
                ->collapsible()
                ->columns(1),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('accountingEvent.name')
                    ->label('Budget Line / Event')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap()
                    ->tooltip(fn(Requisition $record): ?string => $record->accountingEvent?->name)
                    ->icon('heroicon-m-chart-bar')
                    ->placeholder('No budget assigned'),

                TextColumn::make('responsible_desk')
                    ->label('Department')
                    ->formatStateUsing(fn(?PRFResponsibleDesk $state): string => $state?->getLabel() ?? '')
                    ->badge()
                    ->color(fn(?PRFResponsibleDesk $state): string => $state?->getColor() ?? 'gray')
                    ->icon(
                        fn(?PRFResponsibleDesk $state): string => (
                            $state?->getIcon() ?? 'heroicon-m-question-mark-circle'
                        ),
                    )
                    ->sortable()
                    ->tooltip('The department or desk responsible for this expense'),

                TextColumn::make('member.full_name')
                    ->label('Requested By')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn(Requisition $record): ?string => $record->member?->email ?? null)
                    ->placeholder('Unknown Member')
                    ->tooltip('The person who submitted this expense request'),

                TextColumn::make('requisition_date')
                    ->label('Request Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->description(fn(Requisition $record): ?string => $record->requisition_date?->diffForHumans())
                    ->tooltip('When this expense request was submitted'),

                TextColumn::make('total_amount')
                    ->label('Amount (KES)')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->weight('medium')
                    ->tooltip('Total amount requested in Kenyan Shillings'),

                TextColumn::make('approval_status')
                    ->label('Status')
                    ->formatStateUsing(fn(?PRFApprovalStatus $state): string => $state?->getLabel() ?? '')
                    ->badge()
                    ->color(fn(?PRFApprovalStatus $state): string => $state?->getColor() ?? 'gray')
                    ->icon(
                        fn(?PRFApprovalStatus $state): string => (
                            $state?->getIcon() ?? 'heroicon-m-question-mark-circle'
                        ),
                    )
                    ->sortable()
                    ->tooltip('Pending = awaiting review, Approved = funds released, Rejected = request denied'),

                TextColumn::make('approved_at')
                    ->label('Approved Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not yet')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this request was approved'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(
                        fn(Requisition $record): string => 'Created: '
                        . $record->created_at->format('F j, Y \a\t g:i A'),
                    ),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(
                        fn(Requisition $record): string => 'Updated: '
                        . $record->updated_at->format('F j, Y \a\t g:i A'),
                    ),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Active')
                    ->tooltip('When this record was removed'),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->filters([
                TrashedFilter::make()->label('Deleted Records')->placeholder('All Records'),

                PRFApprovalStatus::getTableFilter(),

                PRFResponsibleDesk::getTableFilter(),

                SelectFilter::make('member')
                    ->label('Requesting Member')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Members'),

                SelectFilter::make('accountingEvent')
                    ->label('Budget Line')
                    ->relationship('accountingEvent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Budget Lines'),

                Filter::make('amount_range')
                    ->label('Amount Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('amount_from')
                                    ->label('Minimum (KES)')
                                    ->numeric()
                                    ->placeholder('e.g., 1,000'),
                                TextInput::make('amount_to')
                                    ->label('Maximum (KES)')
                                    ->numeric()
                                    ->placeholder('e.g., 100,000'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['amount_from'], fn(Builder $query, $amount): Builder => $query->where(
                            'total_amount',
                            '>=',
                            $amount,
                        ))->when($data['amount_to'], fn(Builder $query, $amount): Builder => $query->where(
                            'total_amount',
                            '<=',
                            $amount,
                        ));
                    }),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('date_from')
                                    ->label('From Date')
                                    ->native(false)
                                    ->placeholder('Start date...'),
                                DatePicker::make('date_to')
                                    ->label('To Date')
                                    ->native(false)
                                    ->placeholder('End date...'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['date_from'], fn(Builder $query, $date): Builder => $query->whereDate(
                            'requisition_date',
                            '>=',
                            $date,
                        ))->when($data['date_to'], fn(Builder $query, $date): Builder => $query->whereDate(
                            'requisition_date',
                            '<=',
                            $date,
                        ));
                    }),

                Filter::make('pending_approval')
                    ->label('Pending Approval')
                    ->query(fn(Builder $query): Builder => $query->where('approval_status', PRFApprovalStatus::PENDING))
                    ->default()
                    ->toggle(),

                Filter::make('has_payment_instruction')
                    ->label('Has Payment Instruction')
                    ->query(fn(Builder $query): Builder => $query->whereHas('paymentInstruction'))
                    ->toggle(),

                Filter::make('my_requisitions')
                    ->label('My Requisitions')
                    ->query(fn(Builder $query): Builder => $query->where('member_id', Auth::user()->member?->id))
                    ->toggle()
                    ->visible(fn() => Auth::user()->member),

                Filter::make('assigned_to_me')
                    ->label('Assigned to Me')
                    ->query(fn(Builder $query): Builder => $query->where(
                        'appointed_approver_id',
                        Auth::user()->member?->id,
                    ))
                    ->toggle()
                    ->visible(fn() => Auth::user()->member),

                QueryBuilder::make()->constraints([
                    TextConstraint::make('ulid')->label('Requisition ID'),
                    TextConstraint::make('remarks')->label('Remarks'),
                    TextConstraint::make('approval_notes')->label('Approval Notes'),
                    NumberConstraint::make('total_amount')->label('Total Amount'),
                    DateConstraint::make('requisition_date')->label('Requisition Date'),
                    DateConstraint::make('approved_at')->label('Approved Date'),
                    RelationshipConstraint::make('member')->label('Member')->multiple(),
                    RelationshipConstraint::make('accountingEvent')->label('Budget Line')->multiple(),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->modalHeading(fn(Requisition $record) => "Requisition: {$record->ulid}")
                        ->modalDescription(
                            fn(Requisition $record) => 'Amount: KES ' . number_format($record->total_amount, 2),
                        )
                        ->visible(fn() => userCan(Requisition::permission('view'))),

                    EditAction::make()
                        ->color('warning')
                        ->successNotificationTitle('Requisition updated successfully')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(Requisition::permission('edit'))
                                && $record->approval_status === PRFApprovalStatus::PENDING
                            ),
                        ),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Requisition')
                        ->modalDescription(
                            fn(Requisition $record) => (
                                "Are you sure you want to approve requisition {$record->ulid} for KES "
                                . number_format($record->total_amount, 2)
                                . '?'
                            ),
                        )
                        ->schema([
                            Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->placeholder('e.g., Approved for ministry event expenses...')
                                ->helperText('Add any notes explaining your approval decision')
                                ->rows(3),

                            ...static::disbursementFields(),
                        ])
                        ->action(function (array $data, Requisition $record): void {
                            ApproveJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => $data['approval_notes'] ?? null,
                                    'financial_account_ulid' => $data['financial_account_ulid'] ?? null,
                                    'charge' => (int) ($data['charge'] ?? 0),
                                    'reference' => $data['reference'] ?? null,
                                    'paid_on' => $data['paid_on'] ?? null,
                                ],
                                Auth::id(),
                            );
                        })
                        ->successNotificationTitle('Requisition approved successfully')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(Requisition::permission('approve'))
                                && $record->approval_status === PRFApprovalStatus::PENDING
                                && (
                                    $record->appointed_approver_id === Auth::user()->member?->id
                                    || userCan(Requisition::permission('approve any'))
                                )
                            ),
                        ),

                    Action::make('record_disbursement')
                        ->label('Record disbursement')
                        ->icon('heroicon-m-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Record disbursement')
                        ->modalDescription(
                            fn(Requisition $record) => (
                                'Post KES '
                                . number_format($record->total_amount)
                                . ' for requisition '
                                . $record->ulid
                                . ' to the cashbook?'
                            ),
                        )
                        ->schema(static::disbursementFields(accountRequired: true))
                        ->action(function (array $data, Requisition $record): void {
                            RecordDisbursementJob::dispatchSync($record->ulid, $data, Auth::id());
                        })
                        ->successNotificationTitle('Disbursement recorded')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(LedgerEntry::permission('create'))
                                && $record->approval_status === PRFApprovalStatus::APPROVED
                                && !static::isDisbursed($record)
                            ),
                        ),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Requisition')
                        ->modalDescription(
                            fn(Requisition $record) => "Are you sure you want to reject requisition {$record->ulid}?",
                        )
                        ->schema([
                            Textarea::make('approval_notes')
                                ->label('Reason for Rejection')
                                ->placeholder('e.g., Insufficient budget remaining for this quarter...')
                                ->helperText('Please explain why this request is being rejected')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (array $data, Requisition $record): void {
                            RejectJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => $data['approval_notes'],
                                ],
                                Auth::id(),
                            );
                        })
                        ->successNotificationTitle('Requisition rejected')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(Requisition::permission('approve'))
                                && $record->approval_status === PRFApprovalStatus::PENDING
                                && (
                                    $record->appointed_approver_id === Auth::user()->member?->id
                                    || userCan(Requisition::permission('approve any'))
                                )
                            ),
                        ),

                    Action::make('request_review')
                        ->label('Request Review')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Request Review')
                        ->modalDescription('This will notify the assigned approver to review this requisition.')
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

                            RequestReviewJob::dispatchSync($record->ulid, [
                                'appointed_approver_ulid' => $record->appointedApprover->ulid,
                            ]);
                        })
                        ->successNotificationTitle('Review requested')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(Requisition::permission('request review'))
                                && $record->approval_status === PRFApprovalStatus::PENDING
                                && $record->appointed_approver_id
                            ),
                        ),

                    Action::make('recall')
                        ->label('Recall')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Recall Requisition')
                        ->modalDescription(
                            fn(Requisition $record) => "Are you sure you want to recall requisition {$record->ulid}? All approvers and desk members will be notified not to take any action on this requisition.",
                        )
                        ->action(function (Requisition $record): void {
                            RecallJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => 'Requisition recalled by requester',
                                ],
                                Auth::id(),
                            );
                        })
                        ->successNotificationTitle('Requisition recalled successfully'),
                    // ->visible(fn (Requisition $record) => userCan(Requisition::permission('recall')) &&
                    //     in_array($record->approval_status, [
                    //         PRFApprovalStatus::PENDING->value,
                    //         PRFApprovalStatus::UNDER_REVIEW->value,
                    //         PRFApprovalStatus::APPROVED->value,
                    //     ])
                    // )
                    DeleteAction::make()
                        ->successNotificationTitle('Requisition deleted successfully')
                        ->visible(
                            fn(Requisition $record) => (
                                userCan(Requisition::permission('delete'))
                                && $record->approval_status === PRFApprovalStatus::PENDING
                            ),
                        ),

                    ForceDeleteAction::make()->visible(fn() => userCan(Requisition::permission('forceDelete'))),

                    RestoreAction::make()
                        ->successNotificationTitle('Requisition restored successfully')
                        ->visible(fn() => userCan(Requisition::permission('restore'))),
                ])
                    ->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->tooltip('Requisition Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Requisitions deleted successfully')
                        ->visible(fn() => userCan(Requisition::permission('delete'))),

                    ForceDeleteBulkAction::make()->visible(fn() => userCan(Requisition::permission('forceDelete'))),

                    RestoreBulkAction::make()
                        ->successNotificationTitle('Requisitions restored successfully')
                        ->visible(fn() => userCan(Requisition::permission('restore'))),

                    BulkAction::make('bulkApprove')
                        ->label('Bulk Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Approve Requisitions')
                        ->modalDescription('Are you sure you want to approve all selected requisitions?')
                        ->schema([
                            Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->placeholder('e.g., Batch approved for quarterly budget allocation...')
                                ->helperText('These notes will be applied to all selected requisitions')
                                ->rows(3),

                            ...static::disbursementFields(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->approval_status === PRFApprovalStatus::PENDING) {
                                    // Same path as the API. Charges and references differ per payout, so a
                                    // bulk approval only shares the account and date.
                                    ApproveJob::dispatchSync(
                                        $record->ulid,
                                        [
                                            'approval_notes' => $data['approval_notes'] ?? null,
                                            'financial_account_ulid' => $data['financial_account_ulid'] ?? null,
                                            'paid_on' => $data['paid_on'] ?? null,
                                        ],
                                        (int) Auth::id(),
                                    );
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk approval completed')
                                ->body("Approved {$count} requisitions successfully")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn() => userCan(Requisition::permission('approve'))),

                    BulkAction::make('assignApprover')
                        ->label('Assign Approver')
                        ->icon('heroicon-m-user-plus')
                        ->color('info')
                        ->schema([
                            Select::make('appointed_approver_id')
                                ->label('Select Approver')
                                ->relationship('appointedApprover', 'full_name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Search by name...')
                                ->helperText('Choose who should review and approve these requisitions'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update([
                                    'appointed_approver_id' => $data['appointed_approver_id'],
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Approver assigned')
                                ->body("Assigned approver to {$count} requisitions")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn() => userCan(Requisition::permission('assign approver'))),

                    BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            // This would typically generate a file download
                            Notification::make()
                                ->success()
                                ->title('Export prepared')
                                ->body('Export for ' . $records->count() . ' requisitions is ready')
                                ->send();
                        })
                        ->visible(fn() => userCan(Requisition::permission('export'))),
                ])->visible(
                    fn() => userCan(Requisition::permission('delete')) || userCan(Requisition::permission('approve')),
                ),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Optional cashbook posting for an approval: which account paid, the M-Pesa charge,
     * a reference and the date. A blank account means "don't touch the cashbook".
     *
     * @return array<int, mixed>
     */
    /**
     * Whether the payout is already in the cashbook (uses the preloaded `ledger_entries_exists`).
     */
    public static function isDisbursed(Requisition $record): bool
    {
        return (bool) ($record->ledger_entries_exists ?? $record->ledgerEntries()->exists());
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function disbursementFields(bool $accountRequired = false): array
    {
        return [
            Section::make('Paid out from')
                ->description(
                    $accountRequired
                        ? 'Book the payout in the cashbook'
                        : 'Optional: book the payout in the cashbook now',
                )
                ->icon('heroicon-o-banknotes')
                // Only people who keep the cashbook book payouts; approvers like the chair don't see this.
                ->visible(fn(): bool => userCan(LedgerEntry::permission('create')))
                ->schema([
                    Select::make('financial_account_ulid')
                        ->label('Paid from account')
                        ->options(
                            fn(): array => FinancialAccount::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'ulid')
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->required($accountRequired)
                        ->placeholder($accountRequired ? 'Select account…' : 'Leave blank to skip the cashbook')
                        ->helperText(
                            $accountRequired
                                ? 'Which account the money left'
                                : 'Leave blank to approve without posting to the cashbook',
                        ),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('charge')
                                ->label('Transaction charge')
                                ->prefix('KES')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->hint(fn(?Requisition $record): ?string => $record instanceof Requisition
                                    ? 'Estimated charge: KES '
                                        . number_format(Utils::getCharge(
                                            PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
                                            (int) $record->total_amount,
                                        ))
                                    : null)
                                ->helperText('Posted as a second cashbook line'),

                            TextInput::make('reference')
                                ->label('Reference')
                                ->maxLength(255)
                                ->placeholder('M-Pesa code or bank slip'),
                        ]),

                    DatePicker::make('paid_on')
                        ->label('Paid on')
                        ->native(false)
                        ->default(today())
                        ->maxDate(today())
                        ->helperText('Date the money left the account'),
                ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RequisitionItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequisitions::route('/'),
            'create' => CreateRequisition::route('/create'),
            'view' => ViewRequisition::route('/{record}'),
            'edit' => EditRequisition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['member', 'accountingEvent', 'appointedApprover', 'approvedBy', 'paymentInstruction'])
            ->withCount(['requisitionItems'])
            ->withExists('ledgerEntries')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getDefaultEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['member', 'accountingEvent', 'appointedApprover'])
            ->withCount(['requisitionItems']);
    }

    public static function canAccess(): bool
    {
        return false;
        return userCan(Requisition::permission('viewAny'));
    }
}
