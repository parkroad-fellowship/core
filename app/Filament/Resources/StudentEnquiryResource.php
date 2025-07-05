<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentEnquiryResource\Pages;
use App\Filament\Resources\StudentEnquiryResource\RelationManagers;
use App\Models\StudentEnquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StudentEnquiryResource extends Resource
{
    protected static ?string $model = StudentEnquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

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

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->student->name.' - '.str($record->content)->limit(50);
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Student' => $record->student->name,
            'Asked On' => $record->created_at->format('M j, Y g:i A'),
            'Content' => str($record->content)->limit(100),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['content', 'student.name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Enquiry Information')
                    ->description('Record student questions and inquiries')
                    ->icon('heroicon-o-question-mark-circle')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Student')
                            ->required()
                            ->relationship('student', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('👨‍🎓 Select the student asking the question')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('content')
                            ->label('Question/Enquiry')
                            ->required()
                            ->rows(5)
                            ->placeholder('Enter the student\'s question or enquiry...')
                            ->helperText('❓ Record the complete question or inquiry from the student')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn ($record) => 'Student: '.$record->student->name),

                Tables\Columns\TextColumn::make('content')
                    ->label('Question/Enquiry')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->content)
                    ->searchable(),

                Tables\Columns\TextColumn::make('student_enquiry_replies_count')
                    ->label('Replies')
                    ->counts('studentEnquiryReplies')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->tooltip('Number of replies to this enquiry'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Asked On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->tooltip(fn ($record) => 'Asked: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

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

                Tables\Filters\SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Students'),

                Tables\Filters\Filter::make('has_replies')
                    ->label('Has Replies')
                    ->query(fn (Builder $query): Builder => $query->has('studentEnquiryReplies'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view student enquiry')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit student enquiry')),
                    Tables\Actions\Action::make('reply')
                        ->label('Quick Reply')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('reply_content')
                                ->label('Reply')
                                ->required()
                                ->rows(4)
                                ->placeholder('Enter your reply...'),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete student enquiry')),
                    Tables\Actions\BulkAction::make('mark_answered')
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
            RelationManagers\StudentEnquiryRepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentEnquiries::route('/'),
            'create' => Pages\CreateStudentEnquiry::route('/create'),
            'view' => Pages\ViewStudentEnquiry::route('/{record}'),
            'edit' => Pages\EditStudentEnquiry::route('/{record}/edit'),
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
