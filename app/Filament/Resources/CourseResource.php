<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Course';

    protected static ?string $pluralModelLabel = 'Courses';

    protected static ?string $navigationTooltip = 'Manage online courses and learning materials';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->description('Define the course details and overview')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Course Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive name for this course')
                            ->placeholder('e.g., Introduction to Biblical Studies'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this course')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Course Content')
                    ->description('Provide detailed information about the course')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Course Description')
                            ->required()
                            ->rows(4)
                            ->helperText('Describe what students will learn in this course')
                            ->placeholder('Enter a detailed course description...'),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('thumbnails')
                            ->label('Course Thumbnails')
                            ->helperText('Upload course thumbnails and promotional images')
                            ->visibility('private')
                            ->disk(config('filament.default_filesystem_disk'))
                            ->conversionsDisk(config('filament.default_filesystem_disk'))
                            ->collection(Course::THUMBNAILS)
                            ->maxFiles(10)
                            ->acceptedFileTypes(['image/*']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Course Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-book-open')
                    ->description(fn (Course $record): string => str($record->description)->limit(100)->toString()
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('course_modules_count')
                    ->label('Modules')
                    ->counts('courseModules')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-squares-2x2')
                    ->tooltip('Number of modules in this course'),

                Tables\Columns\TextColumn::make('lesson_members_count')
                    ->label('Students')
                    ->counts('lessonMembers')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of students enrolled'),

                Tables\Columns\TextColumn::make('course_groups_count')
                    ->label('Groups')
                    ->counts('courseGroups')
                    ->badge()
                    ->color('secondary')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of groups assigned to this course'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),

                Tables\Filters\Filter::make('with_students')
                    ->label('Courses with Students')
                    ->query(fn (Builder $query): Builder => $query->has('lessonMembers')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('with_modules')
                    ->label('Courses with Modules')
                    ->query(fn (Builder $query): Builder => $query->has('courseModules')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('empty_courses')
                    ->label('Empty Courses')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('courseModules')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view course'))
                    ->tooltip('View course details'),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit course'))
                    ->tooltip('Edit this course'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Course $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Course $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Course $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Course $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip('Toggle course status')
                    ->visible(fn () => userCan('edit course')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete course')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete course')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete course')),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit course')),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit course')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CourseModulesRelationManager::class,
            RelationManagers\LessonMembersRelationManager::class,
            RelationManagers\CourseGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny course');
    }
}
