<?php

namespace App\Imports\Member;

use App\Models\Member;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {

        $phoneUtil = PhoneNumberUtil::getInstance();

        foreach ($rows as $row) {
            try {
                $firstName = Str::of($row['first_name'])
                    ->trim()
                    ->title();
                $lastName = Str::of($row['last_name'])
                    ->trim()
                    ->title();
                $otherName = Str::of($row['other_names'])
                    ->trim()
                    ->title();

                if (! $lastName) {
                    // Skip Anyone Missing 2 Names
                    continue;
                }

                $formattedPhone = $phoneUtil->format(
                    number: $phoneUtil->parse($row['phone_number'], 'KE'),
                    numberFormat: PhoneNumberFormat::E164,
                );

                Member::updateOrCreate([
                    'phone_number' => $formattedPhone,
                ], [
                    'first_name' => Str::title($firstName),
                    'last_name' => Str::trim("{$lastName} {$otherName}"),
                    'full_name' => Str::trim("{$firstName} {$lastName} {$otherName}"),
                    'phone_number' => $formattedPhone,
                    'personal_email' => Str::lower($row['email_address']),
                    'approved' => true,
                ]);
            } catch (Exception $e) {
                Log::error($e->getMessage());

                continue;
            }
        }
    }
}
