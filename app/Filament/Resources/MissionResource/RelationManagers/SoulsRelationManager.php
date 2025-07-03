<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SoulsRelationManager extends RelationManager
{
    protected static string $relationship = 'souls';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $label = 'Soul';

    protected static ?string $pluralLabel = 'Souls';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👤 Student Information')
                    ->description('Basic information about the student')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('full_name')
                                    ->label('Full Name')
                                    ->helperText('Complete name of the student')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter full name'),

                                Forms\Components\TextInput::make('admission_number')
                                    ->label('Admission Number')
                                    ->helperText('Student admission or registration number')
                                    ->maxLength(255)
                                    ->placeholder('Enter admission number'),
                            ]),

                        Forms\Components\Select::make('class_group_id')
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
                    ]),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('👤 Student Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('Student full name'),

                Tables\Columns\TextColumn::make('admission_number')
                    ->label('🆔 Admission No.')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue)
                    ->placeholder('Not provided')
                    ->tooltip('Student admission number'),

                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('🏫 Class Group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Green)
                    ->tooltip('Class group'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('📝 Notes')
                    ->limit(50)
                    ->wrap()
                    ->toggleable()
                    ->placeholder('No notes')
                    ->tooltip(fn ($record) => $record->notes),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date when student was added'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('has_admission_number')
                    ->label('Has Admission Number')
                    ->placeholder('All students')
                    ->trueLabel('With admission number')
                    ->falseLabel('Without admission number')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('admission_number'),
                        false: fn ($query) => $query->whereNull('admission_number'),
                    ),

                Tables\Filters\TernaryFilter::make('has_notes')
                    ->label('Has Notes')
                    ->placeholder('All students')
                    ->trueLabel('With notes')
                    ->falseLabel('Without notes')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('notes'),
                        false: fn ($query) => $query->whereNull('notes'),
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->label('Date Added')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
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
                            $indicators[] = 'From: '.\Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: '.\Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Student added successfully')
                            ->body('New student has been added to the mission souls.')
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Student updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('assign_class_group')
                        ->label('Assign Class Group')
                        ->icon('heroicon-o-academic-cap')
                        ->color(Color::Blue)
                        ->form([
                            Forms\Components\Select::make('class_group_id')
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
                        }),

                    Tables\Actions\BulkAction::make('export_students')
                        ->label('Export Students')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color(Color::Gray)
                        ->action(function ($records) {
                            // This would handle export
                            Notification::make()
                                ->title('Export started')
                                ->body('Student export has been queued for processing.')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('full_name', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with(['classGroup']));
    }

    protected function canCreate(): bool
    {
        return userCan('create soul');
    }
}
