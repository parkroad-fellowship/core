<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use Carbon\Carbon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WeatherForecastsRelationManager extends RelationManager
{
    protected static string $relationship = 'weatherForecasts';

    protected static ?string $navigationIcon = 'heroicon-o-cloud';

    protected static ?string $title = '🌤️ Weather';

    protected static ?string $label = 'Weather Forecast';

    protected static ?string $pluralLabel = 'Weather Forecasts';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->weatherForecasts()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('🗓️ Forecast Date & Conditions')
                    ->description('Date and weather condition for the forecast')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('forecast_date')
                                    ->label('Forecast Date')
                                    ->helperText('Date for which this weather forecast applies')
                                    ->native(false)
                                    ->required()
                                    ->timezone(Auth::user()->timezone),

                                Select::make('weather_code')
                                    ->label('Weather Condition')
                                    ->helperText('Select the weather condition for this date')
                                    ->required()
                                    ->searchable()
                                    ->options(collect(config('prf.weather.codes'))
                                        ->mapWithKeys(fn ($code) => [$code['key'] => $code['value']])
                                        ->toArray()),
                            ]),
                    ])->columnSpanFull(),

                Section::make('🌅 Sun & Moon Schedule')
                    ->description('Sunrise, sunset, moonrise, and moonset times')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DateTimePicker::make('sun_rise_time')
                                    ->label('🌅 Sunrise Time')
                                    ->helperText('Time when the sun rises')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                DateTimePicker::make('sun_set_time')
                                    ->label('🌇 Sunset Time')
                                    ->helperText('Time when the sun sets')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                DateTimePicker::make('moon_rise_time')
                                    ->label('🌙 Moonrise Time')
                                    ->helperText('Time when the moon rises')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),

                                DateTimePicker::make('moon_set_time')
                                    ->label('🌑 Moonset Time')
                                    ->helperText('Time when the moon sets')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->timezone(Auth::user()->timezone),
                            ]),
                    ])->columnSpanFull(),

                Section::make('🌤️ Weather Data')
                    ->description('Detailed weather measurements and conditions')
                    ->schema([
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                KeyValue::make('temperature')
                                    ->label('🌡️ Temperature')
                                    ->helperText('Temperature readings throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('°C')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('humidity')
                                    ->label('💧 Humidity')
                                    ->helperText('Humidity percentage throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('wind')
                                    ->label('💨 Wind')
                                    ->helperText('Wind speed and direction')
                                    ->required()
                                    ->keyLabel('Metric')
                                    ->valueLabel('Value')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('cloud_cover')
                                    ->label('☁️ Cloud Cover')
                                    ->helperText('Cloud coverage throughout the day')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('precipitation_probability')
                                    ->label('🌧️ Rain Probability')
                                    ->helperText('Probability of precipitation')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('%')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('visibility')
                                    ->label('👀 Visibility')
                                    ->helperText('Visibility distance')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('km')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),
                            ]),

                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                KeyValue::make('dew_point')
                                    ->label('💦 Dew Point')
                                    ->helperText('Dew point temperature')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('°C')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('uv')
                                    ->label('☀️ UV Index')
                                    ->helperText('UV radiation index')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('Index')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),

                                KeyValue::make('rain')
                                    ->label('🌧️ Rainfall')
                                    ->helperText('Expected rainfall amounts')
                                    ->required()
                                    ->keyLabel('Time')
                                    ->valueLabel('mm')
                                    ->editableKeys(false)
                                    ->editableValues(false)
                                    ->addable(false),
                            ]),
                    ])->columnSpanFull(),

                Section::make('📝 Recommendations')
                    ->description('Weather-based recommendations for the mission')
                    ->schema([
                        Textarea::make('dressing_recommendations')
                            ->label('👔 Dressing Recommendations')
                            ->helperText('Clothing and attire suggestions based on weather conditions')
                            ->rows(4)
                            ->placeholder('e.g., Light clothing recommended, carry light jackets for evening...'),

                        Textarea::make('activity_recommendations')
                            ->label('🏃 Activity Recommendations')
                            ->helperText('Activity suggestions and precautions based on weather')
                            ->rows(4)
                            ->placeholder('e.g., Perfect weather for outdoor activities, indoor backup recommended for afternoon...'),
                    ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('forecast_date')
            ->columns([
                TextColumn::make('forecast_date')
                    ->label('📅 Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip('Forecast date'),

                TextColumn::make('weather_code')
                    ->label('🌤️ Condition')
                    ->formatStateUsing(
                        fn (string $state): string => collect(config('prf.weather.codes'))->firstWhere('key', $state)['value'] ?? 'Unknown'
                    )
                    ->badge()
                    ->color('primary')
                    ->tooltip('Weather condition'),

                TextColumn::make('temperature_range')
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

                TextColumn::make('sun_rise_time')
                    ->label('🌅 Sunrise')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable()
                    ->tooltip('Sunrise time'),

                TextColumn::make('sun_set_time')
                    ->label('🌇 Sunset')
                    ->time('g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->toggleable()
                    ->tooltip('Sunset time'),

                TextColumn::make('precipitation_chance')
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

                IconColumn::make('has_recommendations')
                    ->label('📝 Recommendations')
                    ->getStateUsing(fn ($record) => ! empty($record->dressing_recommendations) || ! empty($record->activity_recommendations))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => ($record->dressing_recommendations || $record->activity_recommendations) ? 'Has recommendations' : 'No recommendations'),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date forecast was added'),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('weather_code')
                    ->label('Weather Condition')
                    ->options(collect(config('prf.weather.codes'))
                        ->mapWithKeys(fn ($code) => [$code['key'] => $code['value']])
                        ->toArray()),

                Filter::make('forecast_date')
                    ->label('Forecast Date Range')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->native(false)
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
                            $indicators[] = 'From: '.Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until: '.Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('has_recommendations')
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
                CreateAction::make()
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
            ->recordActions([

                ViewAction::make()
                    ->color(Color::Gray),

                EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Forecast updated')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->color(Color::Red),

                ForceDeleteAction::make()
                    ->color(Color::Red),

                RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    BulkAction::make('generate_bulk_recommendations')
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

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->defaultSort('forecast_date', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
