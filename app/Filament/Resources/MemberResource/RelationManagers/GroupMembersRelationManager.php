<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👥 Group Membership Details')
                    ->description('Group participation and membership information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('group_id')
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
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->rows(3),
                                    ]),

                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('📅 Start Date')
                                    ->helperText('Date when membership began')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->required()
                                    ->default(now()),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('📅 End Date')
                                    ->helperText('Date when membership ended (optional)')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->after('start_date'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('📝 Notes')
                            ->helperText('Additional notes about this group membership')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Any special notes about this membership...'),

                        Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('group.name')
                    ->label('👥 Group')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip('Group name'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('📅 Started')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Membership start date'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('📅 Ended')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->placeholder('Ongoing')
                    ->tooltip('Membership end date'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('⏱️ Duration')
                    ->getStateUsing(function ($record) {
                        $start = \Carbon\Carbon::parse($record->start_date);
                        $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date) : now();

                        return $start->diffForHumans($end, true);
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip('Membership duration'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Active membership' : 'Inactive membership'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('📝 Notes')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date membership was recorded'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active memberships only'),

                Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All memberships')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('membership_period')
                    ->label('Membership Period')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->native(false),
                                Forms\Components\DatePicker::make('to_date')
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
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['to_date'] ?? null) {
                            $indicators[] = 'To: '.\Carbon\Carbon::parse($data['to_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('has_ended')
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
                Tables\Actions\CreateAction::make()
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
            ->actions([

                Tables\Actions\Action::make('end_membership')
                    ->label('End Membership')
                    ->icon('heroicon-o-stop-circle')
                    ->color(Color::Orange)
                    ->form([
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(today())
                            ->native(false),
                        Forms\Components\Textarea::make('reason')
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

                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Membership updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\ForceDeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('end_memberships')
                        ->label('End Selected Memberships')
                        ->icon('heroicon-o-stop-circle')
                        ->color(Color::Orange)
                        ->form([
                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(today())
                                ->native(false),
                            Forms\Components\Textarea::make('reason')
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

                    Tables\Actions\BulkAction::make('activate_memberships')
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

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
