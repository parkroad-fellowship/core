<?php

namespace App\Filament\Resources;

use App\Enums\PRFMembershipType;
use App\Filament\Resources\MembershipResource\Pages;
use App\Models\Membership;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MembershipResource extends Resource
{
    protected static ?string $model = Membership::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Membership';

    protected static ?string $pluralModelLabel = 'Memberships';

    protected static ?string $navigationTooltip = 'Manage member registrations and types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Member Information')
                    ->description('Select the member and spiritual year')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship(
                                name: 'member',
                                titleAttribute: 'full_name',
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the member to register'),

                        Forms\Components\Select::make('spiritual_year_id')
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

                Forms\Components\Section::make('Membership Details')
                    ->description('Define membership type and payment amount')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Membership Type')
                            ->required()
                            ->options(PRFMembershipType::getOptions())
                            ->default(PRFMembershipType::FRIEND->value)
                            ->helperText('Choose the type of membership'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Payment Amount')
                            ->required()
                            ->numeric()
                            ->prefix('KES ')
                            ->helperText('Enter the membership fee amount')
                            ->placeholder('0.00'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Approval Status')
                    ->description('Set the approval status for this membership')
                    ->icon('heroicon-o-check-badge')
                    ->schema([
                        Forms\Components\Toggle::make('approved')
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
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->description(fn ($record) => $record->member?->email)
                    ->searchable(['full_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('spiritualYear.name')
                    ->label('Spiritual Year')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-calendar')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
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

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),

                Tables\Columns\IconColumn::make('approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn ($record) => $record->approved ? 'Approved' : 'Pending Approval'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Registered: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Membership Type')
                    ->options(PRFMembershipType::getOptions())
                    ->placeholder('All Types'),

                Tables\Filters\SelectFilter::make('spiritual_year')
                    ->label('Spiritual Year')
                    ->relationship(
                        name: 'spiritualYear',
                        titleAttribute: 'name',
                    )
                    ->placeholder('All Years'),

                Tables\Filters\TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All Memberships')
                    ->trueLabel('Approved Only')
                    ->falseLabel('Pending Only'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view membership')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit membership')),
                    Tables\Actions\Action::make('toggle_approval')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete membership')),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['approved' => true]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit membership')),
                    Tables\Actions\BulkAction::make('unapprove')
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
            'index' => Pages\ListMemberships::route('/'),
            'create' => Pages\CreateMembership::route('/create'),
            'view' => Pages\ViewMembership::route('/{record}'),
            'edit' => Pages\EditMembership::route('/{record}/edit'),
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
