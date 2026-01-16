<?php

namespace App\Helpers;

use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Models\AccountingEvent;
use App\Models\Mission;
use App\Models\Requisition;
use App\Models\TransferRate;
use Illuminate\Support\Str;

class Utils
{
    public static function generateUlid()
    {
        return strtolower((string) Str::ulid());
    }

    public static function randomPassword()
    {
        $password = match (app()->environment()) {
            'production' => Str::random(16),
            default => 'QRnYYl3say',
        };

        return bcrypt($password);
    }

    public static function generatePRFEmail(
        string $model,
        string $fullName,
        bool $random = false,
    ) {
        $email = Str::of($fullName)
            ->trim()
            ->replace(' ', '.') // Replace spaces with dots
            ->pipe(fn ($name) => preg_replace('/[^a-zA-Z.]/u', '', $name)) // Remove all characters except letters and dots
            ->when($random, fn ($builder) => $builder->append('.'.rand(1, 1000))) // Append random number if $random is true
            ->append('@parkroadfellowship.org') // Append the domain
            ->lower() // Convert to lowercase
            ->__toString();

        $emailExists = $model::query()
            ->where('email', $email)
            ->exists();

        if ($emailExists) {
            return self::generatePRFEmail($model, $fullName, true);
        }

        return $email;
    }

    public static function getCharge(
        PRFTransactionType $chargeType,
        int $amount,
    ) {
        if ($amount <= 0) {
            return 0;
        }

        return
            match ($chargeType) {
                PRFTransactionType::CASH->value => 0,
                default => TransferRate::where([
                    'transaction_type' => $chargeType->value,
                    ['min_amount', '<=', $amount],
                    ['max_amount', '>=', $amount],
                ])->first()?->charge ?? 0,
            };
    }

    public static function getMpesaCharge(
        string $confirmationMessage,
    ) {
        $charge = 0;

        // Pattern for "Transaction cost, Ksh7.00" format
        if (preg_match('/Transaction cost, Ksh([\d,.]+)/', $confirmationMessage, $matches)) {
            $charge = (float) str_replace(',', '', $matches[1]);
        }

        if (preg_match('/Transaction cost,\s*Ksh([\d,.]+)/i', $confirmationMessage, $matches)) {
            $charge = (float) str_replace(',', '', $matches[1]);
        }

        // Alternative pattern for other possible formats
        elseif (preg_match('/transaction cost is Ksh([\d,.]+)/', $confirmationMessage, $matches)) {
            $charge = (float) str_replace(',', '', $matches[1]);
        }
        // Another alternative pattern
        elseif (preg_match('/Fee: Ksh([\d,.]+)/', $confirmationMessage, $matches)) {
            $charge = (float) str_replace(',', '', $matches[1]);
        }

        return $charge;
    }

    public static function generateMissionName(Mission $mission)
    {
        return Str::of($mission->school->name)
            ->append(' - ')
            ->append($mission->start_date->format('Y-m-d'))
            ->__toString();
    }

    public static function generateMissionFileName(Mission $mission, string $type, string $extension)
    {
        return Str::of($mission->school->name)
            ->append('-')
            ->append($mission->start_date->format('Y-m-d'))
            ->append('-')
            ->append($type)
            ->append('-report')
            ->slug()
            ->append($extension)
            ->__toString();
    }

    public static function generateRequisitionFileName(Requisition $requisition, string $type, string $extension)
    {
        return Str::of($requisition->accountingEvent->name)
            ->append('-')
            ->append($requisition->requisition_date->format('Y-m-d'))
            ->append('-')
            ->append($type)
            ->append('-report')
            ->slug()
            ->append($extension)
            ->__toString();
    }

    public static function generateAccountingEventFileName(AccountingEvent $accountingEvent, string $type, string $extension)
    {
        return Str::of($accountingEvent->name)
            ->append('-')
            ->append($type)
            ->append('-report')
            ->slug()
            ->append($extension)
            ->__toString();
    }

    public static function checkWhatsAppGroupLink(
        ?string $link,
    ): bool {
        return Str::of($link)
            ->trim()
            ->match('/^https:\/\/chat\.whatsapp\.com\/[A-Za-z0-9_-]{22,}$/')
            ->isNotEmpty();
    }

