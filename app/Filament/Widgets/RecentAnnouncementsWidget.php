<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAnnouncementsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Announcements';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Announcement::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Posted'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
