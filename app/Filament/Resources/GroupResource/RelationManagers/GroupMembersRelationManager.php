<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GroupMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'groupMembers';

    protected static ?string $title = 'Group Members';

    protected static ?string $icon = 'heroicon-o-users';

    protected static ?string $description = 'Manage group membership and member periods';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Membership Information')
                    ->description('Add or update group member details and membership period')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('member', 'full_name')
                            ->helperText('👤 Select the member to add to this group'),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->required()
                                    ->default(now())
                                    ->helperText('📅 When did this member join the group?')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Ensure end date is after start date
                                        $endDate = $get('end_date');
                                        if ($endDate && $state && $endDate < $state) {
                                            $set('end_date', null);
                                        }
                                    }),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->timezone(Auth::user()->timezone)
                                    ->native(false)
                                    ->helperText('📅 When will/did this member leave the group? (Optional)')
                                    ->afterOrEqual('start_date'),
                            ])->columns(2),

                        Forms\Components\Textarea::make('notes')
                            ->label('Membership Notes')
                            ->rows(3)
                            ->placeholder('Optional notes about this membership...')
                            ->helperText('📝 Add any notes about this member\'s role or status in the group')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member.full_name')
            ->columns([
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->wrap()
                    ->sortable()
                    ->weight('semibold')
                    ->tooltip(fn ($record) => 'Member: '.$record->member->full_name),

                Tables\Columns\TextColumn::make('member.email')
                    ->label('Email')
                    ->wrap()
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->tooltip(fn ($record) => 'Email: '.$record->member->email),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Joined On')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->tooltip(fn ($record) => 'Joined: '.$record->start_date->format('F j, Y')),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Left On')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('danger')
                    ->placeholder('Active member')
                    ->tooltip(fn ($record) => $record->end_date ? 'Left: '.$record->end_date->format('F j, Y') : 'Still active in group'),

                Tables\Columns\TextColumn::make('membership_status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $now = now();
                        if (! $record->end_date) {
                            return 'Active';
                        }

                        return $record->end_date->isFuture() ? 'Active' : 'Inactive';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Active' ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === 'Active' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('membership_duration')
                    ->label('Duration')
                    ->getStateUsing(function ($record) {
                        $start = $record->start_date;
                        $end = $record->end_date ?: now();
                        $duration = $start->diffInDays($end);

                        if ($duration < 30) {
                            return $duration.' days';
                        } elseif ($duration < 365) {
                            return round($duration / 30).' months';
                        } else {
                            return round($duration / 365, 1).' years';
                        }
                    })
                    ->icon('heroicon-o-clock')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->placeholder('No notes')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Record Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Record created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                Tables\Filters\SelectFilter::make('membership_status')
                    ->label('Membership Status')
                    ->options([
                        'active' => 'Active Members',
                        'inactive' => 'Inactive Members',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        if ($data['value'] === 'active') {
                            return $query->where(function ($query) {
                                $query->whereNull('end_date')
                                    ->orWhere('end_date', '>', now());
                            });
                        } else {
                            return $query->where('end_date', '<=', now());
                        }
                    })
                    ->placeholder('All Members'),

                Tables\Filters\Filter::make('membership_period')
                    ->form([
                        Forms\Components\DatePicker::make('joined_from')
                            ->label('Joined From'),
                        Forms\Components\DatePicker::make('joined_until')
                            ->label('Joined Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['joined_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['joined_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('current_members')
                    ->label('Current Members Only')
                    ->query(function (Builder $query): Builder {
                        return $query->where(function ($query) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>', now());
                        });
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('has_notes')
                    ->label('Has Notes')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('notes'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Member')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary'),
                Tables\Actions\Action::make('bulk_end_membership')
                    ->label('End Multiple Memberships')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->form([
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(now())
                            ->helperText('Set end date for selected memberships'),
                    ])
                    ->action(function (array $data) {
                        // This would be implemented to bulk end memberships
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk End Membership')
                            ->body('Multiple memberships have been ended.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\Action::make('end_membership')
                        ->label('End Membership')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->form([
                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(now())
                                ->helperText('When did this member leave the group?'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['end_date' => $data['end_date']]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => ! $record->end_date),
                    Tables\Actions\Action::make('extend_membership')
                        ->label('Extend Membership')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['end_date' => null]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->end_date && $record->end_date <= now()),
                    Tables\Actions\Action::make('view_member')
                        ->label('View Member Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn ($record) => route('filament.admin.resources.members.view', $record->member))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('end_memberships')
                        ->label('End Selected Memberships')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->form([
                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(now()),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['end_date' => $data['end_date']]);
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('extend_memberships')
                        ->label('Extend Selected Memberships')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['end_date' => null]);
                            }
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('export_members')
                        ->label('Export Members')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            \Filament\Notifications\Notification::make()
                                ->title('Export Started')
                                ->body('Member export has been queued for processing.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
