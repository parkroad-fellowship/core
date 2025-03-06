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

        // Create the super admin user
        $superAdminUserPayload = (new UserFactory)->raw();
        $superAdmin = User::updateOrCreate([
            'email' => 'admin@parkroadfellowship.org',
        ], array_merge($superAdminUserPayload, [
            'email' => 'admin@parkroadfellowship.org',
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
        ]));

        $nancySuperAdminUserPayload = (new UserFactory)->raw();
        $nancySuperAdmin = User::updateOrCreate([
            'email' => 'nancy.muhungi@parkroadfellowship.org',
        ], array_merge($nancySuperAdminUserPayload, [
            'email' => 'nancy.muhungi@parkroadfellowship.org',
            'name' => 'Nancy Muhungi',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $nancySuperAdmin->assignRole('super admin');

        Member::updateOrCreate([
            'email' => $nancySuperAdmin->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $nancySuperAdmin->id,
            'first_name' => 'Nancy',
            'last_name' => 'Muhungi',
            'email' => $nancySuperAdmin->email,
        ]));

        // Approval User
        $approvalUserPayload = (new UserFactory)->raw();
        $approvalUser = User::updateOrCreate([
            'email' => 'approvals@parkroadfellowship.org',
        ], array_merge($approvalUserPayload, [
            'email' => 'approvals@parkroadfellowship.org',
            'name' => 'Store Approvals',
            'password' => bcrypt('password'),
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
        ]));

        // Chairperson
        $chairpersonUserPayload = (new UserFactory)->raw();
        $chairperson = User::updateOrCreate([
            'email' => 'chairperson@parkroadfellowship.org',
        ], array_merge($chairpersonUserPayload, [
            'email' => 'chairperson@parkroadfellowship.org',
            'name' => 'Chairperson',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $chairperson->assignRole('chairperson');

        Member::updateOrCreate([
            'email' => $chairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $chairperson->id,
            'first_name' => 'Chairperson',
            'last_name' => '',
            'email' => $chairperson->email,
        ]));

        // Vice Chairperson
        $viceChairpersonUserPayload = (new UserFactory)->raw();
        $viceChairperson = User::updateOrCreate([
            'email' => 'vicechair@parkroadfellowship.org',
        ], array_merge($viceChairpersonUserPayload, [
            'email' => 'vicechair@parkroadfellowship.org',
            'name' => 'Vice Chairperson',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $viceChairperson->assignRole('vice chairperson');
        Member::updateOrCreate([
            'email' => $viceChairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $viceChairperson->id,
            'first_name' => 'Vice',
            'last_name' => 'Chairperson',
            'email' => $viceChairperson->email,
        ]));

        // Treasurer
        $treasurerUserPayload = (new UserFactory)->raw();
        $treasurer = User::updateOrCreate([
            'email' => 'treasurer@parkroadfellowship.org',
        ], array_merge($treasurerUserPayload, [
            'email' => 'treasurer@parkroadfellowship.org',
            'name' => 'Treasurer',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $treasurer->assignRole('treasurer');
        Member::updateOrCreate([
            'email' => $treasurer->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $treasurer->id,
            'first_name' => 'Treasurer',
            'last_name' => '',
            'email' => $treasurer->email,
        ]));

        // Mission Coordinator
        $missionCoordinatorUserPayload = (new UserFactory)->raw();
        $missionCoordinator = User::updateOrCreate([
            'email' => 'missions@parkroadfellowship.org',
        ], array_merge($missionCoordinatorUserPayload, [
            'email' => 'missions@parkroadfellowship.org',
            'name' => 'Missions',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $missionCoordinator->assignRole('missions secretary');
        Member::updateOrCreate([
            'email' => $missionCoordinator->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $missionCoordinator->id,
            'first_name' => 'Missions',
            'last_name' => 'Desk',
            'email' => $missionCoordinator->email,
        ]));

        // Organising Secretary
        $organisingSecretaryUserPayload = (new UserFactory)->raw();
        $organisingSecretary = User::updateOrCreate([
            'email' => 'organizingsec@parkroadfellowship.org',
        ], array_merge($organisingSecretaryUserPayload, [
            'email' => 'organizingsec@parkroadfellowship.org',
            'name' => 'Organising Secretary',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $organisingSecretary->assignRole('organising secretary');
        Member::updateOrCreate([
            'email' => $organisingSecretary->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $organisingSecretary->id,
            'first_name' => 'Organising',
            'last_name' => 'Secretary',
            'email' => $organisingSecretary->email,
        ]));

        // Follow Up
        $followUpUserPayload = (new UserFactory)->raw();
        $followUp = User::updateOrCreate([
            'email' => 'follow-up@parkroadfellowship.org',
        ], array_merge($followUpUserPayload, [
            'email' => 'follow-up@parkroadfellowship.org',
            'name' => 'Follow Up',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $followUp->assignRole('follow-up secretary');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $followUp->id,
            'first_name' => 'Follow',
            'last_name' => 'Up',
            'email' => $followUp->email,
        ]));

        // Prayer Desk
        $followUpUserPayload = (new UserFactory)->raw();
        $followUp = User::updateOrCreate([
            'email' => 'prayerdesk@parkroadfellowship.org',
        ], array_merge($followUpUserPayload, [
            'email' => 'prayerdesk@parkroadfellowship.org',
            'name' => 'Prayer Desk',
            'password' => Utils::randomPassword(),
            'email_verified_at' => now(),
        ]));
        $followUp->assignRole('prayer secretary');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $followUp->id,
            'first_name' => 'Prayer',
            'last_name' => 'Desk',
            'email' => $followUp->email,
        ]));

        $missionsCommitteeMembers = [
            [
                'first_name' => 'Esther',
                'last_name' => 'Nyokabi Kabwere',
                'email' => 'esther.nyokabi.kabwere@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Leah',
                'last_name' => 'Muringo Muringi',
                'email' => 'leah.muringo.muringi@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Mwangi',
                'last_name' => 'Maina',
                'email' => 'mwangi.maina@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Nancy',
                'last_name' => 'Muhungi',
                'email' => 'nancy.muhungi@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Wilberforce',
                'last_name' => 'Thiribi',
                'email' => 'wilberforce.thiribi@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Miller',
                'last_name' => 'Adulu',
                'email' => 'adulu@parkroadfellowship.org',
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
                'missions committee member',
            ]);

            Member::updateOrCreate([
                'email' => $user->email,
            ], array_merge((new MemberFactory)->raw(), [
                'user_id' => $user->id,
                'first_name' => $missionsCommitteeMember['first_name'],
                'last_name' => $missionsCommitteeMember['last_name'],
                'email' => $user->email,
            ]));
        }

        $devTeamMembers = [
            [
                'first_name' => 'John',
                'last_name' => "Ng'ang'a",
                'email' => 'john.nganga@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Anthony',
                'last_name' => 'Kahihia',
                'email' => 'anthony.kahihia@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Veronicah',
                'last_name' => 'Maina',
                'email' => 'veronicah.njuguna@parkroadfellowship.org',
            ],
            [
                'first_name' => 'Nancy',
                'last_name' => 'Muhungi',
                'email' => 'nancy.muhungi@parkroadfellowship.org',
            ],
        ];

        foreach ($devTeamMembers as $devTeamMember) {

            $user = User::updateOrCreate([
                'email' => $devTeamMember['email'],
            ], array_merge((new UserFactory)->raw(), [
                'email' => $devTeamMember['email'],
                'name' => "{$devTeamMember['first_name']} {$devTeamMember['last_name']}",
                'password' => 'dev-team-pass',
                'email_verified_at' => now(),
            ]));

            $user->assignRole([
                'member',
                'missions committee member',
            ]);

            Member::updateOrCreate([
                'email' => $user->email,
            ], array_merge((new MemberFactory)->raw(), [
                'user_id' => $user->id,
                'first_name' => $devTeamMember['first_name'],
                'last_name' => $devTeamMember['last_name'],
                'email' => $user->email,
            ]));
        }

        // Student User
        $studentUserPayload = (new UserFactory)->raw();
        $studentUser = User::updateOrCreate([
            'email' => 'students@parkroadfellowship.org',
        ], array_merge($studentUserPayload, [
            'email' => 'students@parkroadfellowship.org',
            'name' => 'Student Approvals',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]));
        $approvalUser->assignRole('student');
        Student::create([
            'name' => $studentUser->name,
            'user_id' => $studentUser->id,
        ]);
    }
}
