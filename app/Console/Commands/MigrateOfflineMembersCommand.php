<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Models\MissionOfflineMember;
use Illuminate\Console\Command;

class MigrateOfflineMembersCommand extends Command
{
    protected $signature = 'app:migrate-offline-members {--dry-run : Show what would be migrated without making changes}';

    protected $description = 'Migrate offline_members JSON data from missions table to mission_offline_members table';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN — no changes will be made.');
        }

        $missions = Mission::query()
            ->whereRaw("offline_members::text != '[]'")
            ->whereNotNull('offline_members')
            ->get();

        $this->info("Found {$missions->count()} missions with offline members.");

        $totalMigrated = 0;

        foreach ($missions as $mission) {
            $offlineMembers = $mission->getRawOriginal('offline_members');
            $offlineMembers = json_decode($offlineMembers, true);

            if (! is_array($offlineMembers) || empty($offlineMembers)) {
                continue;
            }

            $this->info("Mission {$mission->ulid}: {$mission->theme} — ".count($offlineMembers).' offline member(s)');

            foreach ($offlineMembers as $member) {
                // Handle both formats:
                // 1. Object format from Filament: {"name": "John", "phone_number": "+254..."}
                // 2. String format from API: "John Doe"
                if (is_array($member)) {
                    $name = $member['name'] ?? 'Unknown';
                    $phone = $member['phone_number'] ?? $member['phone'] ?? null;
                } else {
                    $name = (string) $member;
                    $phone = null;
                }

                $this->line("  → {$name}".($phone ? " ({$phone})" : ''));

                if (! $dryRun) {
                    MissionOfflineMember::create([
                        'mission_id' => $mission->id,
                        'name' => $name,
                        'phone' => $phone,
                    ]);
                }

                $totalMigrated++;
            }
        }

        $action = $dryRun ? 'Would migrate' : 'Migrated';
        $this->info("{$action} {$totalMigrated} offline member(s) from {$missions->count()} mission(s).");

        return self::SUCCESS;
    }
}