    /**
     * Build a detailed Kenyan address from latitude and longitude using Google Geocoding API
     *
     * @param  string|null  $fallbackAddress  Optional fallback address if API fails
     * @return string The formatted Kenyan address
     */
    public static function buildKenyanAddress(float $latitude, float $longitude, ?string $fallbackAddress = null): string
    {
        try {
            $apiKey = config('filament-google-maps.key');

            if (empty($apiKey)) {
                return $fallbackAddress ?? 'Address not available';
            }

            // Call Google Geocoding API for detailed address components
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data['status'] === 'OK' && ! empty($data['results'])) {
                $result = $data['results'][0];
                $components = $result['address_components'];

                // Extract address components
                $addressParts = [
                    'premise' => '',
                    'street_number' => '',
                    'route' => '',
                    'sublocality_level_3' => '',
                    'sublocality_level_2' => '',
                    'sublocality_level_1' => '',
                    'locality' => '',
                    'administrative_area_level_3' => '',
                    'administrative_area_level_2' => '',
                    'administrative_area_level_1' => '',
                    'postal_code' => '',
                ];

                foreach ($components as $component) {
                    $types = $component['types'];
                    $longName = $component['long_name'];

                    foreach ($types as $type) {
                        if (array_key_exists($type, $addressParts)) {
                            $addressParts[$type] = $longName;
                        }
                    }
                }

                // Build elaborate Kenyan address
                $elaborateAddress = [];

                // Building/Premise
                if (! empty($addressParts['premise'])) {
                    $elaborateAddress[] = $addressParts['premise'];
                }

                // Street address
                $street = '';
                if (! empty($addressParts['street_number'])) {
                    $street .= $addressParts['street_number'].' ';
                }
                if (! empty($addressParts['route'])) {
                    $street .= $addressParts['route'];
                }
                if (! empty($street)) {
                    $elaborateAddress[] = trim($street);
                }

                // Area/Neighborhood (Sublocalities)
                if (! empty($addressParts['sublocality_level_3'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_3'];
                }
                if (! empty($addressParts['sublocality_level_2'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_2'].' Ward';
                }
                if (! empty($addressParts['sublocality_level_1'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_1'].' Constituency';
                }

                // Town/City
                if (! empty($addressParts['locality'])) {
                    $elaborateAddress[] = $addressParts['locality'].' Town';
                }

                // Sub-county
                if (! empty($addressParts['administrative_area_level_3'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_3'].' Sub-County';
                }

                // County
                if (! empty($addressParts['administrative_area_level_2'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_2'].' County';
                }

                // Region/Province
                if (! empty($addressParts['administrative_area_level_1'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_1'].' Region';
                }

                // Postal code
                if (! empty($addressParts['postal_code'])) {
                    $elaborateAddress[] = 'P.O. Box '.$addressParts['postal_code'];
                }

                // Add Kenya
                $elaborateAddress[] = 'Kenya';

                // Clean and join the address
                $elaborateAddress = array_filter($elaborateAddress); // Remove empty elements
                $finalAddress = implode(', ', $elaborateAddress);

                return $finalAddress;
            } else {
                // Fallback to the provided fallback address if API response is not OK
                return $fallbackAddress ?? 'Address not available';
            }
        } catch (\Exception $e) {
            // Fallback to the provided fallback address if anything fails
            return $fallbackAddress ?? 'Address not available';
        }
    }

    public static function convertAzureURLToMediaURL(string $azureUrl): string
    {
        return Str::of($azureUrl)
            ->replace('prfcorestorage.blob.core.windows.net', 'media.parkroadfellowship.org')
            ->__toString();
    }

    public static function getDeskEmails(PRFResponsibleDesk $desk): array
    {
        return match ($desk) {
            PRFResponsibleDesk::CHAIRPERSON => config('prf.app.chairpersons_desk.emails'),
            PRFResponsibleDesk::VICE_CHAIRPERSON_DESK => config('prf.app.vice_chairpersons_desk.emails'),
            PRFResponsibleDesk::TREASURER_DESK => config('prf.app.treasurers_desk.emails'),
            PRFResponsibleDesk::ORGANISING_SECRETARY_DESK => config('prf.app.organising_secretary_desk.emails'),
            PRFResponsibleDesk::MISSIONS_DESK => config('prf.app.missions_desk.emails'),
            PRFResponsibleDesk::PRAYER_DESK => config('prf.app.prayer_desk.emails'),
            PRFResponsibleDesk::FOLLOW_UP_DESK => config('prf.app.follow_up_desk.emails'),
            PRFResponsibleDesk::MUSIC_DESK => config('prf.app.music_desk.emails'),
        };
    }

    public static function checkExternalURLAvailability(string $url): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->connectTimeout(3)
                ->head($url);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
