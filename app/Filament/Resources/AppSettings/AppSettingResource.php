<?php

namespace App\Filament\Resources\AppSettings;

use App\Filament\Resources\AppSettings\Pages\CreateAppSetting;
use App\Filament\Resources\AppSettings\Pages\EditAppSetting;
use App\Filament\Resources\AppSettings\Pages\ListAppSettings;
use App\Models\AppSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'App Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting')
                    ->schema([
                        TextInput::make('group')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Textarea::make('value')
                            ->nullable()
                            ->rows(3),

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
                    ->options(fn () => AppSetting::query()
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
            'index' => ListAppSettings::route('/'),
            'create' => CreateAppSetting::route('/create'),
            'edit' => EditAppSetting::route('/{record}/edit'),
        ];
    }
}
