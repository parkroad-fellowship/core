<?php

namespace App\Filament\Central\Resources\TenantResource\RelationManagers;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $title = 'Tenant Members';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('id')
                ->label('User')
                ->options(User::query()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->exists('users', 'id'),

            TextInput::make('pivot.role')->label('Role')->maxLength(255)->placeholder('e.g., admin, member'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('User Name')->searchable()->sortable(),

                TextColumn::make('email')->searchable()->copyable(),

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
            ->emptyStateHeading('No members')
            ->emptyStateDescription('Add users to this tenant to grant them access.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
