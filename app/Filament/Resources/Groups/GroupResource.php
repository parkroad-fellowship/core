<?php

namespace App\Filament\Resources\Groups;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\Pages\ViewGroup;
use App\Filament\Resources\Groups\RelationManagers\GroupMembersRelationManager;
use App\Models\Group;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?string $label = 'PRF Groups';

    protected static ?string $pluralModelLabel = 'PRF Groups';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationTooltip = 'Manage PRF groups and communities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Group Identity')
                    ->description('Define the basic details for this PRF group')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Group Name',
                                    placeholder: 'e.g., Nairobi Central Fellowship Group',
                                    required: true,
                                    helperText: 'Choose a clear, descriptive name that identifies this group',
                                ),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Group Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    required: true,
                                    hiddenOnCreate: true,
                                    helperText: 'Set whether this group is currently active or inactive',
                                ),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('About This Group')
                    ->description('Provide details about the group\'s purpose and activities')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Group Description',
                            rows: 4,
                            required: true,
                            placeholder: 'Describe the purpose, activities, and meeting schedule of this group. For example: "This group meets every Tuesday at 6pm for prayer and Bible study. We focus on outreach activities in the local community."',
                            helperText: 'This description helps members understand what the group is about',
                        ),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Communication Channel')
                    ->description('Set up the group\'s official communication platform')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        ContactSchema::whatsAppField(
                            name: 'official_whatsapp_link',
                            label: 'WhatsApp Group Link',
                            helperText: 'Paste the invite link to the official WhatsApp group for members to join',
                        )
                            ->placeholder('https://chat.whatsapp.com/ABC123xyz...'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Group Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-users')
                    ->description(fn (Group $record): string => str($record->description)->limit(80)->toString())
                    ->wrap()
                    ->tooltip('Name of the PRF group'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable()
                    ->tooltip('Current status of the group'),

                TextColumn::make('group_members_count')
                    ->label('Members')
                    ->counts('groupMembers')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Total number of members in this group'),

                IconColumn::make('official_whatsapp_link')
                    ->label('WhatsApp')
                    ->boolean()
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color(fn ($record) => $record->official_whatsapp_link ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->official_whatsapp_link ? 'WhatsApp group link is available' : 'No WhatsApp link configured'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable()
                    ->tooltip('Date and time when the group was created'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable()
                    ->tooltip('Date and time of the last update'),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not deleted')
                    ->tooltip('Date and time when the group was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false)
                    ->label('Show Deleted Groups'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active Groups',
                        PRFActiveStatus::INACTIVE->value => 'Inactive Groups',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false)
                    ->placeholder('All Statuses'),

                Filter::make('with_whatsapp')
                    ->label('Has WhatsApp Link')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('official_whatsapp_link'))
                    ->toggle(),

                Filter::make('with_members')
                    ->label('Has Members')
                    ->query(fn (Builder $query): Builder => $query->has('groupMembers'))
                    ->toggle(),

                Filter::make('empty_groups')
                    ->label('No Members')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('groupMembers'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view group'))
                    ->tooltip('View full group details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit group'))
                    ->tooltip('Edit group information'),

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Group $record) => $record->official_whatsapp_link)
                    ->openUrlInNewTab()
                    ->tooltip('Open WhatsApp group in a new tab')
                    ->visible(fn (Group $record) => ! empty($record->official_whatsapp_link)),

                Action::make('toggle_status')
                    ->label(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Group $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value
                        ? 'Set this group as inactive'
                        : 'Set this group as active')
                    ->visible(fn () => userCan('edit group')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete group')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete group')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete group')),

                    BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit group')),

                    BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit group')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            GroupMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'view' => ViewGroup::route('/{record}'),
            'edit' => EditGroup::route('/{record}/edit'),
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
        return userCan('viewAny group');
    }
}
