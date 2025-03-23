<?php

namespace App\Console\Commands\Member;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Console\Command;

class LinkToNetworkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:link-to-network';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link newly created members to the global courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Linking all members to global courses.');

        // Attach the course to the `All` group
        $allGroup = Group::where('name', config('prf.app.global_group'))->first();

        $this->info('Completed linking users to the course.');
        // Attach the users to the `All` group
        foreach (
            Member::query()
                // ->where([
                //     'approved' => true,
                //     'is_invited' => false,
                // ])
                ->cursor() as $member
        ) {
            GroupMember::updateOrCreate([
                'group_id' => $allGroup->id,
                'member_id' => $member->id,
            ], [
                'group_id' => $allGroup->id,
                'member_id' => $member->id,
                'start_date' => now(),
            ]);
        }

        $this->info('Completed attaching users to the `All` group.');
    }
}
