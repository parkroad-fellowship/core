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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('member_id')
                    ->relationship(
                        name: 'member',
                        titleAttribute: 'full_name',
                    ),
                Forms\Components\Select::make('spiritual_year_id')
                    ->relationship(
                        name: 'spiritualYear',
                        titleAttribute: 'name',
                    ),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(PRFMembershipType::getOptions())
                    ->default(PRFMembershipType::FRIEND),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Checkbox::make('approved'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.full_name'),
                Tables\Columns\TextColumn::make('spiritualYear.name')
                    ->numeric(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => PRFMembershipType::fromValue($record->type)->name)
                    ->sortable(),
                Tables\Columns\IconColumn::make('approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        PRFMembershipType::FRIEND->value => 'Friend',
                        PRFMembershipType::YEARLY_MEMBER->value => 'Yearly Member',
                        PRFMembershipType::LIFETIME_MEMBER->value => 'Lifetime Member',
                    ])
                    ->label('Membership Type'),
                Tables\Filters\SelectFilter::make('spiritual_year')
                    ->relationship(
                        name: 'spiritualYear',
                        titleAttribute: 'name',
                    ),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view membership')),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete membership')),
            ]);
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
