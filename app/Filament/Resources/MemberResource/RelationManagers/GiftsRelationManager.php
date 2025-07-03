<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Enums\PRFActiveStatus;
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

class GiftsRelationManager extends RelationManager
{
    protected static string $relationship = 'gifts';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $label = 'Spiritual Gift';

    protected static ?string $pluralLabel = 'Spiritual Gifts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🎁 Spiritual Gift Information')
                    ->description('Spiritual gifts and talents identification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('🎁 Gift Name')
                                    ->helperText('Name of the spiritual gift or talent')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Teaching, Prophecy, Healing'),

                                    Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Current status of this gift')
                                    ->options(PRFActiveStatus::getOptions())
                                    ->default(PRFActiveStatus::ACTIVE)
                                    ->required()
                                    ->native(false),
                                
                            ]),

                        
                        // Forms\Components\Grid::make(2)
                        //     ->schema([
                        //         Forms\Components\Select::make('proficiency_level')
                        //             ->label('📊 Proficiency Level')
                        //             ->helperText('Current level of development in this gift')
                        //             ->options([
                        //                 'beginner' => '🌱 Beginner',
                        //                 'developing' => '📈 Developing',
                        //                 'competent' => '💪 Competent',
                        //                 'advanced' => '🌟 Advanced',
                        //                 'expert' => '👑 Expert',
                        //             ])
                        //             ->native(false)
                        //             ->default('developing'),
                        //     ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('🎁 Gift')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip('Spiritual gift name'),
            
                Tables\Columns\TextColumn::make('is_active')
                    ->badge()
                    ->label('📊 Status')
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->name)
                    ->color(fn ($state) => PRFActiveStatus::fromValue($state)->getColor())
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->tooltip('Gift status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Identified')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date gift was identified'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'ministerial' => 'Ministerial Gifts',
                        'manifestation' => 'Manifestation Gifts',
                        'motivational' => 'Motivational Gifts',
                        'service' => 'Service Gifts',
                        'leadership' => 'Leadership Gifts',
                        'creative' => 'Creative Gifts',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('proficiency_level')
                    ->label('Proficiency Level')
                    ->options([
                        'beginner' => '🌱 Beginner',
                        'developing' => '📈 Developing',
                        'competent' => '💪 Competent',
                        'advanced' => '🌟 Advanced',
                        'expert' => '👑 Expert',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value),

                Tables\Filters\Filter::make('has_description')
                    ->label('Has Description')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('description'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Spiritual gift added')
                            ->body("New gift '{$record->name}' has been identified.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\AttachAction::make()
                    ->icon('heroicon-o-link')
                    ->color(Color::Blue)
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE))
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Gift attached')
                            ->body("'{$record->name}' gift has been associated with this member.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([

                Tables\Actions\Action::make('develop_gift')
                    ->label('Develop')
                    ->icon('heroicon-o-academic-cap')
                    ->color(Color::Green)
                    ->action(function ($record) {
                        // Logic for gift development
                        Notification::make()
                            ->title('Gift development')
                            ->body("Development plan created for '{$record->name}' gift.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => in_array($record->proficiency_level, ['beginner', 'developing']))
                    ->tooltip('Create development plan'),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->visible(fn () => $this->canCreate())
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Gift updated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DetachAction::make()
                    ->color(Color::Red)
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Gift detached')
                            ->body("'{$record->name}' gift has been removed from this member.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_gifts')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE]));
                            
                            Notification::make()
                                ->title('Gifts activated')
                                ->body("{$count} gifts have been activated.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('create_development_plans')
                        ->label('Create Development Plans')
                        ->icon('heroicon-o-academic-cap')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->whereIn('proficiency_level', ['beginner', 'developing'])->count();
                            
                            Notification::make()
                                ->title('Development plans created')
                                ->body("Development plans created for {$count} gifts.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DetachBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => $this->canCreate()),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }

    protected function canCreate(): bool
    {
        return userCan('create gift');
    }
}
