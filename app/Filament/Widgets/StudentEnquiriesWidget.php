<?php

namespace App\Filament\Widgets;

use App\Models\StudentEnquiry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StudentEnquiriesWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Student Enquiries';

    protected static ?int $sort = 23;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StudentEnquiry::query()
                    ->with(['student', 'studentEnquiryReplies'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('content')
                    ->label('Enquiry')
                    ->limit(50)
                    ->searchable(),

                IconColumn::make('has_replies')
                    ->label('Replied')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Submitted'),
            ])
            ->paginated(false);
    }
}
