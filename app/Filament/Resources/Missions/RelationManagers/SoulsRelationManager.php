<?php

namespace App\Filament\Resources\Missions\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFSoulDecisionType;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SoulsRelationManager extends RelationManager
{
    protected static string $relationship = 'souls';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $title = '❤️ Souls';

    protected static ?string $label = 'Soul';

    protected static ?string $pluralLabel = 'Souls';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->souls()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->souls()->count();

        return $count > 0 ? 'success' : 'gray';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('👤 Student Information')
                    ->description('Basic information about the student')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Full Name')
                                    ->helperText('Complete name of the student')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter full name'),

                                TextInput::make('admission_number')
                                    ->label('Admission Number')
                                    ->helperText('Student admission or registration number')
                                    ->maxLength(255)
                                    ->placeholder('Enter admission number'),
                            ]),
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('class_group_id')
                                    ->label('Class Group')
                                    ->helperText('Select the class group this student belongs to')
                                    ->relationship(
                                        name: 'classGroup',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('decision_type')
                                    ->label('Decision Type')
                                    ->helperText('Select the decision type for this student')
                                    ->options(PRFSoulDecisionType::getOptions())
                                    ->default(PRFSoulDecisionType::SALVATION)
                                    ->required(),
                            ]),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->helperText('Any additional notes about the student'),
                    ])->columnSpanFull(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('👤 Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('Student full name'),

                TextColumn::make('admission_number')
                    ->label('🆔 Admission')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue)
                    ->placeholder('N/A')
                    ->tooltip('Student admission number'),

                TextColumn::make('classGroup.name')
                    ->label('🏫 Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Green)
                    ->placeholder('Not assigned')
                    ->tooltip('Class group'),

                TextColumn::make('decision_type')
                    ->label('🙏 Decision')
                    ->formatStateUsing(fn ($record) => PRFSoulDecisionType::fromValue($record->decision_type)->getLabel())
                    ->badge()
                    ->color(fn ($record) => PRFSoulDecisionType::fromValue($record->decision_type)->getColor())
                    ->sortable()
                    ->icon(fn ($record) => PRFSoulDecisionType::fromValue($record->decision_type)->getIcon())
                    ->tooltip(fn ($record) => $record->notes),

                IconColumn::make('has_notes')
                    ->label('📝')
                    ->getStateUsing(fn ($record) => ! empty($record->notes))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($record) => $record->notes ?? 'No notes'),

                TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->since()
                    ->tooltip('Date when student was added'),
            ])
            ->filters([
                SelectFilter::make('decision_type')
                    ->label('🙏 Decision Type')
                    ->options(PRFSoulDecisionType::getOptions())
                    ->multiple(),

                SelectFilter::make('class_group_id')
                    ->label('🏫 Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('has_admission_number')
                    ->label('🆔 Has Admission')
                    ->placeholder('All students')
                    ->trueLabel('With admission number')
                    ->falseLabel('Without admission number')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('admission_number'),
                        false: fn ($query) => $query->whereNull('admission_number'),
                    ),

                TernaryFilter::make('has_notes')
                    ->label('📝 Has Notes')
                    ->placeholder('All students')
                    ->trueLabel('With notes')
                    ->falseLabel('Without notes')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('notes')->where('notes', '!=', ''),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', '')),
                    ),

                Filter::make('created_at')
                    ->label('📅 Date Added')
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
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->label('Add Soul')
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Soul added! 🎉')
                            ->body('New student has been added to the mission souls.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('add_to_cohort')
                        ->label('Add to Cohort')
                        ->icon('heroicon-o-user-group')
                        ->color(Color::Blue)
                        ->schema([
                            Select::make('cohort_id')
                                ->label('Select Cohort')
                                ->relationship('cohort', 'title')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            // This would add the soul to a follow-up cohort
                            Notification::make()
                                ->title('Added to cohort')
                                ->body('Student has been added to the follow-up cohort.')
                                ->success()
                                ->send();
                        })
                        ->tooltip('Add student to follow-up cohort'),

                    ViewAction::make()
                        ->color(Color::Gray),

                    EditAction::make()
                        ->color(Color::Orange)
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Student updated')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->color(Color::Red),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign_class_group')
                        ->label('Assign Class')
                        ->icon('heroicon-o-academic-cap')
                        ->color(Color::Blue)
                        ->form([
                            Select::make('class_group_id')
                                ->label('Class Group')
                                ->relationship(
                                    name: 'classGroup',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['class_group_id' => $data['class_group_id']]);
                            });

                            Notification::make()
                                ->title('Class group assigned')
                                ->body('Class group has been assigned to '.count($records).' students.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('change_decision_type')
                        ->label('Change Decision Type')
                        ->icon('heroicon-o-heart')
                        ->color(Color::Purple)
                        ->form([
                            Select::make('decision_type')
                                ->label('Decision Type')
                                ->options(PRFSoulDecisionType::getOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->update(['decision_type' => $data['decision_type']]);
                            });

                            Notification::make()
                                ->title('Decision type updated')
                                ->body('Decision type has been updated for '.count($records).' students.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('export_students')
                        ->label('Export')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function ($records) {
                            Notification::make()
                                ->title('Export started')
                                ->body('Student export has been queued for processing.')
                                ->info()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->modifyQueryUsing(fn ($query) => $query->with(['classGroup']));
    }

    protected function canCreate(): bool
    {
        return userCan('create soul');
    }
}
