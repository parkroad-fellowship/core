<?php

namespace App\Filament\Resources;

use App\Console\Commands\Member\ImportCommand;
use App\Console\Commands\Member\InviteMembersCommand;
use App\Enums\PRFActiveStatus;
use App\Enums\PRFGender;
use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make(Member::PROFILE_PICTURES)
                    ->label('Profile Picture')
                    ->columnSpanFull()
                    ->collection(Member::PROFILE_PICTURES)
                    ->disk(config('media-library.disk_name')),
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('personal_email')
                            ->email()
                            // ->unique('members', 'personal_email')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()

                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\TextInput::make('postal_address'),
                PhoneInput::make('phone_number')
                    ->required(),
                Forms\Components\Textarea::make('residence'),
                Forms\Components\Textarea::make('bio'),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('marital_status_id')
                            ->relationship(
                                name: 'maritalStatus',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            ),
                        Forms\Components\Select::make('gender')
                            ->required()
                            ->options(PRFGender::getOptions()),
                        Forms\Components\TextInput::make('year_of_salvation')
                            ->numeric(),
                    ])->columns(3),
                Forms\Components\Section::make('Local Church')
                    ->schema([
                        Forms\Components\Select::make('church_id')
                            ->relationship(
                                name: 'church',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable(),
                        Forms\Components\Toggle::make('church_volunteer'),
                        Forms\Components\TextInput::make('pastor'),
                    ]),
                Forms\Components\Section::make('Profession')
                    ->schema([
                        Forms\Components\Select::make('profession_id')
                            ->relationship(
                                name: 'profession',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable(),
                        Forms\Components\TextInput::make('profession_institution')
                            ->label('Institution'),
                        Forms\Components\Textarea::make('profession_location')
                            ->label('Location'),
                        Forms\Components\TextInput::make('profession_contact')
                            ->label('Contact'),
                        Forms\Components\TextInput::make('linked_in_url')
                            ->label('LinkedIn URL'),
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
                Tables\Columns\TextColumn::make('email')
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
                Tables\Filters\SelectFilter::make('approved')
                    ->options([
                        true => 'Approved',
                        false => 'Not Approved',
                    ])
                    ->default(true)
                    ->label('Approved'),
                Tables\Filters\SelectFilter::make('is_invited')
                    ->options([
                        true => 'Invited',
                        false => 'Pending Invite',
                    ])
                    ->label('Invited'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view member')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit member')),
            ])
            ->headerActions([
                Actions\Action::make('Import')
                    ->label('Import Members')
                    ->action(function () {
                        Artisan::call(ImportCommand::class);
                    }),
                Actions\Action::make('Invite')
                    ->label('Send all new credentials')
                    ->action(function () {
                        Artisan::call(InviteMembersCommand::class);
                    }),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete member')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MembershipsRelationManager::class,
            RelationManagers\MissionSubscriptionsRelationManager::class,
            RelationManagers\DepartmentsRelationManager::class,
            RelationManagers\GiftsRelationManager::class,
            RelationManagers\GroupMembersRelationManager::class,
            RelationManagers\CourseMembersRelationManager::class,
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

    public static function canAccess(): bool
    {
        return userCan('viewAny member');
    }
}
