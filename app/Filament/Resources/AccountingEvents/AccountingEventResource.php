<?php

namespace App\Filament\Resources\AccountingEvents;

use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\PersonalInfoSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\AccountingEvents\Pages\CreateAccountingEvent;
use App\Filament\Resources\AccountingEvents\Pages\EditAccountingEvent;
use App\Filament\Resources\AccountingEvents\Pages\ListAccountingEvents;
use App\Filament\Resources\AccountingEvents\Pages\ViewAccountingEvent;
use App\Filament\Resources\AccountingEvents\RelationManagers\RefundsRelationManager;
use App\Filament\Resources\AccountingEvents\RelationManagers\RequisitionsRelationManager;
use App\Jobs\AllocationEntry\AddTokenJob;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\FinancialAccount;
use App\Models\Mission;
use App\Models\PRFEvent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AccountingEventResource extends Resource
{
    protected static ?string $model = AccountingEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?string $navigationLabel = 'Budget Lines';

    protected static ?string $modelLabel = 'Budget Line';

    protected static ?string $pluralModelLabel = 'Budget Lines';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationTooltip = 'Manage budget allocations for missions and events';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->columnSpanFull()
                ->description('Enter the essential details for this budget line item')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    MorphToSelect::make('accountingEventable')
                        ->preload()
                        ->label('Linked To')
                        ->columnSpanFull()
                        ->helperText('Choose whether this budget is for a mission trip or a fellowship event')
                        ->types([
                            Type::make(Mission::class)->titleAttribute('ulid')->label('Mission Trip'),
                            Type::make(PRFEvent::class)->titleAttribute('name')->label('Fellowship Event'),
                        ]),

                    ContentSchema::nameField(
                        name: 'name',
                        label: 'Budget Line Name',
                        placeholder: 'e.g., Youth Conference Catering Budget',
                        required: true,
                        helperText: 'A clear name to identify this budget allocation',
                    ),

                    ContentSchema::descriptionField(
                        name: 'description',
                        label: 'Description',
                        rows: 3,
                        required: false,
                        placeholder: 'e.g., Budget allocation for catering services during the 3-day youth conference including breakfast, lunch, and dinner for 200 attendees...',
                        helperText: 'Provide details about what this budget covers and any relevant notes',
                    ),
                ])
                ->collapsible()
                ->columns(1),

            Section::make('Budget Details')
                ->columnSpanFull()
                ->description('Configure the responsible department, deadline, and status')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    Grid::make(3)
                        ->columnSpanFull()
                        ->schema([
                            StatusSchema::enumSelect(
                                name: 'responsible_desk',
                                label: 'Responsible Department',
                                enumClass: PRFResponsibleDesk::class,
                                required: true,
                                hiddenOnCreate: false,
                                helperText: 'Which department or desk manages this budget',
                            )->placeholder('e.g., Treasurer Desk'),

                            DatePicker::make('due_date')
                                ->label('Deadline')
                                ->required()
                                ->native(false)
                                ->placeholder('Select completion deadline...')
                                ->helperText('When should expenses under this budget be finalized'),

                            StatusSchema::enumSelect(
                                name: 'status',
                                label: 'Status',
                                enumClass: PRFAccountEventStatus::class,
                                default: PRFAccountEventStatus::PENDING->value,
                                required: true,
                                hiddenOnCreate: false,
                                helperText: 'Current state of this budget line',
                            ),
                        ]),
                ])
                ->collapsible()
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ulid')
                    ->label('Reference ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Unique identifier for this budget line - click to copy'),

                TextColumn::make('name')
                    ->label('Budget Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap()
                    ->tooltip(fn(AccountingEvent $record): string => $record->name)
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('accountingEventable.name')
                    ->label('Linked Mission/Event')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not linked')
                    ->tooltip('The mission or event this budget supports'),

                TextColumn::make('responsible_desk')
                    ->label('Department')
                    ->formatStateUsing(fn(?PRFResponsibleDesk $state): string => $state?->getLabel() ?? '')
                    ->badge()
                    ->color(fn(?PRFResponsibleDesk $state): string => $state?->getColor() ?? 'gray')
                    ->sortable()
                    ->tooltip('The department managing this budget'),

                TextColumn::make('due_date')
                    ->label('Deadline')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->tooltip('When expenses should be finalized'),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(?PRFAccountEventStatus $state): string => $state?->getLabel() ?? '')
                    ->badge()
                    ->color(fn(?PRFAccountEventStatus $state): string => $state?->getColor() ?? 'gray')
                    ->icon(
                        fn(?PRFAccountEventStatus $state): string => (
                            $state?->getIcon() ?? 'heroicon-m-question-mark-circle'
                        ),
                    )
                    ->sortable()
                    ->tooltip(
                        'Pending = in progress, Completed = all expenses finalized, Cancelled = budget no longer needed',
                    ),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()?->timezone ?? config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this budget line was created'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()?->timezone ?? config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this record was last modified'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()?->timezone ?? config('app.timezone'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Active')
                    ->tooltip('When this budget line was removed'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make()->label('Deleted Records')->placeholder('All Records'),
            ])
            ->recordActions([
                ViewAction::make()->tooltip('View budget details'),
                EditAction::make()->tooltip('Edit budget information'),
                static::addTokenAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Records a token of appreciation given at this event. When it was handed straight to
     * the treasurer, it is also receipted into the picked account via AddTokenJob.
     */
    public static function addTokenAction(): Action
    {
        return Action::make('add_token')
            ->label('Add token of appreciation')
            ->icon('heroicon-o-gift')
            ->color('success')
            ->modalDescription(
                'A gift of money from the school or host. It is counted towards what the mission team must return.',
            )
            ->schema([
                TextInput::make('unit_cost')
                    ->label('Amount')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->prefix('KES'),

                PersonalInfoSchema::memberSearchSelect('member_ulid', 'Collected by')
                    ->helperText('The missioner who received the token from the school or host.')
                    ->required(),

                Textarea::make('narration')->label('Narration')->required()->rows(2)->columnSpanFull(),

                Textarea::make('confirmation_message')
                    ->label('Confirmation message')
                    ->rows(2)
                    ->placeholder('M-Pesa confirmation…')
                    ->columnSpanFull(),

                Toggle::make('handed_to_treasurer')
                    ->label('Handed to the treasurer')
                    ->live()
                    ->helperText(
                        'Turn on if the money is already in one of the fellowship’s accounts. Otherwise it is counted when the missioner’s refund comes in.',
                    ),

                Select::make('financial_account_ulid')
                    ->label('Received in')
                    ->options(
                        fn(): array => FinancialAccount::query()
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'ulid')
                            ->all(),
                    )
                    ->searchable()
                    ->preload()
                    ->visible(fn(Get $get): bool => (bool) $get('handed_to_treasurer'))
                    ->required(fn(Get $get): bool => (bool) $get('handed_to_treasurer')),
            ])
            ->action(function (array $data, AccountingEvent $record): void {
                AddTokenJob::dispatchSync([
                    'accounting_event_ulid' => $record->ulid,
                    'entry_type' => PRFEntryType::CREDIT->value,
                    'unit_cost' => $data['unit_cost'],
                    'member_ulid' => $data['member_ulid'],
                    'narration' => $data['narration'],
                    'confirmation_message' => $data['confirmation_message'] ?? null,
                    'financial_account_ulid' => !empty($data['handed_to_treasurer'])
                        ? $data['financial_account_ulid'] ?? null
                        : null,
                ]);
            })
            ->successNotificationTitle('Token of appreciation recorded')
            ->visible(fn(): bool => userCan(AllocationEntry::permission('create')));
    }

    public static function getRelations(): array
    {
        return [
            RequisitionsRelationManager::class,
            RefundsRelationManager::class,
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
