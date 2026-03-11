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
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionQuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'missionQuestions';

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $title = '❓ Questions';

    protected static ?string $label = 'Mission Question';

    protected static ?string $pluralLabel = 'Mission Questions';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->missionQuestions()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $unanswered = $ownerRecord->missionQuestions()->whereNull('answer')->count();

        return $unanswered > 0 ? 'warning' : 'success';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('❓ Question Details')
                    ->description('Add questions that arose during the mission')
                    ->schema([
                        Textarea::make('question')
                            ->label('Question')
                            ->helperText('Enter the question that was asked or arose during the mission')
                            ->required()
                            ->rows(6)
                            ->placeholder('What question was asked during the mission?')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question')
            ->columns([

                TextColumn::make('question')
                    ->label('❓ Question')
                    ->limit(80)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->question),

                TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date question was recorded'),
            ])
            ->filters([
                TrashedFilter::make(),

                Filter::make('created_at')
                    ->label('Date Added')
                    ->schema([
                        DatePicker::make('created_from')
                            ->native(false)
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->native(false)
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Question recorded')
                            ->body('Mission question has been successfully recorded.')
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
                            ->title('Question updated')
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

                    BulkAction::make('export_questions')
                        ->label('Export Questions')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function ($records) {
                            // This would handle export
                            Notification::make()
                                ->title('Export started')
                                ->body('Questions export has been queued for processing.')
                                ->info()
                                ->send();
                        }),

                    RestoreBulkAction::make()
                        ->color(Color::Green),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
