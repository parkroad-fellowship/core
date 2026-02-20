<?php

namespace Database\Seeders;

use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Student;
use App\Models\User;
use Database\Factories\MemberFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domain = config('prf.app.org_email_domain', 'example.org');

        // Create the super admin user
        $superAdminUserPayload = (new UserFactory)->raw();
        $superAdmin = User::updateOrCreate([
            'email' => "admin@{$domain}",
        ], array_merge($superAdminUserPayload, [
            'email' => "admin@{$domain}",
            'name' => 'Super Admin',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $superAdmin->assignRole('super admin');

        Member::updateOrCreate([
            'email' => $superAdmin->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $superAdmin->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $superAdmin->email,
            'approved' => false,
        ]));

        // Approval User
        $approvalUserPayload = (new UserFactory)->raw();
        $approvalUser = User::updateOrCreate([
            'email' => "approvals@{$domain}",
        ], array_merge($approvalUserPayload, [
            'email' => "approvals@{$domain}",
            'name' => 'Store Approvals',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $approvalUser->assignRole('super admin');
        Member::updateOrCreate([
            'email' => $approvalUser->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $approvalUser->id,
            'first_name' => 'Store',
            'last_name' => 'Approvals',
            'email' => $approvalUser->email,
            'approved' => false,
        ]));

        // Chairperson
        $chairpersonUserPayload = (new UserFactory)->raw();
        $chairperson = User::updateOrCreate([
            'email' => "chairperson@{$domain}",
        ], array_merge($chairpersonUserPayload, [
            'email' => "chairperson@{$domain}",
            'name' => 'Chairperson',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $chairperson->assignRole('chairperson');

        Member::updateOrCreate([
            'email' => $chairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $chairperson->id,
            'first_name' => 'Chairperson',
            'last_name' => '',
            'email' => $chairperson->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Vice Chairperson
        $viceChairpersonUserPayload = (new UserFactory)->raw();
        $viceChairperson = User::updateOrCreate([
            'email' => "vicechair@{$domain}",
        ], array_merge($viceChairpersonUserPayload, [
            'email' => "vicechair@{$domain}",
            'name' => 'Vice Chairperson',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $viceChairperson->assignRole('vice chairperson');
        Member::updateOrCreate([
            'email' => $viceChairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $viceChairperson->id,
            'first_name' => 'Vice',
            'last_name' => 'Chairperson',
            'email' => $viceChairperson->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Treasurer
        $treasurerUserPayload = (new UserFactory)->raw();
        $treasurer = User::updateOrCreate([
            'email' => "treasurer@{$domain}",
        ], array_merge($treasurerUserPayload, [
            'email' => "treasurer@{$domain}",
            'name' => 'Treasurer',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $treasurer->assignRole('treasurer');
        Member::updateOrCreate([
            'email' => $treasurer->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $treasurer->id,
            'first_name' => 'Treasurer',
            'last_name' => '',
            'email' => $treasurer->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Mission Coordinator
        $missionCoordinatorUserPayload = (new UserFactory)->raw();
        $missionCoordinator = User::updateOrCreate([
            'email' => "missions@{$domain}",
        ], array_merge($missionCoordinatorUserPayload, [
            'email' => "missions@{$domain}",
            'name' => 'Missions',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $missionCoordinator->assignRole('missions secretary');
        Member::updateOrCreate([
            'email' => $missionCoordinator->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $missionCoordinator->id,
            'first_name' => 'Missions',
            'last_name' => 'Desk',
            'email' => $missionCoordinator->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Organising Secretary
        $organisingSecretaryUserPayload = (new UserFactory)->raw();
        $organisingSecretary = User::updateOrCreate([
            'email' => "organizingsec@{$domain}",
        ], array_merge($organisingSecretaryUserPayload, [
            'email' => "organizingsec@{$domain}",
            'name' => 'Organising Secretary',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $organisingSecretary->assignRole('organising secretary');
        Member::updateOrCreate([
            'email' => $organisingSecretary->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $organisingSecretary->id,
            'first_name' => 'Organising',
            'last_name' => 'Secretary',
            'email' => $organisingSecretary->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Follow Up
        $followUpUserPayload = (new UserFactory)->raw();
        $followUp = User::updateOrCreate([
            'email' => "follow-up@{$domain}",
        ], array_merge($followUpUserPayload, [
            'email' => "follow-up@{$domain}",
            'name' => 'Follow Up',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $followUp->assignRole('follow-up secretary');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $followUp->id,
            'first_name' => 'Follow',
            'last_name' => 'Up',
            'email' => $followUp->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        // Prayer Desk
        $prayerDeskUserPayload = (new UserFactory)->raw();
        $prayerDesk = User::updateOrCreate([
            'email' => "prayerdesk@{$domain}",
        ], array_merge($prayerDeskUserPayload, [
            'email' => "prayerdesk@{$domain}",
            'name' => 'Prayer Desk',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
            'is_desk_email' => true,
        ]));
        $prayerDesk->assignRole('prayer secretary');
        Member::updateOrCreate([
            'email' => $prayerDesk->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $prayerDesk->id,
            'first_name' => 'Prayer',
            'last_name' => 'Desk',
            'email' => $prayerDesk->email,
            'approved' => false,
            'is_desk_email' => true,
        ]));

        $missionsCommitteeMembers = [
            [
                'first_name' => 'Committee',
                'last_name' => 'Member',
                'email' => "committee@{$domain}",
            ],
        ];

        foreach ($missionsCommitteeMembers as $missionsCommitteeMember) {

            $user = User::updateOrCreate([
                'email' => $missionsCommitteeMember['email'],
            ], array_merge((new UserFactory)->raw(), [
                'email' => $missionsCommitteeMember['email'],
                'name' => "{$missionsCommitteeMember['first_name']} {$missionsCommitteeMember['last_name']}",
                'password' => Utils::randomPassword(),
                'email_verified_at' => now(),
            ]));

            $user->assignRole([
                'member',
                'super admin',
                'missions committee member',
                'missions secretary',
            ]);

            Member::updateOrCreate([
                'email' => $user->email,
            ], array_merge((new MemberFactory)->raw(), [
                'user_id' => $user->id,
                'first_name' => $missionsCommitteeMember['first_name'],
                'last_name' => $missionsCommitteeMember['last_name'],
                'email' => $user->email,
                'approved' => false,
            ]));
        }

        // Student User
        $studentUserPayload = (new UserFactory)->raw();
        $studentUser = User::updateOrCreate([
            'email' => "students@{$domain}",
        ], array_merge($studentUserPayload, [
            'email' => "students@{$domain}",
            'name' => 'Student Approvals',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $approvalUser->assignRole('student');
        Student::updateOrCreate([
            'name' => $studentUser->name,
        ], [
            'name' => $studentUser->name,
            'user_id' => $studentUser->id,
        ]);
    }
}
