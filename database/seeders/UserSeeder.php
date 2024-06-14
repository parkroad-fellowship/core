<?php

namespace Database\Seeders;

use App\Models\User;
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
        $superAdminUserPayload = (new UserFactory())->raw();
        $superAdmin = User::updateOrCreate([
            'email' => 'admin@parkroadfellowship.org',
        ], array_merge($superAdminUserPayload, [
            'email' => 'admin@parkroadfellowship.org',
            'name' => 'Super Admin',
        ]));
        $superAdmin->assignRole('super admin');

        // Approval User
        $approvalUserPayload = (new UserFactory())->raw();
        $approvalUser = User::updateOrCreate([
            'email' => 'approvals@parkroadfellowship.org',
        ], array_merge($superAdminUserPayload, [
            'email' => 'approvals@parkroadfellowship.org',
            'name' => 'Store Approvals',
        ]));
        $approvalUser->assignRole('super admin');

        // chairperson
        $chairpersonUserPayload = (new UserFactory())->raw();
        $chairperson = User::updateOrCreate([
            'email' => 'chaiperson@parkroadfellowship.org',
        ], array_merge($chairpersonUserPayload, [
            'email' => 'chairperson@parkroadfellowship.org',
            'name' => 'Chairperson',
        ]));
        $chairperson->assignRole('chairperson');

        // vice chairperson
        $viceChairpersonUserPayload = (new UserFactory())->raw();
        $viceChairperson = User::updateOrCreate([
            'email' => 'vicechairperson@parkroadfellowship.org',
        ], array_merge($viceChairpersonUserPayload, [
            'email' => 'vicechairperson@parkroadfellowship.org',
            'name' => 'Vice Chairperson',
        ]));
        $viceChairperson->assignRole('vice chairperson');

        // treasurer
        $treasurerUserPayload = (new UserFactory())->raw();
        $treasurer = User::updateOrCreate([
            'email' => 'treasurer@parkroadfellowship.org',
        ], array_merge($treasurerUserPayload, [
            'email' => 'treasurer@parkroadfellowship.org',
            'name' => 'Treasurer',
        ]));
        $treasurer->assignRole('treasurer');

        // mission coordinator
        $missionCoordinatorUserPayload = (new UserFactory())->raw();
        $missionCoordinator = User::updateOrCreate([
            'email' => 'missioncoordinator@parkroadfellowship.org',
        ], array_merge($missionCoordinatorUserPayload, [
            'email' => 'missioncoordinator@parkroadfellowship.org',
            'name' => 'Mission Coordinator',
        ]));
        $missionCoordinator->assignRole('mission coordinator');

        // vice mission coordinator
        $viceMissionCoordinatorUserPayload = (new UserFactory())->raw();
        $viceMissionCoordinator = User::updateOrCreate([
            'email' => 'vicemissioncoordinator@parkroadfellowship.org',
        ], array_merge($viceMissionCoordinatorUserPayload, [
            'email' => 'vicemissioncoordinator@parkroadfellowship.org',
            'name' => 'Vice Mission Coordinator',
        ]));
        $viceMissionCoordinator->assignRole('vice mission coordinator');

        // organising secretary
        $organisingSecretaryUserPayload = (new UserFactory())->raw();
        $organisingSecretary = User::updateOrCreate([
            'email' => 'organisingsecretary@parkroadfellowship.org',
        ], array_merge($organisingSecretaryUserPayload, [
            'email' => 'organisingsecretary@parkroadfellowship.org',
            'name' => 'Organising Secretary',
        ]));
        $organisingSecretary->assignRole('organising secretary');
    }
}
