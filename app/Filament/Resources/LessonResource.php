<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Filament\Resources\LessonResource\Pages;
use App\Filament\Resources\LessonResource\RelationManagers;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;



class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'E-Learning';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\SpatieMediaLibraryFileUpload::make('thumbnails')
                    ->visibility('private')
                    ->disk(config('media-library.disk_name'))
                    ->conversionsDisk(config('media-library.disk_name'))
                    ->collection(Lesson::THUMBNAILS)
                    ->label('Thumbnail')
                    ->maxFiles(10)
                    ->acceptedFileTypes(['image/*'])
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options(PRFLessonType::getOptions())
                    ->live()
                    ->afterStateUpdated(fn (Forms\Components\Select $component) => $component
                        ->getContainer()
                        ->getComponent('dynamicTypeFields')
                        ->getChildComponentContainer()
                        ->fill()),
                Forms\Components\Select::make('is_active')
                    ->required()
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->hiddenOn('create'),
                Forms\Components\Grid::make(2)
                    ->schema(
                        fn (Get $get): array => match ($get('type')) {
                            (string) PRFLessonType::TEXT->value => [
                                Forms\Components\RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull(),
                            ],
                            (string) PRFLessonType::VIDEO->value => [
                                Forms\Components\TextInput::make('video_url')
                                    ->url()
                                    ->label('Video URL')
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('video')
                                    ->columnSpanFull()
                                    ->visibility('private')
                                    ->disk(config('media-library.disk_name'))
                                    ->conversionsDisk(config('media-library.disk_name'))
                                    ->collection(Lesson::VIDEO)
                                    ->label('Video')
                                    ->maxFiles(1)
                                    ->acceptedFileTypes(['video/*']),
                            ],
                            (string) PRFLessonType::AUDIO->value => [
                                Forms\Components\TextInput::make('audio_url')
                                    ->url()
                                    ->label('Audio URL')
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('audio')
                                    ->columnSpanFull()
                                    ->visibility('private')
                                    ->disk(config('media-library.disk_name'))
                                    ->conversionsDisk(config('media-library.disk_name'))
                                    ->collection(Lesson::AUDIO)
                                    ->label('Audio')
                                    ->maxFiles(1)
                                    ->acceptedFileTypes(['audio/*']),
                            ],
                            (string) PRFLessonType::DOCUMENT->value => [
                                Forms\Components\TextInput::make('document_url')
                                    ->url()
                                    ->label('Document URL')
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('document')
                                    ->columnSpanFull()
                                    ->visibility('private')
                                    ->disk(config('media-library.disk_name'))
                                    ->conversionsDisk(config('media-library.disk_name'))
                                    ->collection(Lesson::DOCUMENT)
                                    ->label('Document')
                                    ->maxFiles(1)
                                    ->acceptedFileTypes(['application/pdf']),
                            ],
                            default => [],
                        }
                    )
                    ->key('dynamicTypeFields'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => PRFLessonType::fromValue($record->type)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view lesson')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit lesson')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete lesson')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
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
