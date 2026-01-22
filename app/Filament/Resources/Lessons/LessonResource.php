<?php

namespace App\Filament\Resources\Lessons;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Resources\Lessons\Pages\ViewLesson;
use App\Filament\Resources\Lessons\RelationManagers\LessonMembersRelationManager;
use App\Models\Lesson;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Lesson';

    protected static ?string $pluralModelLabel = 'Lessons';

    protected static ?string $navigationTooltip = 'Manage educational lessons and content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Define the lesson title and description')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label('Lesson Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive title for this lesson')
                            ->placeholder('e.g., Introduction to Prayer, Bible Study Basics'),

                        Textarea::make('description')
                            ->label('Lesson Description')
                            ->required()
                            ->rows(3)
                            ->helperText('Provide a brief overview of what students will learn')
                            ->placeholder('Describe the lesson content and objectives...'),
                    ])
                    ->columns(2),

                Section::make('Lesson Configuration')
                    ->description('Set the lesson type and status')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('type')
                            ->label('Lesson Type')
                            ->required()
                            ->options(PRFLessonType::getOptions())
                            ->live()
                            ->helperText('Choose the format of this lesson'),

                        Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this lesson')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Section::make('Media Files')
                    ->description('Upload thumbnail images for this lesson')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('thumbnails')

                            ->disk(config('filament.default_filesystem_disk'))
                            ->conversionsDisk(config('filament.default_filesystem_disk'))
                            ->collection(Lesson::THUMBNAILS)
                            ->label('Thumbnail Images')
                            ->maxFiles(10)
                            ->acceptedFileTypes(['image/*'])
                            ->helperText('Upload images to represent this lesson (up to 10 files)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Lesson Content')
                    ->description('Add the specific content based on lesson type')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        // Text Content Fields
                        RichEditor::make('content')
                            ->label('Lesson Content')
                            ->required()
                            ->helperText('Write the complete lesson content')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'h2',
                                'h3',
                                'blockquote',
                            ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::TEXT->value),

                        // Video Content Fields
                        TextInput::make('video_url')
                            ->url()
                            ->label('Video URL')
                            ->helperText('Link to external video (YouTube, Vimeo, etc.)')
                            ->placeholder('https://www.youtube.com/watch?v=...')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::VIDEO->value),

                        SpatieMediaLibraryFileUpload::make('video')
                            ->columnSpanFull()

                            ->disk(config('filament.default_filesystem_disk'))
                            ->conversionsDisk(config('filament.default_filesystem_disk'))
                            ->collection(Lesson::VIDEO)
                            ->label('Upload Video File')
                            ->maxFiles(1)
                            ->acceptedFileTypes(['video/*'])
                            ->helperText('Upload a video file (alternative to video URL)')
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::VIDEO->value),

                        // Audio Content Fields
                        TextInput::make('audio_url')
                            ->url()
                            ->label('Audio URL')
                            ->helperText('Link to external audio file')
                            ->placeholder('https://example.com/audio.mp3')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::AUDIO->value),

                        SpatieMediaLibraryFileUpload::make('audio')
                            ->columnSpanFull()

                            ->disk(config('filament.default_filesystem_disk'))
                            ->conversionsDisk(config('filament.default_filesystem_disk'))
                            ->collection(Lesson::AUDIO)
                            ->label('Upload Audio File')
                            ->maxFiles(1)
                            ->acceptedFileTypes(['audio/*'])
                            ->helperText('Upload an audio file (alternative to audio URL)')
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::AUDIO->value),

                        // Document Content Fields
                        TextInput::make('document_url')
                            ->url()
                            ->label('Document URL')
                            ->helperText('Link to external document')
                            ->placeholder('https://example.com/document.pdf')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::DOCUMENT->value),

                        SpatieMediaLibraryFileUpload::make('document')
                            ->columnSpanFull()

                            ->disk(config('filament.default_filesystem_disk'))
                            ->conversionsDisk(config('filament.default_filesystem_disk'))
                            ->collection(Lesson::DOCUMENT)
                            ->label('Upload Document')
                            ->maxFiles(1)
                            ->acceptedFileTypes(['application/pdf'])
                            ->helperText('Upload a PDF document (alternative to document URL)')
                            ->visible(fn (Get $get): bool => $get('type') == PRFLessonType::DOCUMENT->value),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Lesson Title')
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 80) : null)
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFLessonType::fromValue($state)->getLabel())
                    ->color(fn ($state) => match ($state) {
                        PRFLessonType::TEXT->value => 'gray',
                        PRFLessonType::VIDEO->value => 'info',
                        PRFLessonType::AUDIO->value => 'warning',
                        PRFLessonType::DOCUMENT->value => 'success',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match ($state) {
                        PRFLessonType::TEXT->value => 'heroicon-o-document-text',
                        PRFLessonType::VIDEO->value => 'heroicon-o-video-camera',
                        PRFLessonType::AUDIO->value => 'heroicon-o-musical-note',
                        PRFLessonType::DOCUMENT->value => 'heroicon-o-document',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->sortable(),

                TextColumn::make('lesson_members_count')
                    ->label('Students')
                    ->counts('lessonMembers')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of students enrolled'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->placeholder('All Statuses'),

                SelectFilter::make('type')
                    ->label('Lesson Type')
                    ->options(PRFLessonType::getOptions())
                    ->placeholder('All Types'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view lesson')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit lesson')),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit lesson')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete lesson')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete lesson')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete lesson')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit lesson')),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit lesson')),
                ])->visible(fn () => userCan('delete lesson')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LessonMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessons::route('/'),
            'create' => CreateLesson::route('/create'),
            'view' => ViewLesson::route('/{record}'),
            'edit' => EditLesson::route('/{record}/edit'),
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
        return userCan('viewAny lesson');
    }
}
