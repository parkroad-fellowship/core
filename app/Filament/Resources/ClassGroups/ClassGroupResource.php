<?php

namespace App\Filament\Resources\ClassGroups;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\ClassGroups\Pages\CreateClassGroup;
use App\Filament\Resources\ClassGroups\Pages\EditClassGroup;
use App\Filament\Resources\ClassGroups\Pages\ListClassGroups;
use App\Filament\Resources\ClassGroups\Pages\ViewClassGroup;
use App\Models\ClassGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ClassGroupResource extends Resource
{
    protected static ?string $model = ClassGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Class Group';

    protected static ?string $pluralModelLabel = 'Class Groups';

    protected static ?string $navigationTooltip = 'Manage educational class groups';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Class Group Information')
                    ->description('Define class groups for different educational levels. Class groups help categorize students during missions and track souls won by grade level.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Class Group Name',
                                    placeholder: 'e.g., Form 4A, Grade 12 Science, Year 3',
                                    required: true,
                                    helperText: 'Enter a descriptive name for this class group. Use naming conventions consistent with the institution type.',
                                ),

                                StatusSchema::enumSelect(
                                    name: 'institution_type',
                                    label: 'Institution Type',
                                    enumClass: PRFInstitutionType::class,
                                    default: PRFInstitutionType::HIGH_SCHOOL->value,
                                    required: true,
                                    hiddenOnCreate: false,
                                    helperText: 'Select the type of educational institution this class group belongs to.',
                                ),
                            ]),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            required: true,
                            hiddenOnCreate: true,
                            helperText: 'Active class groups can be selected when recording souls. Inactive groups are hidden but data is preserved.',
                        ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Class Group Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user-group')
                    ->wrap(),

                TextColumn::make('institution_type')
                    ->label('Institution Type')
                    ->badge()
                    ->formatStateUsing(fn ($record) => match ($record->institution_type) {
                        PRFInstitutionType::HIGH_SCHOOL->value => 'High School',
                        PRFInstitutionType::UNIVERSITY->value => 'University',
                        PRFInstitutionType::COLLEGE->value => 'College',
                        PRFInstitutionType::PRIMARY_SCHOOL->value => 'Primary School',
                        PRFInstitutionType::COMMUNITY->value => 'Community',
                        PRFInstitutionType::JUNIOR_SECONDARY_SCHOOL->value => 'Junior Secondary',
                        default => 'Unknown'
                    })
                    ->color(fn ($record) => match ($record->institution_type) {
                        PRFInstitutionType::HIGH_SCHOOL->value => 'info',
                        PRFInstitutionType::UNIVERSITY->value => 'success',
                        PRFInstitutionType::COLLEGE->value => 'warning',
                        PRFInstitutionType::PRIMARY_SCHOOL->value => 'primary',
                        PRFInstitutionType::COMMUNITY->value => 'orange',
                        PRFInstitutionType::JUNIOR_SECONDARY_SCHOOL->value => 'blue',
                        default => 'gray'
                    })
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                TextColumn::make('souls_count')
                    ->label('Students')
                    ->counts('souls')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of students in this class group'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),

                SelectFilter::make('institution_type')
                    ->label('Institution Type')
                    ->options([
                        PRFInstitutionType::HIGH_SCHOOL->value => 'High School',
                        PRFInstitutionType::UNIVERSITY->value => 'University',
                        PRFInstitutionType::COLLEGE->value => 'College',
                        PRFInstitutionType::PRIMARY_SCHOOL->value => 'Primary School',
                        PRFInstitutionType::COMMUNITY->value => 'Community',
                        PRFInstitutionType::JUNIOR_SECONDARY_SCHOOL->value => 'Junior Secondary',
                    ])
                    ->native(false),

                Filter::make('with_students')
                    ->label('Groups with Students')
                    ->query(fn (Builder $query): Builder => $query->has('members')
                    )
                    ->toggle(),

                Filter::make('empty_groups')
                    ->label('Empty Groups')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('members')
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view class group'))
                    ->tooltip('View class group details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit class group'))
                    ->tooltip('Edit this class group'),

                Action::make('toggle_status')
                    ->label(fn (ClassGroup $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (ClassGroup $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (ClassGroup $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (ClassGroup $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip('Toggle class group status')
                    ->visible(fn () => userCan('edit class group')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete class group')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete class group')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete class group')),

                    BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit class group')),

                    BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit class group')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
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
            'index' => ListClassGroups::route('/'),
            'create' => CreateClassGroup::route('/create'),
            'view' => ViewClassGroup::route('/{record}'),
            'edit' => EditClassGroup::route('/{record}/edit'),
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
        return userCan('viewAny class group');
    }
}
