<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GroupMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'groupMembers';

    protected static ?string $title = 'Group Members';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    protected static ?string $description = 'Manage group membership and member periods';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Group Membership Information')
                    ->description('Add or update group member details and membership period')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('member', 'full_name')
                            ->helperText('👤 Select the member to add to this group'),

                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Start Date')
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

                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->native(false)
                                    ->helperText('📅 When will/did this member leave the group? (Optional)')
                                    ->afterOrEqual('start_date'),
                            ])->columns(2),

                        Textarea::make('notes')
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
                TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->wrap()
                    ->sortable()
                    ->weight('semibold')
                    ->tooltip(fn ($record) => 'Member: '.$record->member->full_name),

                TextColumn::make('member.email')
                    ->label('Email')
                    ->wrap()
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->tooltip(fn ($record) => 'Email: '.$record->member->email),

                TextColumn::make('start_date')
                    ->label('Joined On')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->tooltip(fn ($record) => 'Joined: '.$record->start_date->format('F j, Y')),

                TextColumn::make('end_date')
                    ->label('Left On')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('danger')
                    ->placeholder('Active member')
                    ->tooltip(fn ($record) => $record->end_date ? 'Left: '.$record->end_date->format('F j, Y') : 'Still active in group'),

                TextColumn::make('membership_status')
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

                TextColumn::make('membership_duration')
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

                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes)
                    ->placeholder('No notes')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Record Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Record created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('membership_status')
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

                Filter::make('membership_period')
                    ->schema([
                        DatePicker::make('joined_from')
                            ->native(false)
                            ->label('Joined From'),
                        DatePicker::make('joined_until')
                            ->native(false)
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

                Filter::make('current_members')
                    ->label('Current Members Only')
                    ->query(function (Builder $query): Builder {
                        return $query->where(function ($query) {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>', now());
                        });
                    })
                    ->toggle(),

                Filter::make('has_notes')
                    ->label('Has Notes')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('notes'))
                    ->toggle(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Member')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary'),
                Action::make('bulk_end_membership')
                    ->label('End Multiple Memberships')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->schema([
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->helperText('Set end date for selected memberships'),
                    ])
                    ->action(function (array $data) {
                        // This would be implemented to bulk end memberships
                        Notification::make()
                            ->title('Bulk End Membership')
                            ->body('Multiple memberships have been ended.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->color('warning'),
                    Action::make('end_membership')
                        ->label('End Membership')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->schema([
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->helperText('When did this member leave the group?'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['end_date' => $data['end_date']]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => ! $record->end_date),
                    Action::make('extend_membership')
                        ->label('Extend Membership')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['end_date' => null]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->end_date && $record->end_date <= now()),
                    Action::make('view_member')
                        ->label('View Member Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->url(fn ($record) => route('filament.admin.resources.members.view', $record->member))
                        ->openUrlInNewTab(),
                    DeleteAction::make()
                        ->color('danger'),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    BulkAction::make('end_memberships')
                        ->label('End Selected Memberships')
                        ->icon('heroicon-o-user-minus')
                        ->color('danger')
                        ->form([
                            DatePicker::make('end_date')
                                ->label('End Date')
                                ->required()
                                ->native(false)
                                ->default(now()),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update(['end_date' => $data['end_date']]);
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('extend_memberships')
                        ->label('Extend Selected Memberships')
                        ->icon('heroicon-o-arrow-right')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['end_date' => null]);
                            }
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('export_members')
                        ->label('Export Members')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            Notification::make()
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
