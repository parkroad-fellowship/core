<?php

namespace App\Helpers;

use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Models\AccountingEvent;
use App\Models\AppSetting;
use App\Models\Member;
use App\Models\Mission;
use App\Models\Requisition;
use App\Models\TransferRate;
use Exception;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class Utils
{
    public static function generateULID()
    {
        return strtolower((string) Str::ulid());
    }

    /**
     * The seeded password for this environment: fixed and documented outside production
     * (see docs/developer-invite.md), random in production.
     */
    public static function defaultPassword(): string
    {
        return match (app()->environment()) {
            'production' => Str::random(16),
            'local' => '1password',
            default => 'asZDcVt7Q',
        };
    }

    public static function randomPassword(): string
    {
        return bcrypt(self::defaultPassword());
    }

    /**
     * How the current tenant's members sign in. Organisation mode needs a real Workspace
     * domain; without one (or without an explicit mode) members use their personal email.
     */
    public static function memberEmailMode(): PRFMemberEmailMode
    {
        $mode = self::tenant_setting('organization.member_email_mode');
        $hasDomain = self::configuredOrgEmailDomain() !== null;

        // Organisation mode without a real (non-webmail) domain would fabricate addresses: use personal email.
        if (filled($mode)) {
            $mode = PRFMemberEmailMode::fromValue($mode);

            return $mode === PRFMemberEmailMode::ORGANISATION_DOMAIN && !$hasDomain
                ? PRFMemberEmailMode::PERSONAL
                : $mode;
        }

        return $hasDomain ? PRFMemberEmailMode::ORGANISATION_DOMAIN : PRFMemberEmailMode::PERSONAL;
    }

    /**
     * The tenant's Google Workspace domain, or null when members use personal email.
     * Never returns a public webmail domain: addresses there belong to strangers.
     */
    public static function getOrgEmailDomain(): ?string
    {
        if (self::memberEmailMode() !== PRFMemberEmailMode::ORGANISATION_DOMAIN) {
            return null;
        }

        return self::configuredOrgEmailDomain();
    }

    public static function isPublicEmailDomain(string $domain): bool
    {
        return in_array(strtolower(trim($domain)), config('prf.app.public_email_domains', []), true);
    }

    private static function configuredOrgEmailDomain(): ?string
    {
        $domain = self::tenant_setting('organization.org_email_domain');

        if (!is_string($domain) || blank($domain) || self::isPublicEmailDomain($domain)) {
            return null;
        }

        return strtolower(trim($domain));
    }

    public static function getCharge(PRFTransactionType $chargeType, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return match ($chargeType) {
            PRFTransactionType::CASH => 0,
            default => (int) (
                TransferRate::where([
                    'transaction_type' => $chargeType->value,
                    ['min_amount', '<=', $amount],
                    ['max_amount', '>=', $amount],
                ])->first()?->charge ?? 0
            ),
        };
    }

    /**
     * Anticipated M-Pesa transfer cost for moving an amount: whichever is
     * highest of paybill / registered-user / default tariffs.
     */
    public static function estimateTransferCharge(int $amount): int
    {
        return (int) max(
            self::getCharge(PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF, $amount),
            self::getCharge(PRFTransactionType::MPESA_OTHER_REGISTERED_USER, $amount),
            self::getCharge(PRFTransactionType::MPESA_DEFAULT, $amount),
        );
    }

    public static function getMpesaCharge(string $confirmationMessage)
    {
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

    public static function generateAccountingEventFileName(
        AccountingEvent $accountingEvent,
        string $type,
        string $extension,
    ) {
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
     * Normalise a phone number to E.164 (+254712345678), reading local numbers in the configured
     * region. Returns null when the number can't be understood.
     */
    public static function toE164(string|int|null $phoneNumber): ?string
    {
        $phoneNumber = trim((string) $phoneNumber);

        if ($phoneNumber === '') {
            return null;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsed = $phoneUtil->parse($phoneNumber, (string) config('prf.sms.region', 'KE'));
        } catch (NumberParseException) {
            return null;
        }

        return $phoneUtil->isValidNumber($parsed) ? $phoneUtil->format($parsed, PhoneNumberFormat::E164) : null;
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
            return (
                '+'
                . substr($cleaned, 0, 3)
                . ' '
                . substr($cleaned, 3, 3)
                . ' '
                . substr($cleaned, 6, 3)
                . ' '
                . substr($cleaned, 9, 3)
            );
        }

        // 10-digit local: 0XXXXXXXXX
        if (strlen($cleaned) === 10 && str_starts_with($cleaned, '0')) {
            return '+254 ' . substr($cleaned, 1, 3) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7, 3);
        }

        // 9-digit without prefix: 7XXXXXXXX or 1XXXXXXXX
        if (strlen($cleaned) === 9 && in_array($cleaned[0], ['7', '1'])) {
            return '+254 ' . substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 3);
        }

        return (string) $phoneNumber;
    }

    public static function checkWhatsAppGroupLink(?string $link): bool
    {
        return Str::of($link)->trim()->match('/^https:\/\/chat\.whatsapp\.com\/[A-Za-z0-9_-]{22,}$/')->isNotEmpty();
    }

    /**
     * Build a detailed Kenyan address from latitude and longitude using Google Geocoding API
     *
     * @param  string|null  $fallbackAddress  Optional fallback address if API fails
     * @return string The formatted Kenyan address
     */
    public static function buildKenyanAddress(
        float $latitude,
        float $longitude,
        ?string $fallbackAddress = null,
    ): string {
        try {
            $apiKey = config('filament-google-maps.key');

            if (empty($apiKey)) {
                return $fallbackAddress ?? 'Address not available';
            }

            $response = Http::timeout(10)->connectTimeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $apiKey,
            ]);

            $data = $response->json();

            if ($data['status'] === 'OK' && !empty($data['results'])) {
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
                if (!empty($addressParts['premise'])) {
                    $elaborateAddress[] = $addressParts['premise'];
                }

                // Street address
                $street = '';
                if (!empty($addressParts['street_number'])) {
                    $street .= $addressParts['street_number'] . ' ';
                }
                if (!empty($addressParts['route'])) {
                    $street .= $addressParts['route'];
                }
                if (!empty($street)) {
                    $elaborateAddress[] = trim($street);
                }

                // Area/Neighborhood (Sublocalities)
                if (!empty($addressParts['sublocality_level_3'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_3'];
                }
                if (!empty($addressParts['sublocality_level_2'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_2'] . ' Ward';
                }
                if (!empty($addressParts['sublocality_level_1'])) {
                    $elaborateAddress[] = $addressParts['sublocality_level_1'] . ' Constituency';
                }

                // Town/City
                if (!empty($addressParts['locality'])) {
                    $elaborateAddress[] = $addressParts['locality'] . ' Town';
                }

                // Sub-county
                if (!empty($addressParts['administrative_area_level_3'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_3'] . ' Sub-County';
                }

                // County
                if (!empty($addressParts['administrative_area_level_2'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_2'] . ' County';
                }

                // Region/Province
                if (!empty($addressParts['administrative_area_level_1'])) {
                    $elaborateAddress[] = $addressParts['administrative_area_level_1'] . ' Region';
                }

                // Postal code
                if (!empty($addressParts['postal_code'])) {
                    $elaborateAddress[] = 'P.O. Box ' . $addressParts['postal_code'];
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

        return Str::of($azureUrl)->replace('prfcorestorage.blob.core.windows.net', $mediaDomain)->__toString();
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

    /**
     * Everyone who should receive mail sent to a desk: the members behind the desk addresses
     * (matched on either email, so they also get push notifications) plus a plain mail route
     * for any desk address that no member uses.
     *
     * @return Collection<int, Member|AnonymousNotifiable>
     */
    public static function deskRecipients(PRFResponsibleDesk|int $desk): Collection
    {
        $emails = collect(self::getDeskEmails($desk))
            ->filter(fn(mixed $email) => is_string($email) && $email !== '')
            ->map(fn(string $email) => strtolower($email))
            ->unique();

        if ($emails->isEmpty()) {
            return collect();
        }

        $members = Member::query()->where(
            fn($query) => $query->whereIn('email', $emails)->orWhereIn('personal_email', $emails),
        )->get();

        $covered = $members->flatMap(fn(Member $member) => [
            strtolower((string) $member->email),
            strtolower((string) $member->personal_email),
        ]);

        return collect([
            ...$members->all(),
            ...$emails
                ->diff($covered)
                ->map(fn(string $email) => Notification::route('mail', $email))
                ->all(),
        ]);
    }

    public static function checkExternalURLAvailability(string $url): bool
    {
        try {
            $response = Http::timeout(5)->connectTimeout(3)->head($url);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * A tenant setting; outside a tenant there is no tenant value, so the default is returned.
     */
    public static function tenant_setting(string $key, mixed $default = null): mixed
    {
        if (tenancy()->initialized) {
            return AppSetting::get($key, $default);
        }

        return $default;
    }

    /**
     * Seed the domains for the given tenant ID based on the application environment.
     */
    public static function seedDomains(?string $tenantId): void
    {
        if (!$tenantId || !Schema::hasTable('domains')) {
            return;
        }

        match (app()->environment()) {
            'local' => [
                'app.prf.test',
            ],
            'development' => [
                'dev-app.parkroadfellowship.org',
                'dev-api.parkroadfellowship.org',
                'dev-ws.parkroadfellowship.org',
            ],
            'staging' => [
                'stg-app.parkroadfellowship.org',
                'stg-api.parkroadfellowship.org',
                'staging-app.parkroadfellowship.org',
                'demo.parkroadfellowship.org',
                'stg-ws.parkroadfellowship.org',
            ],
            'production' => [
                'app.parkroadfellowship.org',
                'api.parkroadfellowship.org',
                'ws.parkroadfellowship.org',
            ],
            default => [],
        };
    }
}
