<?php

namespace App\Filament\Central\Resources\UserResource\RelationManagers;

use App\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenants';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $title = 'User Tenants';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('id')
                ->label('Tenant')
                ->options(Tenant::query()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->exists('tenants', 'id'),

            TextInput::make('pivot.role')->label('Role')->maxLength(255)->placeholder('e.g., admin, member'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tenant Name')->searchable()->sortable(),

                TextColumn::make('slug')->fontFamily('mono')->copyable(),

                TextColumn::make('pivot.role')->label('Role')->badge()->sortable(),

                TextColumn::make('pivot.created_at')->label('Joined')->dateTime('M j, Y')->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No tenants')
            ->emptyStateDescription('Assign this user to a tenant to grant them access.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
