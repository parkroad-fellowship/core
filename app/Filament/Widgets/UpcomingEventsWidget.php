<?php

namespace App\Filament\Widgets;

use App\Models\PRFEvent;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingEventsWidget extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Events';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(PRFEvent::query()->where('start_date', '>=', now())->orderBy('start_date')->limit(5))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('venue')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'confirmed',
                        'warning' => 'tentative',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
