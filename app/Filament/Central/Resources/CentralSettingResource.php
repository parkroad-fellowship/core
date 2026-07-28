<?php

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\CentralSettingResource\Pages\CreateCentralSetting;
use App\Filament\Central\Resources\CentralSettingResource\Pages\EditCentralSetting;
use App\Filament\Central\Resources\CentralSettingResource\Pages\ListCentralSettings;
use App\Models\CentralSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CentralSettingResource extends Resource
{
    protected static ?string $model = CentralSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Central Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('group')
                            ->required()
                            ->maxLength(255)
                            ->default('general'),

                        TextInput::make('key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Use dot notation for grouping, e.g. admin.emails'),

                        Textarea::make('value')
                            ->nullable()
                            ->rows(3)
                            ->helperText('For array type, enter JSON-encoded values'),

                        Select::make('type')
                            ->options([
                                'string' => 'String',
                                'integer' => 'Integer',
                                'boolean' => 'Boolean',
                                'array' => 'Array (JSON)',
                            ])
                            ->default('string')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                TextColumn::make('key')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono'),

                TextColumn::make('value')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => CentralSetting::query()
                        ->distinct()
                        ->pluck('group', 'group')
                        ->toArray()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group')
            ->defaultGroup('group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCentralSettings::route('/'),
            'create' => CreateCentralSetting::route('/create'),
            'edit' => EditCentralSetting::route('/{record}/edit'),
        ];
    }
}
