<?php

namespace App\Filament\Resources\Letters;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Letters\Pages\CreateLetter;
use App\Filament\Resources\Letters\Pages\EditLetter;
use App\Filament\Resources\Letters\Pages\ListLetters;
use App\Filament\Resources\Letters\Pages\ViewLetter;
use App\Models\Letter;
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

class LetterResource extends Resource
{
    protected static ?string $model = Letter::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Letter';

    protected static ?string $pluralModelLabel = 'Letters';

    protected static ?string $navigationTooltip = 'Manage follow-up letters and communications';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Letter Information')
                    ->description('Enter the basic details for this letter template')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        ContentSchema::titleField(
                            name: 'title',
                            label: 'Letter Title',
                            placeholder: 'e.g., Welcome Letter, Follow-up After First Visit',
                            helperText: 'Give this letter template a descriptive name so you can easily find it later',
                        ),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            required: true,
                            hiddenOnCreate: true,
                            helperText: 'Active letters can be used for follow-up communications. Inactive letters are archived.',
                        ),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Letter Overview')
                    ->description('Briefly describe the purpose of this letter')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Purpose and Audience',
                            rows: 3,
                            required: true,
                            placeholder: 'e.g., This letter is sent to first-time visitors to welcome them to our church family...',
                            helperText: 'Describe when this letter should be used and who the intended recipients are',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Letter Content')
                    ->description('Write the full content of your letter below')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        ContentSchema::richEditorField(
                            name: 'content',
                            label: 'Letter Body',
                            required: true,
                            helperText: 'Write your letter content here. Use the formatting tools to add headings, lists, and emphasis.',
                            toolbarButtons: [
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'h2',
                                'h3',
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
                TextColumn::make('title')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('is_active')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->label('Status'),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn () => userCan('view letter')),
                EditAction::make()->visible(fn () => userCan('edit letter')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete letter')),
            ]);
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
            'index' => ListLetters::route('/'),
            'create' => CreateLetter::route('/create'),
            'view' => ViewLetter::route('/{record}'),
            'edit' => EditLetter::route('/{record}/edit'),
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
        return userCan('viewAny letter');
    }
}
