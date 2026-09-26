<?php

namespace App\Filament\Resources\Pledges;

use App\Enums\PRFPledgeFrequency;
use App\Enums\PRFPledgeStatus;
use App\Filament\Exports\PledgeExporter;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Resources\Pledges\Pages\CreatePledge;
use App\Filament\Resources\Pledges\Pages\EditPledge;
use App\Filament\Resources\Pledges\Pages\ListPledges;
use App\Filament\Resources\Pledges\Pages\ViewPledge;
use App\Filament\Resources\Pledges\RelationManagers\PledgeInstallmentsRelationManager;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Spatie\LaravelPdf\Support\pdf;

class PledgeResource extends Resource
{
    protected static ?string $model = Pledge::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Pledge';

    protected static ?string $pluralModelLabel = 'Pledges';

    protected static ?string $navigationTooltip = 'Track member giving commitments and follow-through';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', PRFPledgeStatus::ACTIVE->value)->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pledger')
                ->columnSpanFull()
                ->description('Contact details of the person making the commitment')
                ->icon('heroicon-o-user')
                ->schema([
                    Select::make('member_id')
                        ->label('Linked Member')
                        ->relationship('member', 'full_name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('None (unregistered giver)'),

                    TextInput::make('name')->label('Name')->required()->columnSpanFull(),
                    ContactSchema::emailField(name: 'email'),
                    ContactSchema::phoneField(name: 'phone'),
                ])
                ->columns(2),

            Section::make('Commitment')
                ->columnSpanFull()
                ->description('What was pledged and how often')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    TextInput::make('amount')->label('Amount')->numeric()->required(),
                    Select::make('frequency')
                        ->label('Frequency')
                        ->options(PRFPledgeFrequency::getOptions())
                        ->required(),
                    DatePicker::make('start_date')->label('Start Date'),
                    DatePicker::make('next_due_on')->label('Next Due On'),
                    Select::make('status')->label('Status')->options(PRFPledgeStatus::getOptions()),
                ])
                ->columns(2),
        ]);
    }

    public static function infosList(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label('Name')
                ->icon('heroicon-m-user')
                ->color(Color::Blue)
                ->weight(FontWeight::SemiBold),
            TextEntry::make('email')->label('Email'),
            TextEntry::make('phone')->label('Phone'),
            TextEntry::make('amount')->label('Amount'),
            TextEntry::make('frequency')->label('Frequency')->badge(),
            TextEntry::make('next_due_on')->label('Next Due')->date('M j, Y')->placeholder('N/A'),
            TextEntry::make('last_fulfilled_on')->label('Last Fulfilled')->date('M j, Y')->placeholder('Never'),
            TextEntry::make('status')->label('Status')->badge(),
            TextEntry::make('created_at')->label('Pledged On')->dateTime('M j, Y g:i A'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn($record) => $record->email ?? $record->phone ?? 'No contact'),
                TextColumn::make('amount')->label('Amount')->sortable(),
                TextColumn::make('frequency')->label('Frequency')->badge(),
                TextColumn::make('next_due_on')->label('Next Due')->dateTime('M j, Y')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Pledged On')->dateTime('M j, Y g:i A')->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Deleted Records')->placeholder('All Records'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PRFPledgeStatus::getOptions())
                    ->placeholder('All Statuses'),
                SelectFilter::make('frequency')
                    ->label('Frequency')
                    ->options(PRFPledgeFrequency::getOptions())
                    ->placeholder('All Frequencies'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->color('info'),
                    EditAction::make()->color('warning'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Selected')
                        ->icon('heroicon-m-inbox-arrow-down')
                        ->exporter(PledgeExporter::class)
                        ->visible(userCan(Pledge::permission('viewAny'))),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        $pledges = Pledge::query()->orderBy('created_at', 'desc')->get();
                        $count = $pledges->count();
                        $projectedAnnual = (float) $pledges->sum(fn(Pledge $pledge) => $pledge->annualizedAmount());
                        $fulfilledThisYear = (float) PledgeInstallment::query()->whereYear(
                            'fulfilled_on',
                            now()->year,
                        )->sum('amount');
                        $filename = 'PRF-giving-commitments-' . now()->toDateString() . '.pdf';

                        // Livewire only triggers downloads for StreamedResponse /
                        // BinaryFileResponse, so render via Gotenberg first and
                        // stream the bytes (PdfBuilder itself is not downloadable).
                        $builder = pdf()
                            ->view('prf.reports.pledges-pdf', [
                                'title' => 'Giving Commitments',
                                'subtitle' => 'Member giving commitments and follow-through.',
                                'pledges' => $pledges,
                                'count' => $count,
                                'projectedAnnual' => $projectedAnnual,
                                'avgAnnual' => $count > 0 ? $projectedAnnual / $count : 0,
                                'fulfilledThisYear' => $fulfilledThisYear,
                            ])
                            ->name($filename);

                        return response()->streamDownload(fn() => print $builder->generatePdfContent(), $filename, [
                            'Content-Type' => 'application/pdf',
                        ]);
                    }),
                ExportAction::make()
                    ->label('Export Pledges')
                    ->icon('heroicon-m-inbox-arrow-down')
                    ->exporter(PledgeExporter::class)
                    ->visible(userCan(Pledge::permission('viewAny')))
                    ->modifyQueryUsing(fn(Builder $query) => $query
                        ->orderBy('created_at', 'desc')
                        ->withoutGlobalScopes([
                            SoftDeletingScope::class,
                        ])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PledgeInstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPledges::route('/'),
            'create' => CreatePledge::route('/create'),
            'view' => ViewPledge::route('/{record}'),
            'edit' => EditPledge::route('/{record}/edit'),
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
        return userCan(Pledge::permission('viewAny'));
    }
}
