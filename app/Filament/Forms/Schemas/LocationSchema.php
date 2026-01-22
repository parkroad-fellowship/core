<?php

namespace App\Filament\Forms\Schemas;

use App\Helpers\Utils;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

class LocationSchema
{
    /**
     * Create a complete venue/location section with geocomplete and map.
     */
    public static function make(
        string $sectionTitle = 'Location',
        string $sectionDescription = 'Event location and venue',
        string $sectionIcon = 'heroicon-o-map-pin',
        bool $collapsible = true,
        bool $collapsed = false,
        bool $includeCapacity = false,
    ): Section {
        $schema = [
            static::geocompleteField(),
            static::venueField(),
        ];

        if ($includeCapacity) {
            $schema[] = static::capacityField();
        }

        $schema[] = static::mapField();

        return Section::make($sectionTitle)
            ->description($sectionDescription)
            ->icon($sectionIcon)
            ->schema($schema)
            ->columns(2)
            ->collapsible($collapsible)
            ->collapsed($collapsed);
    }

    /**
     * Create a geocomplete search field.
     */
    public static function geocompleteField(
        string $name = 'location_search',
        string $label = '🔍 Search Location',
        string $venueFieldName = 'venue',
        string $locationFieldName = 'location',
        array $countries = ['ke'],
    ): Geocomplete {
        return Geocomplete::make($name)
            ->label($label)
            ->helperText('Type a venue name or address to search')
            ->isLocation()
            ->types([
                'point_of_interest',
                'premise',
                'church',
                'place_of_worship',
                'tourist_attraction',
                'establishment',
            ])
            ->reverseGeocode([
                'street_number' => '%n',
                'route' => '%S',
                'locality' => '%L',
                'sublocality' => '%sublocality',
                'administrative_area_level_3' => '%A3',
                'administrative_area_level_2' => '%A2',
                'administrative_area_level_1' => '%A1',
                'country' => '%c',
                'postal_code' => '%z',
                'formatted' => '%formatted_address',
            ])
            ->countries($countries)
            ->updateLatLng()
            ->maxLength(1024)
            ->minChars(3)
            ->placeholder('Type venue name or address...')
            ->geolocate()
            ->geolocateIcon('heroicon-o-map')
            ->columnSpanFull()
            ->dehydrated(false)
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) use ($venueFieldName, $locationFieldName) {
                $set($locationFieldName, $state);

                if ($state && isset($state['lat']) && isset($state['lng'])) {
                    $lat = $state['lat'];
                    $lng = $state['lng'];
                    $fallbackAddress = $state['formatted_address'] ?? null;

                    if (class_exists(Utils::class) && method_exists(Utils::class, 'buildKenyanAddress')) {
                        $elaborateAddress = Utils::buildKenyanAddress($lat, $lng, $fallbackAddress);
                        $set($venueFieldName, $elaborateAddress);
                    } elseif ($fallbackAddress) {
                        $set($venueFieldName, $fallbackAddress);
                    }
                }
            });
    }

    /**
     * Create a venue name/address text field.
     */
    public static function venueField(
        string $name = 'venue',
        string $label = '🏢 Venue Name/Address',
        bool $required = false,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(255)
            ->helperText('Venue name and address (auto-filled from map search)')
            ->placeholder('e.g., Main Conference Hall, Nairobi')
            ->required($required);
    }

    /**
     * Create a capacity field.
     */
    public static function capacityField(
        string $name = 'capacity',
        string $label = '👥 Capacity',
        int $default = 0,
        bool $required = false,
    ): TextInput {
        return TextInput::make($name)
            ->label($label)
            ->default($default)
            ->numeric()
            ->required($required)
            ->helperText('Maximum number of attendees (0 for unlimited)')
            ->placeholder('e.g., 100')
            ->prefixIcon('heroicon-o-users');
    }

    /**
     * Create an interactive map field.
     */
    public static function mapField(
        string $name = 'location',
        string $label = '📍 Location Map',
        array $defaultLocation = [-1.319167, 36.9275],
        int $defaultZoom = 10,
        string $height = '400px',
    ): Map {
        return Map::make($name)
            ->label($label)
            ->helperText('Click and drag to adjust the location pin')
            ->mapControls([
                'mapTypeControl' => true,
                'zoomControl' => true,
                'fullscreenControl' => true,
                'streetViewControl' => false,
                'rotateControl' => false,
                'scaleControl' => false,
            ])
            ->autocompleteReverse(true)
            ->clickable(true)
            ->draggable(true)
            ->geolocate(true)
            ->geolocateOnLoad(false)
            ->defaultZoom($defaultZoom)
            ->defaultLocation($defaultLocation)
            ->height($height)
            ->reactive()
            ->columnSpanFull();
    }
}
