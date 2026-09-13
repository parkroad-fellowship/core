<?php

namespace App\Filament\Resources\Pledges\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Lets the Treasurer record follow-through installments straight on a pledge.
 * The PledgeInstallmentObserver advances the pledge's due date automatically.
 */
class PledgeInstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $recordTitleAttribute = 'fulfilled_on';

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $title = 'Follow-through';

    protected static ?string $modelLabel = 'Installment';

    protected static ?string $pluralModelLabel = 'Installments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Record Follow-through')
                ->columnSpanFull()
                ->description('Record an installment received against this pledge')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    TextInput::make('amount')->label('Amount received')->numeric()->required()->columnSpanFull(),
                    DatePicker::make('fulfilled_on')->label('Date received')->columnSpanFull(),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('amount')->label('Amount')->sortable(),
            TextColumn::make('fulfilled_on')->label('Date')->sortable(),
            TextColumn::make('method')->label('Method')->badge(),
            TextColumn::make('notes')->label('Notes')->limit(60),
            TextColumn::make('created_at')->label('Recorded At')->dateTime('M j, Y g:i A')->sortable(),
        ])->filters([
            TrashedFilter::make()->label('Deleted')->placeholder('All'),
        ])->toolbarActions([
            CreateAction::make(),
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]),
        ])->defaultSort('fulfilled_on', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
