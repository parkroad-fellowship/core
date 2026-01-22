<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\MissionFaqResource\Pages\ListMissionFaqs;
use App\Filament\Resources\MissionFaqResource\Pages\CreateMissionFaq;
use App\Filament\Resources\MissionFaqResource\Pages\ViewMissionFaq;
use App\Filament\Resources\MissionFaqResource\Pages\EditMissionFaq;
use App\Filament\Resources\MissionFaqResource\Pages;
use App\Models\MissionFaq;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionFaqResource extends Resource
{
    protected static ?string $model = MissionFaq::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Mission FAQ';

    protected static ?string $pluralModelLabel = 'Mission FAQs';

    protected static ?string $navigationTooltip = 'Manage frequently asked questions about missions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('FAQ Category')
                    ->description('Select the category for this FAQ')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Select::make('mission_faq_category_id')
                            ->label('Category')
                            ->relationship('missionFaqCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Choose the appropriate category for this FAQ'),
                    ]),

                Section::make('Question')
                    ->description('Enter the frequently asked question')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        Textarea::make('question')
                            ->label('Question')
                            ->required()
                            ->rows(3)
                            ->helperText('Enter the question exactly as it would be asked')
                            ->placeholder('What are the requirements for mission registration?')
                            ->columnSpanFull(),
                    ]),

                Section::make('Answer')
                    ->description('Provide a comprehensive answer')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        RichEditor::make('answer')
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
                TextColumn::make('missionFaqCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-queue-list')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('question')
                    ->label('Question')
                    ->limit(80)
                    ->description(fn ($record) => $record->answer ? Str::limit(strip_tags($record->answer), 100) : null)
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Added: '.$record->created_at->format('F j, Y \a\t g:i A')),

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

                SelectFilter::make('mission_faq_category_id')
                    ->label('Category')
                    ->relationship('missionFaqCategory', 'name')
                    ->placeholder('All Categories'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view mission faq')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission faq')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq')),
                    RestoreBulkAction::make()
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
            'index' => ListMissionFaqs::route('/'),
            'create' => CreateMissionFaq::route('/create'),
            'view' => ViewMissionFaq::route('/{record}'),
            'edit' => EditMissionFaq::route('/{record}/edit'),
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
