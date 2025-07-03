<?php

namespace App\Filament\Widgets;

use App\Models\PrayerRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PrayerRequestsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Prayer Requests';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(PrayerRequest::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.full_name')
                    ->searchable()
                    ->label('Requester'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
