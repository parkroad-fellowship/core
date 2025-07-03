<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?string $label = 'PRF Groups';

    protected static ?string $pluralModelLabel = 'PRF Groups';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationTooltip = 'Manage PRF groups and communities';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Group Information')
                    ->description('Define the PRF group details and communication')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Group Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive name for this PRF group')
                            ->placeholder('e.g., Nairobi Central Group'),
                        
                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this group')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Group Details')
                    ->description('Provide additional information about the group')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Group Description')
                            ->required()
                            ->rows(4)
                            ->helperText('Describe the purpose and activities of this group')
                            ->placeholder('Enter a detailed description of the group...'),
                    ]),

                Forms\Components\Section::make('Communication')
                    ->description('Set up group communication channels')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\TextInput::make('official_whatsapp_link')
                            ->label('Official WhatsApp Link')
                            ->url()
                            ->helperText('Provide the official WhatsApp group link for members')
                            ->placeholder('https://chat.whatsapp.com/...'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Group Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-users')
                    ->description(fn (Group $record): string => 
                        str($record->description)->limit(80)->toString()
                    )
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('group_members_count')
                    ->label('Members')
                    ->counts('groupMembers')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of members in this group'),
                
                Tables\Columns\IconColumn::make('official_whatsapp_link')
                    ->label('WhatsApp')
                    ->boolean()
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color(fn ($record) => $record->official_whatsapp_link ? 'success' : 'gray')
                    ->tooltip(fn ($record) => $record->official_whatsapp_link ? 'WhatsApp link available' : 'No WhatsApp link'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),
                
                Tables\Filters\Filter::make('with_whatsapp')
                    ->label('Groups with WhatsApp')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('official_whatsapp_link')
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('with_members')
                    ->label('Groups with Members')
                    ->query(fn (Builder $query): Builder => 
                        $query->has('groupMembers')
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('empty_groups')
                    ->label('Empty Groups')
                    ->query(fn (Builder $query): Builder => 
                        $query->doesntHave('groupMembers')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view group'))
                    ->tooltip('View group details'),
                
                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit group'))
                    ->tooltip('Edit this group'),
                
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Group $record) => $record->official_whatsapp_link)
                    ->openUrlInNewTab()
                    ->tooltip('Open WhatsApp group')
                    ->visible(fn (Group $record) => !empty($record->official_whatsapp_link)),
                
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Group $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Group $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value 
                                ? PRFActiveStatus::INACTIVE->value 
                                : PRFActiveStatus::ACTIVE->value
                        ]);
                    })
                    ->tooltip('Toggle group status')
                    ->visible(fn () => userCan('edit group')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete group')),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete group')),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete group')),
                    
                    Tables\Actions\BulkAction::make('bulk_activate')
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
                    
                    Tables\Actions\BulkAction::make('bulk_deactivate')
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
            RelationManagers\GroupMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'view' => Pages\ViewGroup::route('/{record}'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
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
