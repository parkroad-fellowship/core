<?php

namespace App\Filament\Widgets;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopMissionersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Missioners This Year';

    protected static ?int $sort = 12;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $currentYear = now()->year;

        return $table
            ->query(
                Member::query()
                    ->select('members.*')
                    ->selectRaw('COUNT(mission_subscriptions.id) as missions_count')
                    ->join('mission_subscriptions', 'members.id', '=', 'mission_subscriptions.member_id')
                    ->join('missions', 'missions.id', '=', 'mission_subscriptions.mission_id')
                    ->whereYear('missions.start_date', $currentYear)
                    ->where('mission_subscriptions.status', PRFMissionSubscriptionStatus::APPROVED->value)
                    ->whereNull('mission_subscriptions.deleted_at')
                    ->whereNull('missions.deleted_at')
                    ->groupBy('members.id')
                    ->orderByDesc('missions_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('full_name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('phone_number')
                    ->label('Phone'),

                TextColumn::make('missions_count')
                    ->label('Missions')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
