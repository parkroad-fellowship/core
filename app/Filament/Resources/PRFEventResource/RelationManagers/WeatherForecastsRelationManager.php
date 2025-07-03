<?php

namespace App\Filament\Resources\PRFEventResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WeatherForecastsRelationManager extends RelationManager
{
    protected static string $relationship = 'weatherForecasts';

    protected static ?string $navigationIcon = 'heroicon-o-cloud';

    protected static ?string $label = 'Weather Forecast';

    protected static ?string $pluralLabel = 'Weather Forecasts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🗓️ Forecast Date & Conditions')
                    ->description('Date and weather condition for the forecast')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('forecast_date')
                                    ->label('Forecast Date')
                                    ->helperText('Date for which this weather forecast applies')
                                    ->native(false)
                                    ->required()
                                    ->timezone(Auth::user()->timezone),

                                Forms\Components\Select::make('weather_code')
                                    ->label('Weather Condition')
                                    ->helperText('Select the weather condition for this date')
                                    ->required()
                                    ->searchable()
                                    ->options(collect(config('prf.weather.codes'))
                                        ->mapWithKeys(fn ($code) => [$code['key'] => $code['value']])
                                        ->toArray()),
                            ]),
                    ]),

                Forms\Components\Section::make('🌅 Sun & Moon Schedule')
                    ->description('Sunrise, sunset, moonrise, and moonset times')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('sun_rise_time')
                                    ->label('🌅 Sunrise Time')
                                    ->helperText('Time when the sun rises')
                                    ->required()
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                Forms\Components\DateTimePicker::make('sun_set_time')
                                    ->label('🌇 Sunset Time')
                                    ->helperText('Time when the sun sets')
                                    ->required()
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                Forms\Components\DateTimePicker::make('moon_rise_time')
                                    ->label('🌙 Moonrise Time')
                                    ->helperText('Time when the moon rises')
                                    ->required()
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                Forms\Components\DateTimePicker::make('moon_set_time')
                                    ->label('🌑 Moonset Time')
                                    ->helperText('Time when the moon sets')
                                    ->required()
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),
                            ]),
                    ])->collapsible(),

                Forms\Components\Section::make('🌤️ Weather Data')
                    ->description('Detailed weather measurements and conditions')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\KeyValue::make('temperature')
                                    ->label('🌡️ Temperature')
                                    ->helperText('Temperature readings throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('°C')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('humidity')
                                    ->label('💧 Humidity')
                                    ->helperText('Humidity percentage throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('wind')
                                    ->label('💨 Wind')
                                    ->helperText('Wind speed and direction')
                                    ->required()
                                    ->keyLabel('Metric')
                                    ->valueLabel('Value')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('cloud_cover')
                                    ->label('☁️ Cloud Cover')
                                    ->helperText('Cloud coverage throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('precipitation_probability')
                                    ->label('🌧️ Rain Probability')
                                    ->helperText('Probability of precipitation')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('visibility')
                                    ->label('👀 Visibility')
                                    ->helperText('Visibility distance')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('km')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\KeyValue::make('dew_point')
                                    ->label('💦 Dew Point')
                                    ->helperText('Dew point temperature')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('°C')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('uv')
                                    ->label('☀️ UV Index')
                                    ->helperText('UV radiation index')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('Index')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                Forms\Components\KeyValue::make('rain')
                                    ->label('🌧️ Rainfall')
                                    ->helperText('Expected rainfall amounts')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('mm')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),
                            ]),
                    ])->collapsible(),

                Forms\Components\Section::make('📝 Recommendations')
                    ->description('Weather-based recommendations for the mission')
                    ->schema([
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->label('👔 Dressing Recommendations')
                            ->helperText('Clothing and attire suggestions based on weather conditions')
                            ->rows(4)
                            ->placeholder('e.g., Light clothing recommended, carry light jackets for evening...'),

                        Forms\Components\Textarea::make('activity_recommendations')
                            ->label('🏃 Activity Recommendations')
                            ->helperText('Activity suggestions and precautions based on weather')
                            ->rows(4)
                            ->placeholder('e.g., Perfect weather for outdoor activities, indoor backup recommended for afternoon...'),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('forecast_date')
            ->columns([
                Tables\Columns\TextColumn::make('forecast_date')
                    ->label('📅 Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Forecast date'),

                Tables\Columns\TextColumn::make('weather_code')
                    ->label('🌤️ Condition')
                    ->formatStateUsing(fn (string $state): string => collect(config('prf.weather.codes'))->firstWhere('key', $state)['value'] ?? 'Unknown'
                    )
                    ->badge()
                    ->color('primary')
                    ->tooltip('Weather condition'),

                Tables\Columns\TextColumn::make('temperature_range')
                    ->label('🌡️ Temperature')
                    ->getStateUsing(function ($record) {
                        $temps = collect($record->temperature ?? [])
                            ->filter(fn ($value) => is_numeric($value))
                            ->values();
                        if ($temps->isEmpty()) {
                            return 'N/A';
                        }
                        $min = $temps->min();
                        $max = $temps->max();

                        return "{$min}°C - {$max}°C";
                    })
                    ->badge()
                    ->color('warning')
                    ->tooltip('Temperature range'),

                Tables\Columns\TextColumn::make('sun_rise_time')
                    ->label('🌅 Sunrise')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable()
                    ->tooltip('Sunrise time'),

                Tables\Columns\TextColumn::make('sun_set_time')
                    ->label('🌇 Sunset')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable()
                    ->tooltip('Sunset time'),

                Tables\Columns\TextColumn::make('precipitation_chance')
                    ->label('🌧️ Rain Chance')
                    ->getStateUsing(function ($record) {
                        $precip = collect($record->precipitation_probability ?? [])
                            ->filter(fn ($value) => is_numeric($value));
                        if ($precip->isEmpty()) {
                            return 'N/A';
                        }
                        $avg = $precip->avg();

                        return round($avg, 1).'%';
                    })
                    ->badge()
                    ->color('info')
                    ->tooltip('Chance of precipitation'),

                Tables\Columns\IconColumn::make('has_recommendations')
                    ->label('📝 Recommendations')
                    ->getStateUsing(fn ($record) => ! empty($record->dressing_recommendations) || ! empty($record->activity_recommendations))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => ($record->dressing_recommendations || $record->activity_recommendations) ? 'Has recommendations' : 'No recommendations'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date forecast was added'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('weather_code')
                    ->label('Weather Condition')
                    ->options(collect(config('prf.weather.codes'))
                        ->mapWithKeys(fn ($code) => [$code['key'] => $code['value']])
                        ->toArray()),

                Tables\Filters\Filter::make('forecast_date')
                    ->label('Forecast Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('forecast_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('forecast_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until: '.\Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TernaryFilter::make('has_recommendations')
                    ->label('Has Recommendations')
                    ->placeholder('All forecasts')
                    ->trueLabel('With recommendations')
                    ->falseLabel('Without recommendations')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNotNull('dressing_recommendations')
                                ->orWhereNotNull('activity_recommendations');
                        }),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNull('dressing_recommendations')
                                ->whereNull('activity_recommendations');
                        }),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Weather forecast added')
                            ->body('Weather forecast has been successfully recorded.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                

                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Forecast updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\ForceDeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('generate_bulk_recommendations')
                        ->label('Generate Recommendations')
                        ->icon('heroicon-o-light-bulb')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Bulk recommendation generation started')
                                ->body('Generating recommendations for '.count($records).' forecasts.')
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->defaultSort('forecast_date', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
