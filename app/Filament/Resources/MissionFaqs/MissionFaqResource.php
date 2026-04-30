<?php

namespace App\Filament\Resources\MissionFaqs;

use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\MissionFaqs\Pages\CreateMissionFaq;
use App\Filament\Resources\MissionFaqs\Pages\EditMissionFaq;
use App\Filament\Resources\MissionFaqs\Pages\ListMissionFaqs;
use App\Filament\Resources\MissionFaqs\Pages\ViewMissionFaq;
use App\Models\MissionFaq;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MissionFaqResource extends Resource
{
    protected static ?string $model = MissionFaq::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Mission FAQ';

    protected static ?string $pluralModelLabel = 'Mission FAQs';

    protected static ?string $navigationTooltip = 'Manage frequently asked questions about missions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->description('Organize this FAQ by selecting an appropriate category. Categories help users find answers quickly.')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'mission_faq_category_id',
                            label: 'FAQ Category',
                            relationship: 'missionFaqCategory',
                            titleAttribute: 'name',
                            required: true,
                            searchable: true,
                            preload: true,
                            helperText: 'Choose the category that best fits this FAQ. If no suitable category exists, you may need to create one first.',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Question')
                    ->description('Write the question as users would naturally ask it. Clear, concise questions help users find what they need.')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'question',
                            label: 'Question',
                            rows: 3,
                            required: true,
                            placeholder: 'e.g., What are the requirements for mission registration? How do I sign up for a mission trip?',
                            helperText: 'Write the question exactly as a user would ask it. Use natural language and keep it clear and specific.',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Answer')
                    ->description('Provide a comprehensive, easy-to-understand answer. Use formatting to make the answer scannable.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        ContentSchema::richEditorField(
                            name: 'answer',
                            label: 'Answer',
                            required: true,
                            helperText: 'Write a complete answer that addresses the question fully. Use bullet points or numbered lists for step-by-step instructions. Keep paragraphs short for easier reading.',
                            toolbarButtons: [
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'blockquote',
                            ],
                        ),
                    ])
                    ->collapsible(),
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
                    ->icon('heroicon-o-folder')
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
