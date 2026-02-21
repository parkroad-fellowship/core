<?php

namespace App\Filament\Resources\APIClients;

use App\Filament\Resources\APIClients\Pages\CreateAPIClient;
use App\Filament\Resources\APIClients\Pages\EditAPIClient;
use App\Filament\Resources\APIClients\Pages\ListAPIClients;
use App\Models\APIClient;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class APIClientResource extends Resource
{
    protected static ?string $model = APIClient::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'API Clients';

    protected static ?string $modelLabel = 'API Client';

    protected static ?string $pluralModelLabel = 'API Clients';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('API Client Details')
                    ->description('Configure an API client for request signing authentication')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Client Name')
                                    ->placeholder('e.g., PRF Mobile App, PRF Leadership App')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('app_id')
                                    ->label('App ID')
                                    ->placeholder('e.g., prf-mobile-app')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText('Unique identifier sent by the client in X-PRF-App-ID header'),

                                TextInput::make('secret')
                                    ->label('Secret Key')
                                    ->required()
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->default(fn () => Str::random(64))
                                    ->helperText('Shared secret used for HMAC-SHA256 request signing'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive clients cannot authenticate API requests'),
                            ]),

                        CheckboxList::make('allowed_roles')
                            ->label('Allowed Roles')
                            ->options(fn () => Role::query()->pluck('name', 'name')->toArray())
                            ->helperText('Select which roles can authenticate via this client. Leave empty to allow all roles.')
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-device-phone-mobile'),

                TextColumn::make('app_id')
                    ->label('App ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('allowed_roles')
                    ->label('Allowed Roles')
                    ->badge()
                    ->separator(',')
                    ->state(fn (APIClient $record): string => empty($record->allowed_roles) ? 'All' : implode(',', $record->allowed_roles))
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange),

                    DeleteAction::make()
                        ->color(Color::Red),

                    RestoreAction::make()
                        ->color(Color::Green),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No API clients configured')
            ->emptyStateDescription('Create an API client to enable request signing for your mobile apps.')
            ->emptyStateIcon('heroicon-o-key');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAPIClients::route('/'),
            'create' => CreateAPIClient::route('/create'),
            'edit' => EditAPIClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
