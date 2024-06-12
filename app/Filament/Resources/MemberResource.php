<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                    )
                    ->searchable(),
                Forms\Components\Select::make('marital_status_id')
                    ->relationship(
                        name: 'maritalStatus',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->required(),

                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\TextInput::make('postal_address'),
                Forms\Components\TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\Textarea::make('residence')
                    ->required(),
                Forms\Components\TextInput::make('year_of_salvation')
                    ->numeric(),
                Forms\Components\Select::make('gender')
                    ->required()
                    ->options(PRFGender::getOptions()),
                Forms\Components\Section::make('Local Church')
                    ->schema([
                        Forms\Components\Select::make('church_id')

                            ->relationship(
                                name: 'church',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable()
                            ->required(),
                        Forms\Components\Toggle::make('church_volunteer')
                            ->required(),
                        Forms\Components\TextInput::make('pastor')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Profession')
                    ->schema([
                        Forms\Components\Select::make('profession_id')
                            ->relationship(
                                name: 'profession',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('profession_institution')
                            ->label('Institution')
                            ->required(),
                        Forms\Components\Textarea::make('profession_location')
                            ->label('Location')
                            ->required(),
                        Forms\Components\TextInput::make('profession_contact')
                            ->label('Contact')
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Grid::make()
                    ->schema([]),
                Forms\Components\Toggle::make('accept_terms')
                    ->required(),
                Forms\Components\Toggle::make('approved')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MissionSubscriptionsRelationManager::class,
            RelationManagers\DepartmentsRelationManager::class,
            RelationManagers\GiftsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function  canAccess(): bool
    {
        return auth()->user()->can('viewAny users');
    }
}
