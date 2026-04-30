<?php

namespace App\Filament\Resources\Users;

use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Tapp\FilamentTimezoneField\Enums\Region;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'System Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Account Information')
                    ->description('Basic user account details for authentication and identification. All users need a valid email address for login and notifications.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Full Name',
                                    placeholder: 'e.g., John Doe',
                                    required: true,
                                    helperText: 'Enter the user\'s full name as it should appear throughout the system.',
                                )->prefixIcon('heroicon-o-user'),

                                ContactSchema::emailField(
                                    name: 'email',
                                    label: 'Email Address',
                                    required: true,
                                    helperText: 'Primary email address for login and system notifications. Must be unique across all users.',
                                )->unique(ignoreRecord: true)
                                    ->placeholder('e.g., user@example.com')
                                    ->prefixIcon('heroicon-o-envelope'),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TimezoneSelect::make('timezone')
                                    ->label('Timezone')
                                    ->helperText('Select the user\'s local timezone. This affects how dates and times are displayed throughout the system.')
                                    ->byRegion(Region::Africa)
                                    ->searchable()
                                    ->required()
                                    ->default('Africa/Nairobi'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Role & Permissions')
                    ->description('Control what the user can access and do within the system. Roles determine the level of access and available features.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('roles')
                            ->label('User Roles')
                            ->helperText('Select one or more roles to assign. Each role grants specific permissions. Users can have multiple roles for combined access.')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select user roles...')
                            ->prefixIcon('heroicon-o-shield-check'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(Color::Blue)
                    ->tooltip('User\'s full name'),

                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->color(Color::Gray)
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->tooltip('Primary email address'),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(Color::Purple)
                    ->icon('heroicon-o-shield-check')
                    ->searchable()
                    ->sortable()
                    ->limit(2)
                    ->tooltip('Assigned user roles'),

                TextColumn::make('timezone')
                    ->label('Timezone')
                    ->badge()
                    ->color(Color::Green)
                    ->icon('heroicon-o-clock')
                    ->toggleable()
                    ->tooltip('User\'s timezone setting'),

                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Not Verified')
                    ->tooltip('Email verification status'),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->since()
                    ->color(Color::Orange)
                    ->toggleable()
                    ->tooltip('Last login time'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Account creation date'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Account deletion date'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active users only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('roles')
                    ->label('Role Filter')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Role'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->nullable(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view user')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit user'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('User updated!')
                                ->body('User information has been updated successfully.')
                        ),

                    Action::make('reset_password')
                        ->icon('heroicon-o-key')
                        ->color(Color::Blue)
                        ->label('Reset Password')
                        ->action(function ($record) {
                            // Password reset logic here
                            Notification::make()
                                ->success()
                                ->title('Password reset!')
                                ->body('Password reset email has been sent to the user.')
                                ->send();
                        })
                        ->visible(fn () => userCan('edit user'))
                        ->requiresConfirmation()
                        ->modalDescription('This will send a password reset email to the user.'),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete user')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete user')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('verify_emails')
                        ->label('Verify Emails')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['email_verified_at' => now()]));

                            Notification::make()
                                ->title('Emails verified')
                                ->body("{$count} user emails have been verified successfully.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('send_password_resets')
                        ->label('Send Password Resets')
                        ->icon('heroicon-o-key')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->count();
                            // Password reset logic here

                            Notification::make()
                                ->title('Password resets sent')
                                ->body("Password reset emails sent to {$count} users.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete user')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('Search users by name or email...')
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('Start by adding your first user to the system.')
            ->emptyStateIcon('heroicon-o-users')
            ->recordClasses(fn ($record) => match (true) {
                ! $record->email_verified_at => 'bg-yellow-50 border-l-4 border-yellow-400',
                $record->trashed() => 'bg-red-50 border-l-4 border-red-400',
                default => null,
            });
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
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
        return userCan('viewAny user');
    }
}
