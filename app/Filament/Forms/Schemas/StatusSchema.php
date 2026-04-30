<?php

namespace App\Filament\Forms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;

class StatusSchema
{
    /**
     * Create a status select field from an enum.
     */
    public static function enumSelect(
        string $name,
        string $label,
        string $enumClass,
        mixed $default = null,
        bool $required = true,
        bool $hiddenOnCreate = true,
        ?string $helperText = null,
    ): Select {
        $field = Select::make($name)
            ->label($label)
            ->required($required)
            ->options($enumClass::getOptions())
            ->native(false);

        if ($default !== null) {
            $field->default($default);
        }

        if ($hiddenOnCreate) {
            $field->hiddenOn(['create']);
        }

        if ($helperText) {
            $field->helperText($helperText);
        }

        return $field;
    }

    /**
     * Create a relationship select field.
     */
    public static function relationshipSelect(
        string $name,
        string $label,
        string $relationship,
        string $titleAttribute = 'name',
        bool $required = true,
        bool $searchable = true,
        bool $preload = true,
        ?callable $modifyQuery = null,
        ?string $helperText = null,
    ): Select {
        $field = Select::make($name)
            ->label($label)
            ->required($required)
            ->relationship(
                name: $relationship,
                titleAttribute: $titleAttribute,
                modifyQueryUsing: $modifyQuery,
            )
            ->searchable($searchable)
            ->preload($preload)
            ->native(false);

        if ($helperText) {
            $field->helperText($helperText);
        }

        return $field;
    }

    /**
     * Create a statistics section with progress visualization.
     */
    public static function statisticsSection(
        string $sectionTitle = 'Statistics',
        string $sectionDescription = 'Status and statistics information',
        string $sectionIcon = 'heroicon-o-chart-bar',
        ?callable $contentCallback = null,
        bool $collapsible = true,
        ?callable $visibleCallback = null,
    ): Section {
        $schema = [];

        if ($contentCallback) {
            $schema[] = TextEntry::make('statistics')
                ->hiddenLabel()
                ->state($contentCallback)
                ->html()
                ->columnSpanFull();
        }

        $section = Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->collapsible($collapsible);

        if ($visibleCallback) {
            $section->visible($visibleCallback);
        }

        return $section;
    }

    /**
     * Create a feedback toggle field.
     */
    public static function feedbackToggle(
        string $name = 'feedback_requested_at',
        string $label = 'Feedback Requested',
        string $hint = 'Request feedback using the action button in the header.',
        bool $disabled = true,
    ): Toggle {
        return Toggle::make($name)
            ->label($label)
            ->hint($hint)
            ->disabled($disabled);
    }

    /**
     * Build a progress bar HTML string.
     */
    public static function buildProgressBar(
        float $percentage,
        ?string $label = null,
    ): HtmlString {
        $color = match (true) {
            $percentage >= 100 => 'bg-green-500',
            $percentage >= 80 => 'bg-yellow-500',
            $percentage >= 50 => 'bg-blue-500',
            default => 'bg-gray-400',
        };

        $width = min($percentage, 100);

        $html = "
            <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4'>
                <div class='{$color} h-4 rounded-full transition-all duration-300' style='width: {$width}%'></div>
            </div>
        ";

        if ($label) {
            $html .= "<div class='text-center text-sm text-gray-500'>{$label}</div>";
        } else {
            $html .= "<div class='text-center text-sm text-gray-500'>{$percentage}% complete</div>";
        }

        return new HtmlString($html);
    }

    /**
     * Build a stats card grid.
     */
    public static function buildStatsCards(array $stats): HtmlString
    {
        $html = "<div class='grid grid-cols-3 gap-4'>";

        foreach ($stats as $stat) {
            $value = $stat['value'] ?? 0;
            $label = $stat['label'] ?? '';
            $icon = $stat['icon'] ?? '';

            $html .= "
                <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center'>
                    <div class='text-2xl font-bold'>{$icon} {$value}</div>
                    <div class='text-sm text-gray-500'>{$label}</div>
                </div>
            ";
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
