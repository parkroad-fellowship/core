<?php

namespace App\Exports\Member;

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
            ->whereNotIn('email', [
                'admin@parkroadfellowship.org',
                'nancy.muhungi@parkroadfellowship.org',
                'approvals@parkroadfellowship.org',
                'chairperson@parkroadfellowship.org',
                'vicechair@parkroadfellowship.org',
                'treasurer@parkroadfellowship.org',
                'missions@parkroadfellowship.org',
                'organizingsec@parkroadfellowship.org',
                'follow-up@parkroadfellowship.org',
                'prayerdesk@parkroadfellowship.org',
                'mwangi.maina@parkroadfellowship.org',
                'wilberforce.thiribi@parkroadfellowship.org',
                'esther.nyokabi.kabwere@parkroadfellowship.org',
                'leah.muringo.muringi@parkroadfellowship.org',
                'adulu@parkroadfellowship.org',
            ])
            ->where([
                'approved' => true,
            ])
            ->whereNotNull('phone_number');
    }

    public function map($member): array
    {
        return [
            $member->first_name,
            $member->last_name,
            $member->email,
            'prf@2025*',
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
