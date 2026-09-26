<?php

namespace App\Filament\Forms\Schemas;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Helpers\Utils;
use App\Jobs\School\CreateJob;
use App\Jobs\School\UpdateJob;
use App\Jobs\SchoolContact\CreateJob as CreateContactJob;
use App\Models\ContactType;
use App\Models\School;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Once;
use Illuminate\Support\Str;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * School form pieces shared by the school pages, the suggested-schools inbox and the mission
 * form's "add a new school" pop-up, so a school is captured the same way everywhere.
 */
class SchoolSchema
{
    /** Where the map pin sits before anyone searches, so it can't be a school's real location. */
    public const DEFAULT_PIN = ['lat' => -1.319167, 'lng' => 36.9275];

    /** Words too common in school names to tell two schools apart. */
    private const COMMON_WORDS = [
        'the',
        'and',
        'school',
        'schools',
        'high',
        'secondary',
        'primary',
        'junior',
        'senior',
        'girls',
        'boys',
        'mixed',
        'day',
        'boarding',
        'academy',
        'college',
        'university',
        'national',
        'county',
        'sub',
        'comprehensive',
        'centre',
        'center',
    ];

    /**
     * Read through getAttribute() so the enum cast is honoured regardless of the column type.
     */
    public static function isActive(School $school): bool
    {
        return $school->getAttribute('is_active') !== PRFActiveStatus::INACTIVE;
    }

    /**
     * @return array<int, string>
     */
    public static function institutionTypeOptions(): array
    {
        /** @var array<int, string> */
        return PRFInstitutionType::getOptions();
    }

    public static function planMissionUrl(School $school): string
    {
        return MissionPlannerResource::getUrl('create') . '?school=' . $school->ulid;
    }

    public static function googleMapsUrl(School $school): ?string
    {
        if (!self::isPinned(['lat' => $school->latitude, 'lng' => $school->longitude])) {
            return null;
        }

        return "https://www.google.com/maps/search/?api=1&query={$school->latitude},{$school->longitude}";
    }

    /**
     * Whether a map value is a real pin rather than empty or the untouched default.
     */
    public static function isPinned(mixed $location): bool
    {
        if (is_string($location)) {
            $location = json_decode($location, true);
        }

        if (!is_array($location) || !is_numeric($location['lat'] ?? null) || !is_numeric($location['lng'] ?? null)) {
            return false;
        }

        $lat = (float) $location['lat'];
        $lng = (float) $location['lng'];

        if ($lat === 0.0 && $lng === 0.0) {
            return false;
        }

        return abs($lat - self::DEFAULT_PIN['lat']) > 0.00001 || abs($lng - self::DEFAULT_PIN['lng']) > 0.00001;
    }

    public static function nameField(): TextInput
    {
        return ContentSchema::nameField(
            name: 'name',
            label: 'School name',
            placeholder: 'e.g. Moi Girls High School, Eldoret',
            helperText: 'Use the full name. If a school with a similar name is already saved, we will point it out so you don’t add it twice.',
        )
            ->prefixIcon('heroicon-o-academic-cap')
            ->live(onBlur: true);
    }

    public static function institutionTypeField(): Select
    {
        return Select::make('institution_type')
            ->label('Type of school')
            ->options(self::institutionTypeOptions())
            ->default(PRFInstitutionType::HIGH_SCHOOL->value)
            ->required()
            ->native(false);
    }

    public static function studentsField(): TextInput
    {
        return TextInput::make('total_students')
            ->label('Number of students')
            ->helperText('A rough number is fine.')
            ->numeric()
            ->default(0)
            ->minValue(0)
            ->maxValue(10000)
            ->placeholder('e.g. 500');
    }

    /**
     * Search box, address and map. On a new school the pin must be moved off the default spot.
     *
     * @return list<Component>
     */
    public static function locationFields(): array
    {
        return [
            Geocomplete::make('location_search')
                ->label('Find the school on the map')
                ->helperText(
                    'Type the school’s name or area and pick it from the list. The address and map pin fill in for you.',
                )
                ->isLocation()
                ->types(['school', 'point_of_interest', 'university', 'secondary_school', 'premise'])
                ->countries(['ke'])
                ->maxLength(1024)
                ->minChars(3)
                ->placeholder('Start typing the school name…')
                ->columnSpanFull()
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set): void {
                    if (!is_array($state) || !is_numeric($state['lat'] ?? null) || !is_numeric($state['lng'] ?? null)) {
                        return;
                    }

                    $set('location', ['lat' => (float) $state['lat'], 'lng' => (float) $state['lng']]);

                    $fallbackAddress = is_string($state['formatted_address'] ?? null)
                        ? $state['formatted_address']
                        : null;
                    $set('address', Utils::buildKenyanAddress(
                        (float) $state['lat'],
                        (float) $state['lng'],
                        $fallbackAddress,
                    ));
                }),

