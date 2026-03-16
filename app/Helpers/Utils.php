<?php

namespace App\Helpers;

use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Models\AccountingEvent;
use App\Models\AppSetting;
use App\Models\Mission;
use App\Models\Requisition;
use App\Models\TransferRate;
use Exception;
use Illuminate\Support\Facades\Http;
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
            ->append('@'.config('prf.app.org_email_domain', 'example.org')) // Append the domain
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

    public static function generateMissionsScheduleFileName(?string $termName = null, string $extension = '.pdf')
    {
        $year = now()->year;

        $name = Str::of($year)
            ->append('_')
            ->append($termName ?? 'All_Terms')
            ->append('_Missions_Schedule')
            ->slug('_')
            ->append($extension)
            ->__toString();

        return $name;
    }

    /**
     * Format a phone number for display in exports and reports.
     * Returns a space-separated format like "+254 712 345 678" that Excel treats as text.
     */
    public static function formatPhoneNumber(string|int|null $phoneNumber): string
    {
        if (empty($phoneNumber)) {
            return 'N/A';
        }

        $cleaned = preg_replace('/[^0-9]/', '', (string) $phoneNumber);

        // 12-digit Kenyan number: 254XXXXXXXXX
        if (strlen($cleaned) === 12 && str_starts_with($cleaned, '254')) {
            return '+'.substr($cleaned, 0, 3).' '.substr($cleaned, 3, 3).' '.substr($cleaned, 6, 3).' '.substr($cleaned, 9, 3);
        }

        // 10-digit local: 0XXXXXXXXX
        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '0')) {
            return '+254 '.substr($cleaned, 1, 3).' '.substr($cleaned, 4, 3).' '.substr($cleaned, 7, 3);
        }

        // 9-digit without prefix: 7XXXXXXXX or 1XXXXXXXX
        if (strlen($cleaned) === 9 && in_array($cleaned[0], ['7', '1'])) {
            return '+254 '.substr($cleaned, 0, 3).' '.substr($cleaned, 3, 3).' '.substr($cleaned, 6, 3);
        }

        return (string) $phoneNumber;
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

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $apiKey,
                ]);

            $data = $response->json();

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
        } catch (Exception $e) {
            // Fallback to the provided fallback address if anything fails
            return $fallbackAddress ?? 'Address not available';
        }
    }

    public static function convertAzureURLToMediaURL(string $azureUrl): string
    {
        $mediaDomain = AppSetting::get('organization.media_cdn_domain', '');

        if (empty($mediaDomain)) {
            return $azureUrl;
        }

        return Str::of($azureUrl)
            ->replace('prfcorestorage.blob.core.windows.net', $mediaDomain)
            ->__toString();
    }

    public static function getDeskEmails(PRFResponsibleDesk|int $desk): array
    {
        if (is_int($desk)) {
            $desk = PRFResponsibleDesk::from($desk);
        }

        return match ($desk) {
            PRFResponsibleDesk::CHAIRPERSON => AppSetting::get('desk_emails.chairpersons', []),
            PRFResponsibleDesk::VICE_CHAIRPERSON_DESK => AppSetting::get('desk_emails.vice_chairpersons', []),
            PRFResponsibleDesk::TREASURER_DESK => AppSetting::get('desk_emails.treasurers', []),
            PRFResponsibleDesk::ORGANISING_SECRETARY_DESK => AppSetting::get('desk_emails.organising_secretary', []),
            PRFResponsibleDesk::MISSIONS_DESK => AppSetting::get('desk_emails.missions', []),
            PRFResponsibleDesk::PRAYER_DESK => AppSetting::get('desk_emails.prayer', []),
            PRFResponsibleDesk::FOLLOW_UP_DESK => AppSetting::get('desk_emails.follow_up', []),
            PRFResponsibleDesk::MUSIC_DESK => AppSetting::get('desk_emails.music', []),
        };
    }

    public static function checkExternalURLAvailability(string $url): bool
    {
        try {
            $response = Http::timeout(5)
                ->connectTimeout(3)
                ->head($url);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}
