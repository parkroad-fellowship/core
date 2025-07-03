<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MissionFaqResource\Pages;
use App\Models\MissionFaq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionFaqResource extends Resource
{
    protected static ?string $model = MissionFaq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Mission FAQ';

    protected static ?string $pluralModelLabel = 'Mission FAQs';

    protected static ?string $navigationTooltip = 'Manage frequently asked questions about missions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('FAQ Category')
                    ->description('Select the category for this FAQ')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Forms\Components\Select::make('mission_faq_category_id')
                            ->label('Category')
                            ->relationship('missionFaqCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Choose the appropriate category for this FAQ'),
                    ]),

                Forms\Components\Section::make('Question')
                    ->description('Enter the frequently asked question')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        Forms\Components\Textarea::make('question')
                            ->label('Question')
                            ->required()
                            ->rows(3)
                            ->helperText('Enter the question exactly as it would be asked')
                            ->placeholder('What are the requirements for mission registration?')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Answer')
                    ->description('Provide a comprehensive answer')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\RichEditor::make('answer')
                            ->label('Answer')
                            ->required()
                            ->helperText('Provide a detailed and helpful answer to the question')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'blockquote',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('missionFaqCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-queue-list')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->limit(80)
                    ->description(fn ($record) => $record->answer ? \Illuminate\Support\Str::limit(strip_tags($record->answer), 100) : null)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Added: ' . $record->created_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: ' . $record->updated_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                
                Tables\Filters\SelectFilter::make('mission_faq_category_id')
                    ->label('Category')
                    ->relationship('missionFaqCategory', 'name')
                    ->placeholder('All Categories'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view mission faq')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission faq')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq')),
                ])->visible(fn () => userCan('delete mission faq')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissionFaqs::route('/'),
            'create' => Pages\CreateMissionFaq::route('/create'),
            'view' => Pages\ViewMissionFaq::route('/{record}'),
            'edit' => Pages\EditMissionFaq::route('/{record}/edit'),
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
        return userCan('viewAny mission faq');
    }
}
