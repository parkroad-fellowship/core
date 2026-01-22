<?php

namespace App\Imports\Member;

use App\Models\Member;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;

class WebUploadImport implements SkipsEmptyRows, ToCollection, WithEvents, WithHeadingRow
{
    protected $importedCount = 0;

    protected $skippedCount = 0;

    protected $updatedCount = 0;

    protected $errors = [];

    public function collection(Collection $rows)
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        foreach ($rows as $rowIndex => $row) {
            try {
                // Validate required fields
                if (empty($row['first_name']) && empty($row['last_name'])) {
                    $this->skippedCount++;
                    $this->errors[] = 'Row '.($rowIndex + 2).': Missing required first_name or last_name';

                    continue;
                }

                if (empty($row['phone_number'])) {
                    $this->skippedCount++;
                    $this->errors[] = 'Row '.($rowIndex + 2).': Missing required phone_number';

                    continue;
                }

                $firstName = Str::of($row['first_name'] ?? '')
                    ->trim()
                    ->title();
                $lastName = Str::of($row['last_name'] ?? '')
                    ->trim()
                    ->title();
                $otherName = Str::of($row['other_names'] ?? '')
                    ->trim()
                    ->title();

                // Format phone number
                $formattedPhone = $phoneUtil->format(
                    number: $phoneUtil->parse($row['phone_number'], 'KE'),
                    numberFormat: PhoneNumberFormat::E164,
                );

                // Check if member exists
                $existingMember = Member::where('phone_number', $formattedPhone)->first();

                $memberData = [
                    'first_name' => Str::title($firstName),
                    'last_name' => Str::trim("{$lastName} {$otherName}"),
                    'full_name' => Str::trim("{$firstName} {$lastName} {$otherName}"),
                    'phone_number' => $formattedPhone,
                    'personal_email' => Str::lower($row['email_address']),
                    'approved' => true,
                ];

                Member::updateOrCreate([
                    'phone_number' => $formattedPhone,
                ], $memberData);

                if ($existingMember) {
                    $this->updatedCount++;
                } else {
                    $this->importedCount++;
                }

            } catch (NumberParseException $e) {
                $this->skippedCount++;
                $this->errors[] = 'Row '.($rowIndex + 2).': Invalid phone number format - '.($row['phone_number'] ?? 'N/A');
                Log::error('Phone number parse error for row '.($rowIndex + 2), [
                    'phone' => $row['phone_number'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ]);

                continue;
            } catch (Exception $e) {
                $this->skippedCount++;
                $this->errors[] = 'Row '.($rowIndex + 2).': '.$e->getMessage();
                Log::error('Import error for row '.($rowIndex + 2), [
                    'row_data' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                // Log import summary
                Log::info('Member import completed', [
                    'imported' => $this->importedCount,
                    'updated' => $this->updatedCount,
                    'skipped' => $this->skippedCount,
                    'errors' => count($this->errors),
                ]);
            },
        ];
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
