<?php

namespace Database\Seeders;

use App\Helpers\Utils;
use App\Models\Member;
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
        ]));
        $superAdmin->assignRole('super admin');

        Member::updateOrCreate([
            'email' => $superAdmin->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $superAdmin->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $superAdmin->email,
            'first_name' => $superAdmin->name,
        ]));

        // Approval User
        $approvalUserPayload = (new UserFactory)->raw();
        $approvalUser = User::updateOrCreate([
            'email' => 'approvals@parkroadfellowship.org',
        ], array_merge($approvalUserPayload, [
            'email' => 'approvals@parkroadfellowship.org',
            'name' => 'Store Approvals',
            'password' => bcrypt('password'),
        ]));
        $approvalUser->assignRole('super admin');
        Member::updateOrCreate([
            'email' => $approvalUser->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $approvalUser->id,
            'first_name' => 'Store',
            'last_name' => 'Approvals',
            'email' => $approvalUser->email,
            'first_name' => $approvalUser->name,
        ]));

        // Chairperson
        $chairpersonUserPayload = (new UserFactory)->raw();
        $chairperson = User::updateOrCreate([
            'email' => 'chaiperson@parkroadfellowship.org',
        ], array_merge($chairpersonUserPayload, [
            'email' => 'chairperson@parkroadfellowship.org',
            'name' => 'Chairperson',
            'password' => Utils::randomPassword(),
        ]));
        $chairperson->assignRole('chairperson');

        Member::updateOrCreate([
            'email' => $chairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $chairperson->id,
            'first_name' => 'Chairperson',
            'last_name' => '',
            'email' => $chairperson->email,
            'first_name' => $chairperson->name,
        ]));

        // Vice Chairperson
        $viceChairpersonUserPayload = (new UserFactory)->raw();
        $viceChairperson = User::updateOrCreate([
            'email' => 'vicechair@parkroadfellowship.org',
        ], array_merge($viceChairpersonUserPayload, [
            'email' => 'vicechair@parkroadfellowship.org',
            'name' => 'Vice Chairperson',
            'password' => Utils::randomPassword(),
        ]));
        $viceChairperson->assignRole('vice chairperson');
        Member::updateOrCreate([
            'email' => $viceChairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $viceChairperson->id,
            'first_name' => 'Vice',
            'last_name' => 'Chairperson',
            'email' => $viceChairperson->email,
            'first_name' => $viceChairperson->name,
        ]));

        // Treasurer
        $treasurerUserPayload = (new UserFactory)->raw();
        $treasurer = User::updateOrCreate([
            'email' => 'treasurer@parkroadfellowship.org',
        ], array_merge($treasurerUserPayload, [
            'email' => 'treasurer@parkroadfellowship.org',
            'name' => 'Treasurer',
            'password' => Utils::randomPassword(),
        ]));
        $treasurer->assignRole('treasurer');
        Member::updateOrCreate([
            'email' => $treasurer->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $treasurer->id,
            'first_name' => 'Treasurer',
            'last_name' => '',
            'email' => $treasurer->email,
            'first_name' => $treasurer->name,
        ]));

        // Mission Coordinator
        $missionCoordinatorUserPayload = (new UserFactory)->raw();
        $missionCoordinator = User::updateOrCreate([
            'email' => 'missions@parkroadfellowship.org',
        ], array_merge($missionCoordinatorUserPayload, [
            'email' => 'missions@parkroadfellowship.org',
            'name' => 'Missions',
            'password' => Utils::randomPassword(),
        ]));
        $missionCoordinator->assignRole('mission coordinator');
        Member::updateOrCreate([
            'email' => $missionCoordinator->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $missionCoordinator->id,
            'first_name' => 'Missions',
            'last_name' => 'Desk',
            'email' => $missionCoordinator->email,
            'first_name' => $missionCoordinator->name,
        ]));

        // Organising Secretary
        $organisingSecretaryUserPayload = (new UserFactory)->raw();
        $organisingSecretary = User::updateOrCreate([
            'email' => 'organizingsec@parkroadfellowship.org',
        ], array_merge($organisingSecretaryUserPayload, [
            'email' => 'organizingsec@parkroadfellowship.org',
            'name' => 'Organising Secretary',
            'password' => Utils::randomPassword(),
        ]));
        $organisingSecretary->assignRole('organising secretary');
        Member::updateOrCreate([
            'email' => $organisingSecretary->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $organisingSecretary->id,
            'first_name' => 'Organising',
            'last_name' => 'Secretary',
            'email' => $organisingSecretary->email,
            'first_name' => $organisingSecretary->name,
        ]));

        // Follow Up
        $followUpUserPayload = (new UserFactory)->raw();
        $followUp = User::updateOrCreate([
            'email' => 'follow-up@parkroadfellowship.org',
        ], array_merge($followUpUserPayload, [
            'email' => 'follow-up@parkroadfellowship.org',
            'name' => 'Follow Up',
            'password' => Utils::randomPassword(),
        ]));
        $followUp->assignRole('follow up');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $followUp->id,
            'first_name' => 'Follow',
            'last_name' => 'Up',
            'email' => $followUp->email,
            'first_name' => $followUp->name,
        ]));

        // Prayer Desk
        $followUpUserPayload = (new UserFactory)->raw();
        $followUp = User::updateOrCreate([
            'email' => 'prayerdesk@parkroadfellowship.org',
        ], array_merge($followUpUserPayload, [
            'email' => 'prayerdesk@parkroadfellowship.org',
            'name' => 'Prayer Desk',
            'password' => Utils::randomPassword(),
        ]));
        $followUp->assignRole('prayer');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'user_id' => $followUp->id,
            'first_name' => 'Prayer',
            'last_name' => 'Desk',
            'email' => $followUp->email,
            'first_name' => $followUp->name,
        ]));
    }
}
