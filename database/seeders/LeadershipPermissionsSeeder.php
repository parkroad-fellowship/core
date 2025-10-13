<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class LeadershipPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $chairpersons = User::whereIn('email', config('prf.app.chairpersons_desk.emails'))->get();

        if ($chairpersons) {
            $chairpersons->each(function ($user) {
                $user->assignRole('chairperson');
            });
        }

        $viceChairpersons = User::whereIn('email', config('prf.app.vice_chairpersons_desk.emails'))->get();
        if ($viceChairpersons) {
            $viceChairpersons->each(function ($user) {
                $user->assignRole('vice chairperson');
            });
        }

        $organisingSecretaries = User::whereIn('email', config('prf.app.organising_secretary_desk.emails'))->get();
        if ($organisingSecretaries) {
            $organisingSecretaries->each(function ($user) {
                $user->assignRole('organising secretary');
            });
        }

        $missionSecretaries = User::whereIn('email', config('prf.app.missions_desk.emails'))->get();
        if ($missionSecretaries) {
            $missionSecretaries->each(function ($user) {
                $user->assignRole('missions secretary');
            });
        }

        $followUpSecretaries = User::whereIn('email', config('prf.app.follow_up_desk.emails'))->get();
        if ($followUpSecretaries) {
            $followUpSecretaries->each(function ($user) {
                $user->assignRole('follow-up secretary');
            });
        }

        $treasurers = User::whereIn('email', config('prf.app.treasurers_desk.emails'))->get();
        if ($treasurers) {
            $treasurers->each(function ($user) {
                $user->assignRole('treasurer');
            });
        }

        $prayerSecretaries = User::whereIn('email', config('prf.app.prayer_desk.emails'))->get();
        if ($prayerSecretaries) {
            $prayerSecretaries->each(function ($user) {
                $user->assignRole('prayer secretary');
            });
        }

        $musicSecretaries = User::whereIn('email', config('prf.app.music_desk.emails'))->get();
        if ($musicSecretaries) {
            $musicSecretaries->each(function ($user) {
                $user->assignRole('music secretary');
            });
        }

        $campCommitteeMembers = User::whereIn('email', config('prf.app.camp_committee.2025-2026.emails'))->get();
        if ($campCommitteeMembers) {
            $campCommitteeMembers->each(function ($user) {
                $user->assignRole('camp committee member');
            });
        }

        $dinel = User::whereIn('email', ['dinel.njoroge.wangari@parkroadfellowship.org'])->get();
        if ($dinel) {
            $dinel->each(function ($user) {
                $user->assignRole('missions committee member');
            });
        }
    }
}
