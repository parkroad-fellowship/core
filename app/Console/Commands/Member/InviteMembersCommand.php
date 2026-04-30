<?php

namespace App\Console\Commands\Member;

use App\Models\Member;
use App\Notifications\Member\SendCredentialsNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class InviteMembersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:invite-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the script to request members to join the app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting...');

        // Get total count for progress bar
        $totalMembers = Member::where([
            'approved' => true,
            'is_invited' => false,
        ])->count();

        if ($totalMembers === 0) {
            $this->info('No members to invite.');

            return;
        }

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalMembers);
        $progressBar->start();

        Member::query()
            ->where([
                'approved' => true,
                'is_invited' => false,
            ])
            ->where('is_desk_email', false)
            ->chunk(30, function ($members) use ($progressBar) {
                foreach ($members as $member) {
                    Notification::send(
                        $member,
                        new SendCredentialsNotification,
                    );

                    $member->update([
                        'is_invited' => true,
                    ]);

                    // Advance progress bar
                    $progressBar->advance();
                }
            });

        // Finish progress bar
        $progressBar->finish();
        $this->newLine();
        $this->info('Finished.');
    }
}
