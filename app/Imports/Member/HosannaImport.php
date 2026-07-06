<?php

namespace App\Imports\Member;

use App\Models\Church;
use App\Models\Department;
use App\Models\Gift;
use App\Models\Member;
use App\Models\Profession;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HosannaImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    public function collection(Collection $rows): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        foreach ($rows as $rowIndex => $row) {
            try {
                $name = Str::of($row['name'] ?? '')->trim();

                if ($name->isEmpty()) {
                    $this->skippedCount++;

                    continue;
                }

                $phoneRaw = $row['active_contact'] ?? '';
                if (empty($phoneRaw)) {
                    $this->skippedCount++;
                    $this->errors[] = 'Row '.($rowIndex + 2).': Missing phone number';

                    continue;
                }

                $formattedPhone = $phoneUtil->format(
                    number: $phoneUtil->parse($phoneRaw, 'KE'),
                    numberFormat: PhoneNumberFormat::E164,
                );

                $nameParts = $name->explode(' ');
                $firstName = Str::of($nameParts->shift() ?? '')->title();
                $lastName = Str::of($nameParts->implode(' '))->title();

                $member = Member::updateOrCreate([
                    'phone_number' => $formattedPhone,
                ], [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'full_name' => $name->title(),
                    'phone_number' => $formattedPhone,
                    'email' => Str::lower($row['active_email_address'] ?? ''),
                    'personal_email' => Str::lower($row['active_email_address'] ?? ''),
                    'approved' => true,
                ]);

                $existing = ! $member->wasRecentlyCreated;
                if ($existing) {
                    $this->updatedCount++;
                } else {
                    $this->importedCount++;
                }

                // Link church
                $churchName = Str::of($row['church_ministry_you_are_serving'] ?? '')->trim();
                if ($churchName->isNotEmpty()) {
                    $church = Church::firstOrCreate([
                        'name' => $churchName->toString(),
                    ]);
                    $member->church()->associate($church);
                    $member->save();
                }

                // Link profession (primary segment before / or ,)
                $professionRaw = Str::of($row['profession_career'] ?? '')->trim();
                if ($professionRaw->isNotEmpty()) {
                    $professionName = Str::of(preg_split('#[/,]#', $professionRaw->toString())[0])->trim()->title();
                    if ($professionName->isNotEmpty()) {
                        $profession = Profession::firstOrCreate([
                            'name' => $professionName->toString(),
                        ]);
                        $member->profession()->associate($profession);
                        $member->save();
                    }
                }

                // Parse calling/talent into gifts and departments
                $callingRaw = $row['your_calling_talent'] ?? '';
                if (! empty($callingRaw)) {
                    $segments = preg_split('#[/,&]#', $callingRaw);
                    $giftIds = [];
                    $departmentIds = [];

                    foreach ($segments as $segment) {
                        $segment = trim($segment);
                        $segment = rtrim($segment, '. ');
                        $segment = Str::title($segment);

                        if (empty($segment)) {
                            continue;
                        }

                        $gift = Gift::firstOrCreate(['name' => $segment]);
                        $giftIds[] = $gift->id;

                        $department = Department::firstOrCreate(['name' => $segment]);
                        $departmentIds[] = $department->id;
                    }

                    if (! empty($giftIds)) {
                        $member->gifts()->syncWithoutDetaching($giftIds);
                    }
                    if (! empty($departmentIds)) {
                        $member->departments()->syncWithoutDetaching($departmentIds);
                    }
                }

                // Store ID number and referral in bio
                $bioParts = [];
                $idNumber = Str::of($row['id_number'] ?? '')->trim();
                if ($idNumber->isNotEmpty()) {
                    $bioParts[] = "ID: {$idNumber}";
                }

                $referredBy = Str::of($row['who_referred_you_to_the_team'] ?? '')->trim();
                if ($referredBy->isNotEmpty()) {
                    $bioParts[] = "Referred by: {$referredBy}";
                }

                if (! empty($bioParts)) {
                    $existingBio = $member->bio ? $member->bio."\n" : '';
                    $member->update([
                        'bio' => $existingBio.implode("\n", $bioParts),
                    ]);
                }

            } catch (NumberParseException $e) {
                $this->skippedCount++;
                $this->errors[] = 'Row '.($rowIndex + 2).': Invalid phone number - '.($row['active_contact'] ?? 'N/A');
                Log::error('Phone parse error for row '.($rowIndex + 2), [
                    'phone' => $row['active_contact'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ]);

                continue;
            } catch (Exception $e) {
                $this->skippedCount++;
                $this->errors[] = 'Row '.($rowIndex + 2).': '.$e->getMessage();
                Log::error('Import error for row '.($rowIndex + 2), [
                    'name' => $row['name'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSummary(): string
    {
        $summary = "Import completed: {$this->importedCount} new members added, {$this->updatedCount} members updated";

        if ($this->skippedCount > 0) {
            $summary .= ", {$this->skippedCount} rows skipped";
        }

        return $summary;
    }
}
