<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\LetterResource\Pages;
use App\Models\Letter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LetterResource extends Resource
{
    protected static ?string $model = Letter::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Letter';

    protected static ?string $pluralModelLabel = 'Letters';

    protected static ?string $navigationTooltip = 'Manage follow-up letters and communications';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Letter Information')
                    ->description('Define the letter details and purpose')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Letter Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive title for this letter')
                            ->placeholder('e.g., Welcome Letter, Follow-up Communication'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this letter')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Letter Overview')
                    ->description('Provide a brief description of the letter')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Letter Description')
                            ->required()
                            ->rows(3)
                            ->helperText('Briefly describe the purpose and audience of this letter')
                            ->placeholder('Enter a description of what this letter is about...'),
                    ]),

                Forms\Components\Section::make('Letter Content')
                    ->description('Write the complete letter content')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Letter Content')
                            ->required()
                            ->helperText('Write the complete content of the letter')
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
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
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
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view letter')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit letter')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListLetters::route('/'),
            'create' => Pages\CreateLetter::route('/create'),
            'view' => Pages\ViewLetter::route('/{record}'),
            'edit' => Pages\EditLetter::route('/{record}/edit'),
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
