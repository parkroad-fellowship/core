<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\MissionFaqCategoryResource\Pages;
use App\Models\MissionFaqCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionFaqCategoryResource extends Resource
{
    protected static ?string $model = MissionFaqCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'FAQ Category';

    protected static ?string $pluralModelLabel = 'FAQ Categories';

    protected static ?string $navigationTooltip = 'Manage mission FAQ categories and organization';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->description('Define the FAQ category details')
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Category Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive name for this FAQ category')
                            ->placeholder('e.g., Mission Registration, Mission Requirements'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this category')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Category Name')
                    ->icon('heroicon-o-queue-list')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mission_faqs_count')
                    ->label('FAQs Count')
                    ->counts('missionFaqs')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-question-mark-circle')
                    ->tooltip('Number of FAQs in this category'),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

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

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->placeholder('All Statuses'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view mission faq category')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission faq category')),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit mission faq category')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq category')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq category')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete mission faq category')),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit mission faq category')),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit mission faq category')),
                ])->visible(fn () => userCan('delete mission faq category')),
            ])
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListMissionFaqCategories::route('/'),
            'create' => Pages\CreateMissionFaqCategory::route('/create'),
            'view' => Pages\ViewMissionFaqCategory::route('/{record}'),
            'edit' => Pages\EditMissionFaqCategory::route('/{record}/edit'),
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
        return userCan('viewAny mission faq category');
    }
}
