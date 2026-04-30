<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use App\Enums\PRFGender;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MissionSubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionSubscriptions';

    protected static ?string $title = '👥 Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static ?string $pluralModelLabel = 'Subscriptions';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-users';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->missionSubscriptions()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->missionSubscriptions()->count();
        $capacity = $ownerRecord->capacity ?? 1;
        $percentage = ($count / $capacity) * 100;

        return match (true) {
            $percentage >= 100 => 'success',
            $percentage >= 80 => 'warning',
            $percentage >= 50 => 'info',
            default => 'gray',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member Information')
                    ->schema([
                        Select::make('member_id')
                            ->required()
                            ->relationship('member', 'full_name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} - {$record->phone_number}")
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $member = Member::find($state);
                                    if ($member) {
                                        $set('phone_display', $member->phone_number);
                                        $set('gender_display', $member->gender ? PRFGender::fromValue($member->gender)->name : 'Not specified');
                                    }
                                } else {
                                    $set('phone_display', null);
                                    $set('gender_display', null);
                                }
                            }),
                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('phone_display')
                                    ->label('Phone Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record && $record->member) {
                                            $component->state($record->member->phone_number);
                                        }
                                    }),
                                TextInput::make('gender_display')
                                    ->label('Gender')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record && $record->member && $record->member->gender) {
                                            $component->state(PRFGender::fromValue($record->member->gender)->name);
                                        }
                                    }),
                            ])->columns(2),
                    ]),
                Section::make('Mission Details')
                    ->schema([
                        Grid::make()
                            ->columnSpanFull()
                            ->schema([
                                Select::make('mission_role')
                                    ->required()
                                    ->options(PRFMissionRole::getOptions())
                                    ->default(PRFMissionRole::MEMBER->value)
                                    ->live()
                                    ->helperText('Select the role this member will have in the mission'),
                                Select::make('status')
                                    ->required()
                                    ->options(PRFMissionSubscriptionStatus::getOptions())
                                    ->default(PRFMissionSubscriptionStatus::PENDING->value)
                                    ->live()
                                    ->helperText('Current status of this subscription'),
                            ])->columns(2),
                        Repeater::make('notes')
                            ->label('Notes')
                            ->helperText('Add notes related to this subscription. You can add multiple notes.')
                            ->schema([
                                Textarea::make('note')
                                    ->required()
                                    ->label('Note')
                                    ->rows(3)
                                    ->placeholder('Enter your note here...'),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->minItems(0)
                            ->maxItems(10)
                            ->dehydrated(fn ($state) => ! empty($state))
                            ->columnSpan('full'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('member.name')
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('👤 Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->member?->email)
                    ->tooltip('Member full name and email'),

                TextColumn::make('member.gender')
                    ->label('⚧ Gender')
                    ->formatStateUsing(fn ($record) => PRFGender::fromValue($record->member->gender)->name)
                    ->badge()
                    ->color(fn ($record) => match (PRFGender::fromValue($record->member->gender)) {
                        PRFGender::MALE => 'info',
                        PRFGender::FEMALE => 'pink',
                        default => 'gray',
                    })
                    ->tooltip('Member gender'),

                PhoneColumn::make('member.phone_number')
                    ->label('📞 Phone')
                    ->tooltip('Click to call'),

                TextColumn::make('mission_role')
                    ->label('🎯 Role')
                    ->formatStateUsing(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getLabel())
                    ->badge()
                    ->color(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getColor())
                    ->icon(fn ($record) => PRFMissionRole::fromValue($record->mission_role)->getIcon())
                    ->sortable()
                    ->tooltip('Member role in the mission'),

                TextColumn::make('status')
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getLabel())
                    ->badge()
                    ->color(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getColor())
                    ->icon(fn ($record) => PRFMissionSubscriptionStatus::fromValue($record->status)->getIcon())
                    ->sortable()
                    ->tooltip('Subscription status'),

                TextColumn::make('created_at')
                    ->label('📅 Subscribed')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date of subscription'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('📊 Status')
                    ->multiple()
                    ->options(PRFMissionSubscriptionStatus::getOptions())
                    ->default([
                        PRFMissionSubscriptionStatus::PENDING->value,
                        PRFMissionSubscriptionStatus::APPROVED->value,
                        PRFMissionSubscriptionStatus::CONFLICT->value,
                    ]),

                SelectFilter::make('mission_role')
                    ->label('🎯 Role')
                    ->multiple()
                    ->options(PRFMissionRole::getOptions()),

                SelectFilter::make('gender')
                    ->label('⚧ Gender')
                    ->options(PRFGender::getOptions())
                    ->modifyQueryUsing(fn ($query, $data) => $data['value']
                        ? $query->whereHas('member', fn ($q) => $q->where('gender', $data['value']))
                        : $query
                    ),

                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->label('Add Member'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionSubscriptionStatus::APPROVED->value]);
                            Notification::make()
                                ->title('Subscription approved')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record && PRFMissionSubscriptionStatus::fromValue($record->status) === PRFMissionSubscriptionStatus::PENDING)
                        ->requiresConfirmation(),

                    Action::make('withdraw')
                        ->label('Withdraw')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionSubscriptionStatus::WITHDRAWN->value]);
                            Notification::make()
                                ->title('Subscription withdrawn')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn ($record) => $record && PRFMissionSubscriptionStatus::fromValue($record->status) === PRFMissionSubscriptionStatus::PENDING)
                        ->requiresConfirmation(),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square'),

                    Action::make('view_member')
                        ->label('View Profile')
                        ->color('info')
                        ->icon('heroicon-o-user')
                        ->url(fn ($record) => route('filament.admin.resources.members.view', ['record' => $record->member_id]))
                        ->openUrlInNewTab(),

                    DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (PRFMissionSubscriptionStatus::fromValue($record->status) === PRFMissionSubscriptionStatus::PENDING) {
                                    $record->update(['status' => PRFMissionSubscriptionStatus::APPROVED->value]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} subscriptions approved")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_withdraw')
                        ->label('Withdraw Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (PRFMissionSubscriptionStatus::fromValue($record->status) === PRFMissionSubscriptionStatus::PENDING) {
                                    $record->update(['status' => PRFMissionSubscriptionStatus::WITHDRAWN->value]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} subscriptions withdrawn")
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('assign_role')
                        ->label('Assign Role')
                        ->icon('heroicon-o-user-plus')
                        ->color('info')
                        ->form([
                            Select::make('mission_role')
                                ->label('Mission Role')
                                ->options(PRFMissionRole::getOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update(['mission_role' => $data['mission_role']]);
                            }
                            Notification::make()
                                ->title('Roles assigned to '.count($records).' members')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('status', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    protected function canCreate(): bool
    {
        return userCan('create mission subscription');
    }
}
