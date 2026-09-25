<?php

namespace App\Imports\Member;

use App\Helpers\Utils;
use App\Jobs\Member\OnboardJob;
use App\Models\Member;
use Exception;
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
                $firstName = Str::of($row['first_name'])->trim()->title();
                $lastName = Str::of($row['last_name'])->trim()->title();
                $otherName = Str::of($row['other_names'])->trim()->title();

                $formattedPhone = Utils::toE164($row['phone_number'] ?? null);
                $personalEmail = Str::lower(trim((string) ($row['email_address'] ?? '')));

                // Every member needs two names, a phone and a personal email (their Workspace recovery contacts).
                if (!$lastName || $formattedPhone === null || !filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
                    Log::warning('Member import row skipped: missing name, phone or personal email', ['row' => $row]);

                    continue;
                }

                $member = Member::updateOrCreate([
                    'phone_number' => $formattedPhone,
                ], [
                    'first_name' => Str::title($firstName),
                    'last_name' => Str::trim("{$lastName} {$otherName}"),
                    'full_name' => Str::trim("{$firstName} {$lastName} {$otherName}"),
                    'phone_number' => $formattedPhone,
                    'personal_email' => $personalEmail,
                    'approved' => true,
                ]);

                if ($member->wasRecentlyCreated) {
                    OnboardJob::dispatchSync($member);
                }
            } catch (Exception $e) {
                Log::error($e->getMessage());

                continue;
            }
        }
    }
}