            Map::make('location')
                ->label('Map')
                ->helperText(
                    'Drag the pin, or click on the map, to put it exactly on the school. The address updates to match.',
                )
                ->mapControls([
                    'mapTypeControl' => true,
                    'zoomControl' => true,
                    'fullscreenControl' => true,
                    'streetViewControl' => false,
                    'rotateControl' => false,
                    'scaleControl' => false,
                ])
                ->reverseGeocode(['address' => '%p %n %S, %D, %L, %A2'])
                ->clickable(true)
                ->draggable(true)
                ->geolocate(false)
                ->geolocateOnLoad(false)
                ->defaultZoom(10)
                ->defaultLocation([self::DEFAULT_PIN['lat'], self::DEFAULT_PIN['lng']])
                ->height('360px')
                ->required()
                ->rule(
                    static fn(): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
                        if (!self::isPinned($value)) {
                            $fail('Search for the school or drop the pin on it.');
                        }
                    },
                    static fn(?Model $record): bool => !$record instanceof School,
                )
                ->columnSpanFull(),

            Textarea::make('address')
                ->label('Address')
                ->helperText('Filled in from the map. You can tidy it up.')
                ->required()
                ->rows(2)
                ->maxLength(1000)
                ->placeholder('Appears here once you find the school on the map')
                ->afterStateUpdated(function (mixed $state, Set $set): void {
                    if (is_string($state)) {
                        $set('address', self::tidyAddress($state));
                    }
                })
                ->columnSpanFull(),
        ];
    }

    /**
     * The map fills the address from parts that can be missing, leaving stray commas behind.
     */
    public static function tidyAddress(string $address): string
    {
        $address = preg_replace('/[ \t]*,([ \t]*,)+/', ',', $address) ?? $address;
        $address = preg_replace('/[ \t]{2,}/', ' ', $address) ?? $address;

        return trim($address, " ,\t\n\r");
    }

    /**
     * A warning under the school name listing saved schools with a similar name, with a link to
     * each and a way to switch an inactive one back on instead of adding a copy.
     */
    public static function duplicateWarning(): Callout
    {
        $similar = static fn(Get $get, ?Model $record): Collection => self::similarSchools(
            is_string($get('name')) ? $get('name') : '',
            $record instanceof School ? $record->id : null,
        );

        return Callout::make('Is this school already saved?')
            ->warning()
            ->description(static function (Get $get, ?Model $record) use ($similar): HtmlString {
                $lines = $similar($get, $record)->map(fn(School $school): string => sprintf(
                    '<li><strong>%s</strong>%s%s</li>',
                    e($school->name),
                    filled($school->address) ? ' · ' . e(Str::limit($school->address, 60)) : '',
                    self::isActive($school) ? '' : ' · <em>inactive</em>',
                ));

                return new HtmlString(
                    'These saved schools have a similar name. If one of them is the same school, use it instead of adding it again.'
                    . '<ul class="list-disc ps-5 mt-1">'
                    . $lines->implode('')
                    . '</ul>',
                );
            })
            ->actions([
                static fn(Get $get, ?Model $record): array => $similar($get, $record)->flatMap(
                    fn(School $school): array => self::similarSchoolActions($school),
                )->all(),
            ])
            ->visible(static fn(Get $get, ?Model $record): bool => $similar($get, $record)->isNotEmpty())
            ->columnSpanFull();
    }

    /**
     * @return list<Action>
     */
    private static function similarSchoolActions(School $school): array
    {
        $actions = [
            Action::make('openSchool' . $school->ulid)
                ->label('Open ' . Str::limit($school->name, 30))
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->link()
                ->url(SchoolResource::getUrl('view', ['record' => $school]), shouldOpenInNewTab: true),
        ];

        if (!self::isActive($school)) {
            $actions[] = Action::make('reactivateSchool' . $school->ulid)
                ->label('Reactivate ' . Str::limit($school->name, 30))
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->link()
                ->action(function () use ($school): void {
                    UpdateJob::dispatchSync(['is_active' => PRFActiveStatus::ACTIVE], $school->ulid);
                    Once::flush();

                    Notification::make()
                        ->success()
                        ->title("{$school->name} is active again")
                        ->body('You can now pick it when planning a mission.')
                        ->send();
                })
                ->visible(fn(): bool => userCan(School::permission('edit')));
        }

        return $actions;
    }

    /**
     * Saved schools whose name shares a distinctive word with $name, ignoring case, punctuation and
     * common words like "High" or "School". Inactive schools are included; best matches first.
     *
     * @return Collection<int, School>
     */
    public static function similarSchools(string $name, ?int $ignoreId = null): Collection
    {
        return once(static function () use ($name, $ignoreId): Collection {
            $normalised = self::normalise($name);

            if (mb_strlen($normalised) < 3) {
                return new Collection();
            }

            $tokens = array_values(array_unique(array_filter(
                explode(' ', $normalised),
                fn(string $word): bool => mb_strlen($word) >= 3 && !in_array($word, self::COMMON_WORDS, true),
            )));

            if ($tokens === []) {
                $tokens = [$normalised];
            }

            $candidates = School::query()
                ->when($ignoreId !== null, fn(Builder $query) => $query->whereKeyNot($ignoreId))
                ->where(function (Builder $query) use ($tokens): void {
                    foreach ($tokens as $token) {
                        $query->orWhereLike('name', "%{$token}%", caseSensitive: false);
                    }
                })
                ->orderBy('name')
                ->limit(25)
                ->get();

            return $candidates
                ->sortByDesc(function (School $school) use ($tokens): int {
                    $schoolName = self::normalise($school->name);

                    return count(array_filter($tokens, fn(string $token): bool => str_contains($schoolName, $token)));
                })
                ->take(5)
                ->values();
        });
    }

    private static function normalise(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->toString();
    }

    /**
     * Active contact types, keyed by ULID.
     *
     * @return array<string, string>
     */
    public static function contactTypeOptions(): array
    {
        /** @var array<string, string> */
        return ContactType::query()
            ->where('is_active', PRFActiveStatus::ACTIVE)
            ->orderBy('name')
            ->pluck('name', 'ulid')
            ->all();
    }

    /**
     * The contact type most schools' contacts already use (usually the principal or patron).
     */
    public static function defaultContactTypeULID(): ?string
    {
        $contactType = ContactType::query()
            ->where('is_active', PRFActiveStatus::ACTIVE)
            ->withCount('schoolContacts')
            ->orderByDesc('school_contacts_count')
            ->orderBy('name')
            ->first();

        return $contactType?->ulid;
    }

    /**
     * Just enough to save a school and plan a mission there. Everything else can be added later
     * on the school's page.
     *
     * @return list<Component>
     */
    public static function quickCreateForm(): array
    {
        return [
            self::nameField(),
            self::duplicateWarning(),
            Grid::make(2)->columnSpanFull()->schema([self::institutionTypeField(), self::studentsField()]),
            ...self::locationFields(),
            Section::make('Who do we talk to? (optional)')
                ->description('The teacher or patron who arranges visits. You can add more people later.')
                ->compact()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('contact_name')
                        ->label('Their name')
                        ->placeholder('e.g. Mrs Jane Wanjiku')
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->required(fn(Get $get): bool => filled($get('contact_phone'))),
                    PhoneInput::make('contact_phone')
                        ->label('Phone number')
                        ->defaultCountry('KE')
                        ->required(fn(Get $get): bool => filled($get('contact_name'))),
                    Select::make('contact_type_ulid')
                        ->label('Their role')
                        ->options(fn(): array => self::contactTypeOptions())
                        ->default(fn(): ?string => self::defaultContactTypeULID())
                        ->native(false)
                        ->required(fn(Get $get): bool => filled($get('contact_name')))
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Starting values for quickCreateForm() when filling it with fillForm(), which skips defaults.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function quickCreateDefaults(array $overrides = []): array
    {
        return [
            'name' => null,
            'institution_type' => PRFInstitutionType::HIGH_SCHOOL->value,
            'total_students' => 0,
            'location' => null,
            'address' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'contact_type_ulid' => self::defaultContactTypeULID(),
            ...$overrides,
        ];
    }

    /**
     * Saves the school (and its contact, if one was given) from quickCreateForm() data.
     * DB::transaction because there is no builder API for wrapping two jobs in one transaction.
     *
     * @param  array<mixed>  $data
     */
    public static function createFromQuickForm(array $data): School
    {
        return DB::transaction(function () use ($data): School {
            /** @var array<string, mixed> $attributes */
            $attributes = [
                ...Arr::only($data, ['name', 'institution_type', 'address', 'location']),
                'total_students' => is_numeric($data['total_students'] ?? null) ? (int) $data['total_students'] : 0,
                'is_active' => PRFActiveStatus::ACTIVE,
            ];

            $school = CreateJob::dispatchSync($attributes);
            assert($school instanceof School);

            $contactTypeULID = $data['contact_type_ulid'] ?? self::defaultContactTypeULID();

            if (
                filled($data['contact_name'] ?? null)
                && filled($data['contact_phone'] ?? null)
                && filled($contactTypeULID)
            ) {
                CreateContactJob::dispatchSync([
                    'school_ulid' => $school->ulid,
                    'contact_type_ulid' => $contactTypeULID,
                    'name' => $data['contact_name'],
                    'phone' => $data['contact_phone'],
                ]);
            }

            return $school;
        });
    }
}
