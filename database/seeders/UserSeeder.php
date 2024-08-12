<?php

namespace Database\Seeders;

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
        ]));
        $superAdmin->assignRole('super admin');

        Member::updateOrCreate([
            'email' => $superAdmin->email,
        ], array_merge((new MemberFactory)->raw(), [
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
        ]));
        $approvalUser->assignRole('super admin');
        Member::updateOrCreate([
            'email' => $approvalUser->email,
        ], array_merge((new MemberFactory)->raw(), [
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
        ]));
        $chairperson->assignRole('chairperson');

        Member::updateOrCreate([
            'email' => $chairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
            'email' => $chairperson->email,
            'first_name' => $chairperson->name,
        ]));

        // Vice Chairperson
        $viceChairpersonUserPayload = (new UserFactory)->raw();
        $viceChairperson = User::updateOrCreate([
            'email' => 'vicechairperson@parkroadfellowship.org',
        ], array_merge($viceChairpersonUserPayload, [
            'email' => 'vicechairperson@parkroadfellowship.org',
            'name' => 'Vice Chairperson',
        ]));
        $viceChairperson->assignRole('vice chairperson');
        Member::updateOrCreate([
            'email' => $viceChairperson->email,
        ], array_merge((new MemberFactory)->raw(), [
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
        ]));
        $treasurer->assignRole('treasurer');
        Member::updateOrCreate([
            'email' => $treasurer->email,
        ], array_merge((new MemberFactory)->raw(), [
            'email' => $treasurer->email,
            'first_name' => $treasurer->name,
        ]));

        // Mission Coordinator
        $missionCoordinatorUserPayload = (new UserFactory)->raw();
        $missionCoordinator = User::updateOrCreate([
            'email' => 'missioncoordinator@parkroadfellowship.org',
        ], array_merge($missionCoordinatorUserPayload, [
            'email' => 'missioncoordinator@parkroadfellowship.org',
            'name' => 'Mission Coordinator',
        ]));
        $missionCoordinator->assignRole('mission coordinator');
        Member::updateOrCreate([
            'email' => $missionCoordinator->email,
        ], array_merge((new MemberFactory)->raw(), [
            'email' => $missionCoordinator->email,
            'first_name' => $missionCoordinator->name,
        ]));

        // Vice Mission Coordinator
        $viceMissionCoordinatorUserPayload = (new UserFactory)->raw();
        $viceMissionCoordinator = User::updateOrCreate([
            'email' => 'vicemissioncoordinator@parkroadfellowship.org',
        ], array_merge($viceMissionCoordinatorUserPayload, [
            'email' => 'vicemissioncoordinator@parkroadfellowship.org',
            'name' => 'Vice Mission Coordinator',
        ]));
        $viceMissionCoordinator->assignRole('vice mission coordinator');
        Member::updateOrCreate([
            'email' => $viceMissionCoordinator->email,
        ], array_merge((new MemberFactory)->raw(), [
            'email' => $viceMissionCoordinator->email,
            'first_name' => $viceMissionCoordinator->name,
        ]));

        // Organising Secretary
        $organisingSecretaryUserPayload = (new UserFactory)->raw();
        $organisingSecretary = User::updateOrCreate([
            'email' => 'organisingsecretary@parkroadfellowship.org',
        ], array_merge($organisingSecretaryUserPayload, [
            'email' => 'organisingsecretary@parkroadfellowship.org',
            'name' => 'Organising Secretary',
        ]));
        $organisingSecretary->assignRole('organising secretary');
        Member::updateOrCreate([
            'email' => $organisingSecretary->email,
        ], array_merge((new MemberFactory)->raw(), [
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
        ]));
        $followUp->assignRole('follow up');
        Member::updateOrCreate([
            'email' => $followUp->email,
        ], array_merge((new MemberFactory)->raw(), [
            'email' => $followUp->email,
            'first_name' => $followUp->name,
        ]));
    }
}
