<?php

namespace App\Filament\Resources\StudentEnquiries;

use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\StudentEnquiries\Pages\CreateStudentEnquiry;
use App\Filament\Resources\StudentEnquiries\Pages\EditStudentEnquiry;
use App\Filament\Resources\StudentEnquiries\Pages\ListStudentEnquiries;
use App\Filament\Resources\StudentEnquiries\Pages\ViewStudentEnquiry;
use App\Filament\Resources\StudentEnquiries\RelationManagers\StudentEnquiryRepliesRelationManager;
use App\Models\StudentEnquiry;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StudentEnquiryResource extends Resource
{
    protected static ?string $model = StudentEnquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Student Enquiry';

    protected static ?string $pluralModelLabel = 'Student Enquiries';

    protected static ?string $navigationLabel = 'Student Enquiries';

    protected static ?string $navigationTooltip = 'Manage student questions and inquiries';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'info' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' student enquir'.($count !== 1 ? 'ies' : 'y');
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record?->student?->name.' - '.str($record->content)->limit(50);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Student' => $record?->student?->name,
            'Asked On' => $record->created_at->format('M j, Y g:i A'),
            'Content' => str($record->content)->limit(100),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['content', 'student.name'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student Information')
                    ->description('Identify which student submitted this enquiry. Student enquiries help track questions that arise during or after missions.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'student_id',
                            label: 'Student',
                            relationship: 'student',
                            titleAttribute: 'name',
                            required: true,
                            helperText: 'Select the student who asked this question. Start typing to search by name.',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Enquiry Content')
                    ->description('Record the student\'s question or enquiry in full detail. Clear documentation helps provide accurate and helpful responses.')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'content',
                            label: 'Question/Enquiry',
                            rows: 5,
                            required: true,
                            placeholder: 'e.g., How can I join a Bible study group in my area?',
                            helperText: 'Record the complete question or enquiry from the student. Include all relevant details to ensure an accurate response can be provided.',
                        ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn ($record) => 'Student: '.$record?->student?->name),

                TextColumn::make('content')
                    ->label('Question/Enquiry')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->content)
                    ->searchable(),

                TextColumn::make('student_enquiry_replies_count')
                    ->label('Replies')
                    ->counts('studentEnquiryReplies')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->tooltip('Number of replies to this enquiry'),

                TextColumn::make('created_at')
                    ->label('Asked On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->tooltip(fn ($record) => 'Asked: '.$record->created_at->format('F j, Y \a\t g:i A')),

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

                SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Students'),

                Filter::make('has_replies')
                    ->label('Has Replies')
                    ->query(fn (Builder $query): Builder => $query->has('studentEnquiryReplies'))
                    ->toggle(),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->native(false)
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view student enquiry')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit student enquiry')),
                    Action::make('reply')
                        ->label('Quick Reply')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->schema([
                            Textarea::make('reply_content')
                                ->label('Reply')
                                ->required()
                                ->rows(4)
                                ->placeholder('Enter your reply to address the student\'s question...')
                                ->helperText('Provide a clear, helpful response to the student\'s enquiry.'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->studentEnquiryReplies()->create([
                                'content' => $data['reply_content'],
                                'user_id' => Auth::id(),
                            ]);
                        })
                        ->successNotificationTitle('Reply added successfully')
                        ->visible(fn () => userCan('create student enquiry reply')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    BulkAction::make('mark_answered')
                        ->label('Mark as Answered')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->studentEnquiryReplies()->create([
                                    'content' => 'Marked as answered by admin.',
                                    'user_id' => Auth::id(),
                                ]);
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('create student enquiry reply')),
                ])->visible(fn () => userCan('delete student enquiry')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            StudentEnquiryRepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentEnquiries::route('/'),
            'create' => CreateStudentEnquiry::route('/create'),
            'view' => ViewStudentEnquiry::route('/{record}'),
            'edit' => EditStudentEnquiry::route('/{record}/edit'),
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
        return userCan('viewAny student enquiry');
    }
}
