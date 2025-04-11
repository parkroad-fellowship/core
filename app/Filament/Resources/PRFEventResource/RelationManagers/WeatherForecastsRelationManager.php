<?php

namespace App\Filament\Resources\PRFEventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WeatherForecastsRelationManager extends RelationManager
{
    protected static string $relationship = 'weatherForecasts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('forecast_date')
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('weather_code')
                    ->required()
                    ->searchable()
                    ->options(collect(config('prf.weather.codes'))
                        ->map(fn ($code) => [
                            $code['key'] => $code['value'],
                        ])
                        ->flatten()
                        ->toArray()),
                Forms\Components\Section::make('Preparation')
                    ->schema([
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->rows(5),
                    ]),
                Forms\Components\DateTimePicker::make('moon_rise_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('moon_set_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('sun_rise_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('sun_set_time')
                    ->required(),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\KeyValue::make('cloud_cover')
                            ->label('Cloud Cover')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false),
                        Forms\Components\KeyValue::make('dew_point')
                            ->label('Dew Point')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('humidity')
                            ->label('Humidity')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('precipitation_probability')
                            ->label('Precipitation Probability')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('visibility')
                            ->label('Visibility')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('temperature')
                            ->label('Temperature')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('uv')
                            ->label('UV')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('wind')
                            ->label('Wind')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                        Forms\Components\KeyValue::make('rain')
                            ->label('Rain')
                            ->required()
                            ->editableKeys(false)
                            ->editableValues(false)
                            ->addable(false),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('forecast_date')
            ->columns([
                Tables\Columns\TextColumn::make('forecast_date')
                    ->label('Date')
                    ->date()
                    ->timezone(Auth::user()->timezone)
                    ->sortable(),
                Tables\Columns\TextColumn::make('weather_code')
                    ->formatStateUsing(fn (string $state): string => collect(config('prf.weather.codes'))->firstWhere('key', $state)['value']),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
