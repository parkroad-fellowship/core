<?php

namespace App\Filament\Resources\LedgerCategories;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Clusters\MasterDataCluster;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Resources\LedgerCategories\Pages\CreateLedgerCategory;
use App\Filament\Resources\LedgerCategories\Pages\EditLedgerCategory;
use App\Filament\Resources\LedgerCategories\Pages\ListLedgerCategories;
use App\Models\LedgerCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LedgerCategoryResource extends Resource
{
    protected static ?string $model = LedgerCategory::class;

    protected static ?string $cluster = MasterDataCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Ledger Category';

    protected static ?string $pluralModelLabel = 'Ledger Categories';

    protected static ?string $navigationTooltip = 'Chart of accounts: what fellowship money is for';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category Details')
                ->columnSpanFull()
                ->description('Define a line of the chart of accounts for receipting and payments')
                ->icon('heroicon-o-tag')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            ContentSchema::nameField(
                                name: 'name',
                                label: 'Category Name',
                                placeholder: 'e.g., Mission Contribution, Prayer Desk Expenses',
                                helperText: 'Coded categories are posted to automatically; rename them but never delete them',
                            )
                                ->unique(ignoreRecord: true)
                                ->prefixIcon('heroicon-o-tag'),

                            TextInput::make('code')
                                ->label('Code')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->placeholder('e.g., income.mission_contribution')
                                ->helperText(
                                    'Set by the system for categories the app posts to; leave blank for your own categories',
                                )
                                ->prefixIcon('heroicon-o-hashtag')
                                ->disabled(
                                    fn(?LedgerCategory $record): bool => $record?->exists && $record->code !== null,
                                ),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('kind')
                                ->label('Kind')
                                ->options(PRFLedgerCategoryKind::getOptions())
                                ->required()
                                ->native(false)
                                ->prefixIcon('heroicon-o-squares-2x2')
                                ->disabled(
                                    fn(?LedgerCategory $record): bool => (
                                        $record?->code !== null
                                        || (bool) $record?->ledgerEntries()->exists()
                                    ),
                                )
                                ->helperText(fn(?LedgerCategory $record): string => match (true) {
                                    $record?->code !== null
                                        => 'The app posts to this category automatically, so its kind is fixed.',
                                    (bool) $record?->ledgerEntries()->exists()
                                        => 'Fixed once the category has cashbook lines, so past statements don’t change.',
                                    default => 'Only income counts as income; refunds reduce their desk’s expense line.',
                                }),

                            Select::make('responsible_desk')
                                ->label('Responsible Desk')
                                ->options(PRFResponsibleDesk::getOptions())
                                ->native(false)
                                ->placeholder('No desk')
                                ->prefixIcon('heroicon-o-users')
                                ->disabled(fn(?LedgerCategory $record): bool => $record?->code !== null)
                                ->helperText('Which desk this category belongs to on the statements'),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('statement_line')
                                ->label('Statement Line')
                                ->maxLength(255)
                                ->placeholder('Defaults to the category name')
                                ->helperText('The line this category rolls up to on the income statement'),

                            TextInput::make('sort')
                                ->label('Sort Order')
                                ->numeric()
                                ->default(0)
                                ->helperText('Lower numbers appear first on the statements'),
                        ]),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive categories are hidden when receipting or recording payments'),
                ])
                ->collapsible()
                ->persistCollapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-tag')
                    ->wrap()
                    ->tooltip('What the money was for'),

                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->copyable()
                    ->toggleable()
                    ->placeholder('Custom')
                    ->tooltip('Categories with a code are posted to automatically and can never be deleted'),

                TextColumn::make('kind')
                    ->label('Kind')
                    ->badge()
                    ->formatStateUsing(fn(LedgerCategory $record): string => $record->kind?->getLabel() ?? '—')
                    ->color(fn(LedgerCategory $record): string => $record->kind?->getColor() ?? 'gray')
                    ->sortable()
                    ->tooltip('How lines of this category count on the statements'),

                TextColumn::make('responsible_desk')
                    ->label('Desk')
                    ->formatStateUsing(
                        fn(LedgerCategory $record): string => $record->responsible_desk?->getLabel() ?? '—',
                    )
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->tooltip('The desk this category belongs to'),

                TextColumn::make('statement_line')
                    ->label('Statement Line')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Same as name')
                    ->tooltip('The income statement line this category rolls up to'),

                TextColumn::make('ledger_entries_count')
                    ->label('Lines')
                    ->counts('ledgerEntries')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-book-open')
                    ->tooltip('Cashbook lines posted to this category'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn(LedgerCategory $record): string => $record->is_active
                        ? 'Active: available on money forms'
                        : 'Inactive: hidden from money forms'),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()->native(false)->label('Show Deleted')->placeholder('Active categories only'),

                SelectFilter::make('kind')
                    ->label('Filter by Kind')
                    ->options(PRFLedgerCategoryKind::getOptions())
                    ->native(false)
                    ->placeholder('All kinds'),

                SelectFilter::make('responsible_desk')
                    ->label('Filter by Desk')
                    ->options(PRFResponsibleDesk::getOptions())
                    ->native(false)
                    ->placeholder('All desks'),

                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All categories')
                    ->trueLabel('Active categories')
                    ->falseLabel('Inactive categories'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn(): bool => userCan(LedgerCategory::permission('edit')))
                    ->tooltip('Rename or reclassify this category'),

                DeleteAction::make()->visible(
                    fn(LedgerCategory $record): bool => (
                        $record->code === null
                        && userCan(LedgerCategory::permission('delete'))
                    ),
                ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Per-record checks keep the app's own (coded) categories safe from bulk deletes.
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->visible(fn(): bool => userCan(LedgerCategory::permission('delete'))),
                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords()
                        ->visible(fn(): bool => userCan(LedgerCategory::permission('delete'))),
                    RestoreBulkAction::make()->visible(fn(): bool => userCan(LedgerCategory::permission('delete'))),
                ]),
            ])
            ->defaultSort('sort')
            ->striped()
            ->searchPlaceholder('Search ledger categories...')
            ->emptyStateHeading('No ledger categories found')
            ->emptyStateDescription(
                'Seeded categories appear here automatically; add custom ones for anything the chart does not cover.',
            )
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedgerCategories::route('/'),
            'create' => CreateLedgerCategory::route('/create'),
            'edit' => EditLedgerCategory::route('/{record}/edit'),
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
        return userCan(LedgerCategory::permission('viewAny'));
    }
}
