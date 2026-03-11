<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\PRFActiveStatus;
use Carbon\Carbon;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GroupMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'groupMembers';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $label = 'Group Membership';

    protected static ?string $pluralLabel = 'Group Memberships';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('👥 Group Membership Details')
                    ->description('Group participation and membership information')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('group_id')
                                    ->label('👥 Group')
                                    ->helperText('Select the group for this membership')
                                    ->required()
                                    ->searchable()
                                    ->relationship(
                                        name: 'group',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->preload()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Textarea::make('description')
                                            ->rows(3),
                                    ]),

                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('📅 Start Date')
                                    ->helperText('Date when membership began')
                                    ->native(false)
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('end_date')
                                    ->label('📅 End Date')
                                    ->helperText('Date when membership ended (optional)')
                                    ->native(false)
                                    ->after('start_date'),
                            ]),

                        Textarea::make('notes')
                            ->label('📝 Notes')
                            ->helperText('Additional notes about this group membership')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Any special notes about this membership...'),

                        Toggle::make('is_active')
                            ->label('📊 Active Membership')
                            ->helperText('Is this membership currently active?')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('group.name')
            ->columns([
                TextColumn::make('group.name')
                    ->label('👥 Group')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip('Group name'),

                TextColumn::make('start_date')
                    ->label('📅 Started')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Membership start date'),

                TextColumn::make('end_date')
                    ->label('📅 Ended')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->placeholder('Ongoing')
                    ->tooltip('Membership end date'),

                TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(function ($record) {
                        $start = Carbon::parse($record->start_date);
                        $end = $record->end_date ? Carbon::parse($record->end_date) : now();

                        return $start->diffForHumans($end, true);
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip('Membership duration'),

                IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Active membership' : 'Inactive membership'),

                TextColumn::make('notes')
                    ->label('📝 Notes')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date membership was recorded'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active memberships only'),

                SelectFilter::make('group')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All memberships')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Filter::make('membership_period')
                    ->label('Membership Period')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->native(false),
                                DatePicker::make('to_date')
                                    ->label('To Date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators[] = 'To: '.Carbon::parse($data['to_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_ended')
                    ->label('Membership Status')
                    ->placeholder('All memberships')
                    ->trueLabel('Ended memberships')
                    ->falseLabel('Ongoing memberships')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('end_date'),
                        false: fn (Builder $query) => $query->whereNull('end_date'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        $groupName = $record->group->name ?? 'Unknown Group';

                        Notification::make()
                            ->title('Group membership added')
                            ->body("Member added to '{$groupName}'.")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([

                Action::make('end_membership')
                    ->label('End Membership')
                    ->icon('heroicon-o-stop-circle')
                    ->color(Color::Orange)
                    ->schema([
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(today())
                            ->native(false),
                        Textarea::make('reason')
                            ->label('Reason')
                            ->placeholder('Reason for ending membership...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'end_date' => $data['end_date'],
                            'is_active' => false,
                            'notes' => ($record->notes ? $record->notes."\n" : '').'Ended: '.$data['reason'],
                        ]);

                        Notification::make()
                            ->title('Membership ended')
                            ->body('Group membership has been ended.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => ! $record->end_date)
                    ->tooltip('End this membership'),

                ViewAction::make()
                    ->color(Color::Gray),

                EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Membership updated')
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
                    BulkAction::make('end_memberships')
                        ->label('End Selected Memberships')
                        ->icon('heroicon-o-stop-circle')
                        ->color(Color::Orange)
                        ->form([
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(today())
                                ->native(false),
                            Textarea::make('reason')
                                ->label('Reason')
                                ->placeholder('Reason for ending memberships...'),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each(function ($record) use ($data) {
                                $record->update([
                                    'end_date' => $data['end_date'],
                                    'is_active' => false,
                                    'notes' => ($record->notes ? $record->notes."\n" : '').'Ended: '.$data['reason'],
                                ]);
                            });

                            Notification::make()
                                ->title('Memberships ended')
                                ->body("{$count} group memberships have been ended.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('activate_memberships')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Memberships activated')
                                ->body("{$count} memberships have been activated.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
