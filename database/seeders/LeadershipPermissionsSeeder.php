<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadershipPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chairpersons = User::whereIn('email', AppSetting::get('desk_emails.chairpersons', []))->get();

        if ($chairpersons) {
            $chairpersons->each(function ($user) {
                $user->assignRole('chairperson');
            });
        }

        $viceChairpersons = User::whereIn('email', AppSetting::get('desk_emails.vice_chairpersons', []))->get();
        if ($viceChairpersons) {
            $viceChairpersons->each(function ($user) {
                $user->assignRole('vice chairperson');
            });
        }

        $organisingSecretaries = User::whereIn('email', AppSetting::get('desk_emails.organising_secretary', []))->get();
        if ($organisingSecretaries) {
            $organisingSecretaries->each(function ($user) {
                $user->assignRole('organising secretary');
            });
        }

        $missionSecretaries = User::whereIn('email', AppSetting::get('desk_emails.missions', []))->get();
        if ($missionSecretaries) {
            $missionSecretaries->each(function ($user) {
                $user->assignRole('missions secretary');
                $user->assignRole('follow-up secretary');
            });
        }

        $followUpSecretaries = User::whereIn('email', AppSetting::get('desk_emails.follow_up', []))->get();
        if ($followUpSecretaries) {
            $followUpSecretaries->each(function ($user) {
                $user->assignRole('follow-up secretary');
            });
        }

        $treasurers = User::whereIn('email', AppSetting::get('desk_emails.treasurers', []))->get();
        if ($treasurers) {
            $treasurers->each(function ($user) {
                $user->assignRole('treasurer');
            });
        }

        $prayerSecretaries = User::whereIn('email', AppSetting::get('desk_emails.prayer', []))->get();
        if ($prayerSecretaries) {
            $prayerSecretaries->each(function ($user) {
                $user->assignRole('prayer secretary');
            });
        }

        $musicSecretaries = User::whereIn('email', AppSetting::get('desk_emails.music', []))->get();
        if ($musicSecretaries) {
            $musicSecretaries->each(function ($user) {
                $user->assignRole('music secretary');
            });
        }

        // $campCommitteeMembers = User::whereIn('email', AppSetting::get('desk_emails.camp_committee', []))->get();
        // if ($campCommitteeMembers) {
        //     $campCommitteeMembers->each(function ($user) {
        //         $user->assignRole('camp committee member');
        //     });
        // }
    }
}
