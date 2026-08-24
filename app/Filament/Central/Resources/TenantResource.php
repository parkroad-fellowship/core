<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\Central\Resources\TenantResource\Pages\EditTenant;
use App\Filament\Central\Resources\TenantResource\Pages\ListTenants;
use App\Filament\Central\Resources\TenantResource\Pages\ViewTenant;
use App\Filament\Central\Resources\TenantResource\RelationManagers\DomainsRelationManager;
use App\Filament\Central\Resources\TenantResource\RelationManagers\MembersRelationManager;
use App\Models\Tenant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenants';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tenant Details')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                    TextInput::make('slug')
                        ->required()
                        ->unique(table: 'tenants', column: 'data->slug', ignoreRecord: true)
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated(),

                    Toggle::make('is_active')->label('Active')->default(true),

                    TextInput::make('custom_domain')
                        ->label('Custom Domain')
                        ->placeholder('admin.example.org')
                        ->nullable()
                        ->maxLength(255),

                    TextInput::make('admin_email')
                        ->label('Admin Email')
                        ->email()
                        ->nullable()
                        ->maxLength(255)
                        ->helperText('User will be promoted to super admin of this tenant'),

                    TextInput::make('admin_password')
                        ->label('Admin Password')
                        ->password()
                        ->nullable()
                        ->maxLength(255)
                        ->helperText('Leave empty for auto-generated password'),

                    KeyValue::make('data')
                        ->label('Configuration Data')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->helperText('Additional configuration data for this tenant (JSON)'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(query: fn($query, $search) => $query->orWhere('data->name', 'like', "%{$search}%"))
                    ->sortable(query: fn($query, string $direction) => $query->orderBy('data->name', $direction))
                    ->weight('medium'),

                TextColumn::make('slug')
                    ->searchable(query: fn($query, $search) => $query->orWhere('data->slug', 'like', "%{$search}%"))
                    ->sortable(query: fn($query, string $direction) => $query->orderBy('data->slug', $direction))
                    ->fontFamily('mono')
                    ->copyable(),

                IconColumn::make('is_active')->label('Status')->boolean()->sortable(),

                TextColumn::make('domains_count')->counts('domains')->label('Domains')->sortable(),

                TextColumn::make('members_count')->counts('members')->label('Members')->sortable(),

                TextColumn::make('created_at')->label('Created')->dateTime('M j, Y')->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search tenants by name or slug...')
            ->emptyStateHeading('No tenants found')
            ->emptyStateDescription('Create your first tenant to get started.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
