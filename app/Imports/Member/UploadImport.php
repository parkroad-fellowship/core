<?php

namespace App\Imports\Member;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $firstName = trim($row['first_name']);
                $lastName = trim($row['last_name']);
                $otherName = trim($row['other_name']);

                if (! $lastName) {
                    // Skip Anyone Missing 2 Names
                    continue;
                }

                Member::updateOrCreate([
                    'phone_number' => $row['phone_number'],
                ], [
                    'first_name' => $firstName,
                    'last_name' => trim("{$lastName} {$otherName}"),
                    'phone_number' => $row['phone_number'],
                    'personal_email' => Str::lower($row['email_address']),
                    'approved' => true,
                ]);
            } catch (\Exception $e) {
                Log::error($e->getMessage());

                continue;
            }
        }
    }
}
