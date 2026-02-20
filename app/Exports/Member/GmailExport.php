<?php

namespace App\Exports\Member;

use App\Models\AppSetting;
use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GmailExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function query()
    {
        return Member::query()
            ->where([
                'approved' => true,
                'is_invited' => false,
            ])
            ->whereNotNull('phone_number');
    }

    public function map($member): array
    {
        return [
            $member->first_name,
            $member->last_name,
            $member->email,
            AppSetting::get('organization.google_workspace_temp_password', ''),
            '/',
            $member->personal_email,
            $member->personal_email,
            "'".$member->phone_number,
            "'".$member->phone_number,
            true,
        ];
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email Address',
            'Password',
            'Org Unit Path',
            'Recovery Email',
            'Work Secondary Email',
            'Recovery Phone',
            'Work Phone',
            'Change Password at Next Sign-In',
        ];
    }
}
