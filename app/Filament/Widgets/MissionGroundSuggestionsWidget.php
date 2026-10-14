<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Models\MissionGroundSuggestion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MissionGroundSuggestionsWidget extends BaseWidget
{
    protected static ?string $heading = 'New school suggestions';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MissionGroundSuggestion::query()
                    ->where('status', PRFMissionGroundSuggestionStatus::PENDING)
                    ->latest()
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('name')->label('School')->searchable()->sortable(),

                TextColumn::make('contact_person')->label('Contact Person')->searchable(),

                TextColumn::make('contact_number')->label('Phone')->searchable(),

                TextColumn::make('suggestor.full_name')->label('Suggested By')->searchable(),

                TextColumn::make('created_at')->dateTime()->sortable()->label('Submitted'),
            ])
            ->paginated(false);
    }
}
