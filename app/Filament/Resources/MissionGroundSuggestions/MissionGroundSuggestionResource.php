<?php

namespace App\Filament\Resources\MissionGroundSuggestions;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\MissionGroundSuggestions\Pages\CreateMissionGroundSuggestion;
use App\Filament\Resources\MissionGroundSuggestions\Pages\EditMissionGroundSuggestion;
use App\Filament\Resources\MissionGroundSuggestions\Pages\ListMissionGroundSuggestions;
use App\Filament\Resources\MissionGroundSuggestions\Pages\ViewMissionGroundSuggestion;
use App\Jobs\MissionGroundSuggestion\UpdateJob;
use App\Jobs\School\UpdateJob as UpdateSchoolJob;
use App\Models\Mission;
use App\Models\MissionGroundSuggestion;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MissionGroundSuggestionResource extends Resource
{
    protected static ?string $model = MissionGroundSuggestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Suggested schools';

    protected static ?string $modelLabel = 'suggested school';

    protected static ?string $pluralModelLabel = 'suggested schools';

    protected static ?string $navigationTooltip = 'Schools members have suggested for a mission';

    /** Once a suggestion reaches one of these, there is nothing left to follow up. */
    private const CLOSED = [
        PRFMissionGroundSuggestionStatus::MISSION_SECURED,
        PRFMissionGroundSuggestionStatus::COMPLETED,
        PRFMissionGroundSuggestionStatus::IGNORE,
    ];

    public static function getNavigationBadge(): ?string
    {
        $pending = MissionGroundSuggestion::query()
            ->where('status', PRFMissionGroundSuggestionStatus::PENDING)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Waiting for someone to follow up';
    }

    /**
     * Read through getAttribute() so the enum cast is honoured regardless of the column type.
     */
    public static function statusOf(MissionGroundSuggestion $suggestion): ?PRFMissionGroundSuggestionStatus
    {
        $status = $suggestion->getAttribute('status');

        return $status instanceof PRFMissionGroundSuggestionStatus ? $status : null;
    }

    public static function statusLabel(?PRFMissionGroundSuggestionStatus $status): string
    {
        return match ($status) {
            PRFMissionGroundSuggestionStatus::PENDING => 'New',
            PRFMissionGroundSuggestionStatus::INITIATED_CONTACT => 'Contacted',
            PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED => 'Visit planned',
            PRFMissionGroundSuggestionStatus::MISSION_SECURED => 'Mission planned',
            PRFMissionGroundSuggestionStatus::COMPLETED => 'Done',
            PRFMissionGroundSuggestionStatus::IGNORE => 'Not pursuing',
            null => 'Unknown',
        };
    }

    public static function statusColor(?PRFMissionGroundSuggestionStatus $status): string
    {
        return match ($status) {
            PRFMissionGroundSuggestionStatus::PENDING => 'warning',
            PRFMissionGroundSuggestionStatus::INITIATED_CONTACT,
            PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED,
                => 'info',
            PRFMissionGroundSuggestionStatus::MISSION_SECURED, PRFMissionGroundSuggestionStatus::COMPLETED => 'success',
            PRFMissionGroundSuggestionStatus::IGNORE, null => 'gray',
        };
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('The suggestion')
                ->columnSpanFull()
                ->icon('heroicon-o-light-bulb')
                ->columns(2)
                ->schema([
                    StatusSchema::relationshipSelect(
                        name: 'suggestor_id',
                        label: 'Suggested by',
                        relationship: 'suggestor',
                        titleAttribute: 'full_name',
                        helperText: 'The member who told us about this school.',
                    )->columnSpanFull(),

                    ContentSchema::nameField(
                        name: 'name',
                        label: 'School name',
                        placeholder: 'e.g. Moi Girls High School, Eldoret',
                        helperText: 'As the member gave it. You can correct it when you add the school.',
                    )->columnSpanFull(),

                    TextInput::make('contact_person')
                        ->label('Person to talk to')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Mrs Jane Wanjiku')
                        ->helperText('A teacher, patron or principal at the school.'),

                    PhoneInput::make('contact_number')->label('Their phone number')->defaultCountry('KE')->required(),
                ]),

            Section::make('Follow-up')
                ->columnSpanFull()
                ->icon('heroicon-o-clipboard-document-list')
                ->hiddenOn('create')
                ->schema([
                    StatusSchema::enumSelect(
                        name: 'status',
                        label: 'Where are we with it?',
                        enumClass: PRFMissionGroundSuggestionStatus::class,
                        default: PRFMissionGroundSuggestionStatus::PENDING->value,
                        helperText: 'Update this as you follow up, so everyone knows where it stands.',
                    )->options(
                        collect(PRFMissionGroundSuggestionStatus::cases())
                            ->mapWithKeys(fn(PRFMissionGroundSuggestionStatus $status): array => [
                                $status->value => self::statusLabel($status),
                            ])->all(),
                    ),

                    ContentSchema::descriptionField(
                        name: 'notes',
                        label: 'Notes (optional)',
                        rows: 4,
                        placeholder: 'e.g. Called on 15 Jan. The principal is keen; best to call in the morning.',
                    ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('School')
                    ->weight('semibold')
                    ->description(
                        fn(MissionGroundSuggestion $record): string => (
                            'Suggested by ' . ($record->suggestor->full_name ?? 'a member')
                        ),
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')->label('Person to talk to')->searchable(),

                PhoneColumn::make('contact_number')->label('Phone')->displayFormat(PhoneInputNumberType::INTERNATIONAL),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn(?PRFMissionGroundSuggestionStatus $state): string => self::statusLabel($state),
                    )
                    ->color(fn(?PRFMissionGroundSuggestionStatus $state): string => self::statusColor($state))
                    ->sortable(),

                TextColumn::make('created_at')->label('Suggested on')->date('j M Y')->sortable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted on')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(
                        collect(PRFMissionGroundSuggestionStatus::cases())
                            ->mapWithKeys(fn(PRFMissionGroundSuggestionStatus $status): array => [
                                $status->value => self::statusLabel($status),
                            ])->all(),
                    )
                    ->placeholder('Any status'),

                TrashedFilter::make()->label('Deleted suggestions'),
            ])
            ->recordActions([
                self::addSchoolAction()->button()->size('sm'),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::statusAction(
                        'initiate_contact',
                        'Mark as contacted',
                        'heroicon-m-chat-bubble-left-right',
                        PRFMissionGroundSuggestionStatus::INITIATED_CONTACT,
                    )
                        ->visible(
                            fn(MissionGroundSuggestion $record): bool => (
                                self::statusOf($record) === PRFMissionGroundSuggestionStatus::PENDING
                            ),
                        ),
                    self::statusAction(
                        'schedule_visit',
                        'Mark visit as planned',
                        'heroicon-m-calendar',
                        PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED,
                    )
                        ->visible(
                            fn(MissionGroundSuggestion $record): bool => (
                                self::statusOf($record) === PRFMissionGroundSuggestionStatus::INITIATED_CONTACT
                            ),
                        ),
                    self::statusAction(
                        'ignore',
                        'Not pursuing',
                        'heroicon-m-x-circle',
                        PRFMissionGroundSuggestionStatus::IGNORE,
                    )
                        ->color('danger')
                        ->visible(fn(MissionGroundSuggestion $record): bool => !in_array(
                            self::statusOf($record),
                            self::CLOSED,
                            true,
                        )),
                ])->label('More')->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('initiate_contact')
                        ->label('Mark as contacted')
                        ->icon('heroicon-m-chat-bubble-left-right')
                        ->action(
                            fn(Collection $records) => self::setStatus(
                                $records,
                                PRFMissionGroundSuggestionStatus::INITIATED_CONTACT,
                            ),
                        )
                        ->requiresConfirmation(),
                    BulkAction::make('ignore')
                        ->label('Not pursuing')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(
                            fn(Collection $records) => self::setStatus(
                                $records,
                                PRFMissionGroundSuggestionStatus::IGNORE,
                            ),
                        )
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No suggested schools')
            ->emptyStateDescription('When members suggest a school from the app, it shows up here.');
    }

    private static function statusAction(
        string $name,
        string $label,
        string $icon,
        PRFMissionGroundSuggestionStatus $status,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->requiresConfirmation()
            ->action(function (MissionGroundSuggestion $record) use ($status): void {
                UpdateJob::dispatchSync(['status' => $status], $record->ulid);

                Notification::make()
                    ->success()
                    ->title('Marked as “' . self::statusLabel($status) . '”')
                    ->send();
            });
    }

    /**
     * @param  Collection<int, MissionGroundSuggestion>  $records
     */
    private static function setStatus(Collection $records, PRFMissionGroundSuggestionStatus $status): void
    {
        $records->each(fn(MissionGroundSuggestion $record) => UpdateJob::dispatchSync([
            'status' => $status,
        ], $record->ulid));

        Notification::make()
            ->success()
            ->title("{$records->count()} marked as “" . self::statusLabel($status) . '”')
            ->send();
    }

    /**
     * Turns a suggestion into a saved school (or links it to one already saved), marks it as
     * planned, and opens the mission form for that school.
     */
    public static function addSchoolAction(): Action
    {
        return Action::make('addSchoolAndPlan')
            ->label('Add school & plan mission')
            ->icon('heroicon-m-academic-cap')
            ->slideOver()
            ->modalWidth(Width::TwoExtraLarge)
            ->modalHeading(fn(MissionGroundSuggestion $record): string => "Add “{$record->name}”")
            ->modalDescription(
                'Save the school, then carry on to plan the mission. Details from the suggestion are already filled in.',
            )
            ->modalSubmitActionLabel('Save and plan the mission')
            ->fillForm(fn(MissionGroundSuggestion $record): array => SchoolSchema::quickCreateDefaults([
                'existing_school_ulid' => 'new',
                'name' => $record->name,
                'contact_name' => $record->contact_person,
                'contact_phone' => $record->contact_number,
            ]))
            ->schema(fn(MissionGroundSuggestion $record): array => [
                Radio::make('existing_school_ulid')
                    ->label('Is it one of these saved schools?')
                    ->options(fn(): array => [
                        ...SchoolSchema::similarSchools($record->name)->mapWithKeys(fn(School $school): array => [
                            $school->ulid =>
                                $school->name
                                    . (filled($school->address) ? ' · ' . Str::limit($school->address, 50) : '')
                                    . (SchoolSchema::isActive($school) ? '' : ' (inactive)'),
                        ])->all(),
                        'new' => 'No, it is a new school',
                    ])
                    ->helperText('Pick the saved school to use it instead of adding it again.')
                    ->required()
                    ->live()
                    ->visible(fn(): bool => SchoolSchema::similarSchools($record->name)->isNotEmpty()),

                Group::make(SchoolSchema::quickCreateForm())
                    ->columnSpanFull()
                    ->visible(fn(Get $get): bool => ($get('existing_school_ulid') ?? 'new') === 'new'),
            ])
            ->action(function (array $data, MissionGroundSuggestion $record, Action $action): void {
                /** DB::transaction: there is no builder API for wrapping several jobs in one transaction. */
                $school = DB::transaction(function () use ($data, $record): School {
                    $school = self::schoolFor($data);

                    UpdateJob::dispatchSync([
                        'status' => PRFMissionGroundSuggestionStatus::MISSION_SECURED,
                    ], $record->ulid);

                    return $school;
                });

                Notification::make()
                    ->success()
                    ->title("{$school->name} is ready")
                    ->body('Now fill in the mission details.')
                    ->send();

                $action->redirect(SchoolSchema::planMissionUrl($school));
            })
            ->visible(
                fn(MissionGroundSuggestion $record): bool => (
                    !in_array(self::statusOf($record), self::CLOSED, true)
                    && userCan(School::permission('create'))
                    && userCan(Mission::permission('create'))
                ),
            );
    }

    /**
     * The saved school picked in the form (switched back on if it was inactive), or a new one.
     *
     * @param  array<mixed>  $data
     */
    private static function schoolFor(array $data): School
    {
        $existingULID = $data['existing_school_ulid'] ?? 'new';

        if (!is_string($existingULID) || $existingULID === 'new') {
            return SchoolSchema::createFromQuickForm($data);
        }

        $school = School::query()->where('ulid', $existingULID)->firstOrFail();

        if (!SchoolSchema::isActive($school)) {
            $school = UpdateSchoolJob::dispatchSync(['is_active' => PRFActiveStatus::ACTIVE], $school->ulid);
            assert($school instanceof School);
        }

        return $school;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMissionGroundSuggestions::route('/'),
            'create' => CreateMissionGroundSuggestion::route('/create'),
            'view' => ViewMissionGroundSuggestion::route('/{record}'),
            'edit' => EditMissionGroundSuggestion::route('/{record}/edit'),
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
        return userCan(MissionGroundSuggestion::permission('viewAny'));
    }
}
