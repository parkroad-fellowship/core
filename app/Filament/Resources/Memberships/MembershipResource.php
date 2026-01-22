<?php

namespace App\Filament\Resources\Memberships;

use App\Enums\PRFMembershipType;
use App\Filament\Resources\Memberships\Pages\CreateMembership;
use App\Filament\Resources\Memberships\Pages\EditMembership;
use App\Filament\Resources\Memberships\Pages\ListMemberships;
use App\Filament\Resources\Memberships\Pages\ViewMembership;
use App\Models\Membership;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Membership';

    protected static ?string $pluralModelLabel = 'Memberships';

    protected static ?string $navigationTooltip = 'Manage member registrations and types';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member Information')
                    ->description('Select the member and spiritual year')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->relationship(
                                name: 'member',
                                titleAttribute: 'full_name',
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the member to register'),

                        Select::make('spiritual_year_id')
                            ->label('Spiritual Year')
                            ->relationship(
                                name: 'spiritualYear',
                                titleAttribute: 'name',
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the spiritual year for this membership'),
                    ])
                    ->columns(2),

                Section::make('Membership Details')
                    ->description('Define membership type and payment amount')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Select::make('type')
                            ->label('Membership Type')
                            ->required()
                            ->options(PRFMembershipType::getOptions())
                            ->default(PRFMembershipType::FRIEND->value)
                            ->helperText('Choose the type of membership'),

                        TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('KES ')
                            ->helperText('Enter the membership fee amount')
                            ->placeholder('0.00'),
                    ])
                    ->columns(2),

                Section::make('Approval Status')
                    ->description('Set the approval status for this membership')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Toggle::make('approved')
                            ->label('Approved')
                            ->helperText('Toggle to approve or reject this membership')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->description(fn ($record) => $record->member?->email)
                    ->searchable(['full_name'])
                    ->sortable(),

                TextColumn::make('spiritualYear.name')
                    ->label('Spiritual Year')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-calendar')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Membership Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFMembershipType::fromValue($state)->getLabel())
                    ->color(fn ($state) => match ($state) {
                        PRFMembershipType::FRIEND->value => 'gray',
                        PRFMembershipType::YEARLY_MEMBER->value => 'warning',
                        PRFMembershipType::LIFETIME_MEMBER->value => 'success',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match ($state) {
                        PRFMembershipType::FRIEND->value => 'heroicon-o-heart',
                        PRFMembershipType::YEARLY_MEMBER->value => 'heroicon-o-clock',
                        PRFMembershipType::LIFETIME_MEMBER->value => 'heroicon-o-star',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),

                IconColumn::make('approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->approved ? 'Approved' : 'Pending Approval'),

                TextColumn::make('created_at')
                    ->label('Registered On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Registered: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('type')
                    ->label('Membership Type')
                    ->options(PRFMembershipType::getOptions())
                    ->placeholder('All Types'),

                SelectFilter::make('spiritual_year')
                    ->label('Spiritual Year')
                    ->relationship(
                        name: 'spiritualYear',
                        titleAttribute: 'name',
                    )
                    ->placeholder('All Years'),

                TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All Memberships')
                    ->trueLabel('Approved Only')
                    ->falseLabel('Pending Only'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view membership')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit membership')),
                    Action::make('toggle_approval')
                        ->label(fn ($record) => $record->approved ? 'Unapprove' : 'Approve')
                        ->icon(fn ($record) => $record->approved ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->approved ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update(['approved' => ! $record->approved]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit membership')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['approved' => true]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit membership')),
                    BulkAction::make('unapprove')
                        ->label('Unapprove Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['approved' => false]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit membership')),
                ])->visible(fn () => userCan('delete membership')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberships::route('/'),
            'create' => CreateMembership::route('/create'),
            'view' => ViewMembership::route('/{record}'),
            'edit' => EditMembership::route('/{record}/edit'),
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
        return userCan('viewAny membership');
    }
}
