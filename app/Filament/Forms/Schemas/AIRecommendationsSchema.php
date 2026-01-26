<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class AIRecommendationsSchema
{
    /**
     * Create a complete AI recommendations section.
     */
    public static function make(
        string $sectionTitle = 'AI Recommendations',
        string $sectionDescription = 'AI-generated recommendations based on weather and conditions',
        string $sectionIcon = 'heroicon-o-light-bulb',
        bool $collapsible = true,
        bool $collapsed = false,
        bool $includePreparationNotes = false,
        ?callable $visibleCallback = null,
    ): Section {
        $schema = [];

        if ($includePreparationNotes) {
            $schema[] = static::preparationNotesField();
        }

        $schema[] = Grid::make(2)
            ->columnSpanFull()
            ->schema([
                static::dressingRecommendationsField(),
                static::activityRecommendationsField(),
            ]);

        $section = Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->collapsible($collapsible)
            ->collapsed($collapsed);

        if ($visibleCallback) {
            $section->visible($visibleCallback);
        }

        return $section;
    }

    /**
     * Create a preparation notes field.
     */
    public static function preparationNotesField(
        string $name = 'mission_prep_notes',
        string $label = 'Preparation Notes',
        int $rows = 3,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->columnSpanFull()
            ->rows($rows)
            ->placeholder('e.g., "Bring extra water bottles", "Prepare song lyrics in advance", "Contact school principal by Tuesday"')
            ->helperText('Write any notes to help missionaries prepare for this event. Include reminders, special instructions, or things to bring.');
    }

    /**
     * Create a dressing recommendations field.
     */
    public static function dressingRecommendationsField(
        string $name = 'dressing_recommendations',
        string $label = 'Dressing Recommendations',
        int $rows = 3,
        bool $disabled = false,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->hint('Auto-generated based on weather forecast')
            ->rows($rows)
            ->disabled($disabled)
            ->placeholder('e.g., "Wear light, breathable clothing due to expected high temperatures"')
            ->helperText('Suggestions for what missionaries should wear based on expected weather conditions.');
    }

    /**
     * Create an activity recommendations field.
     */
    public static function activityRecommendationsField(
        string $name = 'activity_recommendations',
        string $label = 'Activity Recommendations',
        int $rows = 3,
        bool $disabled = false,
    ): Textarea {
        return Textarea::make($name)
            ->label($label)
            ->hint('Auto-generated based on weather forecast')
            ->rows($rows)
            ->disabled($disabled)
            ->placeholder('e.g., "Consider indoor activities if rain is expected"')
            ->helperText('Suggestions for activities based on expected weather conditions.');
    }

    /**
     * Create a weather-based recommendations section (read-only view).
     */
    public static function weatherSection(
        string $sectionTitle = 'Weather & Recommendations',
        string $sectionDescription = 'AI-generated recommendations based on weather',
        string $sectionIcon = 'heroicon-o-cloud',
        bool $collapsible = true,
    ): Section {
        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema([
                static::dressingRecommendationsField(disabled: true)
                    ->columnSpanFull()
                    ->helperText('AI-generated recommendations will appear here based on weather forecast'),
            ])
            ->collapsible($collapsible);
    }
}
