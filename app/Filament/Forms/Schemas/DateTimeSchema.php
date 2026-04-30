<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class DateTimeSchema
{
    /**
     * Create a complete schedule section with date and time pickers.
     */
    public static function make(
        string $sectionTitle = 'Schedule',
        string $sectionDescription = 'Event date and time',
        string $sectionIcon = 'heroicon-o-calendar',
        bool $collapsible = true,
        bool $collapsed = false,
        ?callable $collapsedCallback = null,
    ): Section {
        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema([
                Grid::make(4)
                    ->columnSpanFull()
                    ->schema([
                        static::startDateField(),
                        static::startTimeField(),
                        static::endDateField(),
                        static::endTimeField(),
                    ]),
            ])
            ->collapsible($collapsible)
            ->collapsed($collapsedCallback ?? $collapsed);
    }

    /**
     * Create a start date picker field.
     */
    public static function startDateField(
        string $name = 'start_date',
        string $label = 'Start Date',
        bool $required = true,
        bool $autoSetEndDate = true,
    ): DatePicker {
        $field = DatePicker::make($name)
            ->label($label)
            ->native(false)
            ->required($required)
            ->live()
            ->helperText('When does this event begin?');

        if ($autoSetEndDate) {
            $field->afterStateUpdated(function ($state, callable $set, callable $get) {
                if ($state && ! $get('end_date')) {
                    $set('end_date', $state);
                }
            });
        }

        return $field;
    }

    /**
     * Create a start time picker field.
     */
    public static function startTimeField(
        string $name = 'start_time',
        string $label = 'Start Time',
        string $default = '08:00',
        bool $required = true,
    ): TimePicker {
        return TimePicker::make($name)
            ->label($label)
            ->seconds(false)
            ->native(false)
            ->required($required)
            ->default($default)
            ->format('H:i')
            ->helperText('What time should participants arrive?');
    }

    /**
     * Create an end date picker field.
     */
    public static function endDateField(
        string $name = 'end_date',
        string $label = 'End Date',
        string $afterOrEqual = 'start_date',
        bool $required = true,
    ): DatePicker {
        return DatePicker::make($name)
            ->label($label)
            ->native(false)
            ->required($required)
            ->afterOrEqual($afterOrEqual)
            ->helperText('Leave blank if event is one day only');
    }

    /**
     * Create an end time picker field.
     */
    public static function endTimeField(
        string $name = 'end_time',
        string $label = 'End Time',
        string $default = '17:00',
        bool $required = true,
    ): TimePicker {
        return TimePicker::make($name)
            ->label($label)
            ->seconds(false)
            ->native(false)
            ->required($required)
            ->default($default)
            ->format('H:i')
            ->helperText('Expected time when the event ends');
    }

    /**
     * Get just the date/time fields as an array (without the section wrapper).
     */
    public static function fields(bool $required = true): array
    {
        return [
            static::startDateField(required: $required),
            static::startTimeField(required: $required),
            static::endDateField(required: false),
            static::endTimeField(required: $required),
        ];
    }
}
