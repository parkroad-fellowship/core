<?php

namespace App\Filament\Resources\Schools\RelationManagers;

use App\Filament\Forms\Schemas\SchoolSchema;
use App\Jobs\SchoolContact\CreateJob;
use App\Jobs\SchoolContact\UpdateJob;
use App\Models\School;
use App\Models\SchoolContact;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class SchoolContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'schoolContacts';

    protected static ?string $title = 'Contacts';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-phone';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Full name')
                ->placeholder('e.g. Mrs Jane Wanjiku')
                ->required()
                ->maxLength(255),
            PhoneInput::make('phone')->label('Phone number')->defaultCountry('KE')->required(),
            Select::make('contact_type_ulid')
                ->label('Their role')
                ->options(fn(): array => SchoolSchema::contactTypeOptions())
                ->default(fn(): ?string => SchoolSchema::defaultContactTypeULID())
                ->native(false)
                ->required(),
            TextInput::make('preferred_name')
                ->label('What to call them (optional)')
                ->placeholder('e.g. Madam Jane')
                ->helperText('Used when we send them messages. Leave empty to use their full name.')
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with('contactType'))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->description(fn(SchoolContact $record): ?string => $record->preferred_name !== $record->name
                        ? $record->preferred_name
                        : null),
                TextColumn::make('contactType.name')->label('Role')->badge()->color('gray'),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->url(fn(SchoolContact $record): ?string => filled($record->phone)
                        ? 'tel:' . preg_replace('/\s+/', '', (string) $record->phone)
                        : null),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add a contact')
                    ->modalHeading('Add a contact')
                    ->using(function (array $data): Model {
                        $school = $this->getOwnerRecord();
                        assert($school instanceof School);

                        $contact = CreateJob::dispatchSync([...self::typed($data), 'school_ulid' => $school->ulid]);
                        assert($contact instanceof SchoolContact);

                        return $contact;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn(array $data, SchoolContact $record): array => [
                        ...$data,
                        'contact_type_ulid' => $record->contactType?->ulid,
                    ])
                    ->using(function (SchoolContact $record, array $data): Model {
                        $contact = UpdateJob::dispatchSync(self::typed($data), $record->ulid);
                        assert($contact instanceof SchoolContact);

                        return $contact;
                    }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No contacts yet')
            ->emptyStateDescription('Add the teacher or patron who arranges visits.');
    }

    /**
     * @param  array<mixed>  $data
     * @return array<string, mixed>
     */
    private static function typed(array $data): array
    {
        return collect($data)->mapWithKeys(fn(mixed $value, int|string $key): array => [
            (string) $key => $value,
        ])->all();
    }

    protected function canCreate(): bool
    {
        return userCan(SchoolContact::permission('create'));
    }

    protected function canEdit(Model $record): bool
    {
        return userCan(SchoolContact::permission('edit'));
    }

    protected function canDelete(Model $record): bool
    {
        return userCan(SchoolContact::permission('delete'));
    }
}
