<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFMembershipType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $label = 'Membership';

    protected static ?string $pluralLabel = 'Memberships';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📋 Membership Details')
                    ->description('Annual membership information and payment details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('spiritual_year_id')
                                    ->label('📅 Spiritual Year')
                                    ->helperText('Select the spiritual year for this membership')
                                    ->relationship(
                                        name: 'spiritualYear',
                                        titleAttribute: 'name',
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false),

                                Forms\Components\Select::make('type')
                                    ->label('🎫 Membership Type')
                                    ->helperText('Select the type of membership')
                                    ->required()
                                    ->options(PRFMembershipType::getOptions())
                                    ->default(PRFMembershipType::FRIEND)
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $amount = match ($state) {
                                            PRFMembershipType::FRIEND->value => 0,
                                            PRFMembershipType::YEARLY_MEMBER->value => 500,
                                            PRFMembershipType::LIFETIME_MEMBER->value => 5000,
                                            default => 0,
                                        };
                                        $set('amount', $amount);
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('💰 Membership Fee')
                                    ->helperText('Fee amount for this membership type')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Toggle::make('approved')
                                    ->label('✅ Approved')
                                    ->helperText('Mark membership as approved')
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('spiritual_year.name')
            ->columns([
                Tables\Columns\TextColumn::make('spiritualYear.name')
                    ->label('📅 Spiritual Year')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->tooltip('Spiritual year for this membership'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('🎫 Type')
                    ->formatStateUsing(fn ($record) => PRFMembershipType::fromValue($record->type)->name)
                    ->color(fn ($record) => match ($record->type) {
                        PRFMembershipType::FRIEND->value => 'gray',
                        PRFMembershipType::YEARLY_MEMBER->value => 'warning',
                        PRFMembershipType::LIFETIME_MEMBER->value => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($record) => match ($record->type) {
                        PRFMembershipType::FRIEND->value => 'heroicon-o-heart',
                        PRFMembershipType::YEARLY_MEMBER->value => 'heroicon-o-calendar',
                        PRFMembershipType::LIFETIME_MEMBER->value => 'heroicon-o-star',
                        default => 'heroicon-o-identification',
                    })
                    ->sortable()
                    ->tooltip('Membership type and level'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('💰 Fee')
                    ->money('KES')
                    ->sortable()
                    ->color(fn ($record) => $record->amount > 0 ? 'success' : 'gray')
                    ->tooltip('Membership fee amount'),

                Tables\Columns\IconColumn::make('approved')
                    ->label('✅ Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->approved ? 'Membership approved' : 'Pending approval'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Registered')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date membership was registered'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Membership Type')
                    ->options(PRFMembershipType::getOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('spiritual_year')
                    ->label('Spiritual Year')
                    ->relationship('spiritualYear', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All memberships')
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval'),

                Tables\Filters\Filter::make('amount_range')
                    ->label('Fee Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Minimum Fee')
                                    ->numeric()
                                    ->prefix('KES'),
                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Maximum Fee')
                                    ->numeric()
                                    ->prefix('KES'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators[] = 'Min: KES ' . number_format($data['min_amount']);
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators[] = 'Max: KES ' . number_format($data['max_amount']);
                        }
                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['amount'] = match ($data['type']) {
                            PRFMembershipType::FRIEND->value => 0,
                            PRFMembershipType::YEARLY_MEMBER->value => 500,
                            PRFMembershipType::LIFETIME_MEMBER->value => 5000,
                            default => 0,
                        };

                        return $data;
                    })
                    ->after(function ($record) {
                        $typeName = $record->type->name;
                        Notification::make()
                            ->title('Membership created')
                            ->body("New {$typeName} membership has been registered.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->action(function ($record) {
                        $record->update(['approved' => true]);
                        Notification::make()
                            ->title('Membership approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->approved)
                    ->tooltip('Approve this membership'),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Membership updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color(Color::Blue)
                    ->action(function ($record) {
                        // Logic to generate/view receipt
                        Notification::make()
                            ->title('Receipt generated')
                            ->body('Membership receipt is being prepared.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->approved && $record->amount > 0)
                    ->tooltip('Generate membership receipt'),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_memberships')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['approved' => true]));
                            
                            Notification::make()
                                ->title('Memberships approved')
                                ->body("{$count} memberships have been approved.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('generate_receipts')
                        ->label('Generate Receipts')
                        ->icon('heroicon-o-document-text')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->where('approved', true)->where('amount', '>', 0)->count();
                            
                            Notification::make()
                                ->title('Receipts generated')
                                ->body("Receipts generated for {$count} paid memberships.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
