<?php

namespace App\Console\Commands\MissionSession;

use App\Models\MissionSession;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class AddTestMediaFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-test-media-file-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add test media file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = '/Users/adulu/Work/PRF/SuperApp/API/MediaTests/Mugoiri_Girls_Sunday_Service_copy.m4a';
        $processedPath = storage_path('app/temp/processed_'.basename($filePath).'.wav'); // Add .wav extension

        $this->info('Processing audio file: '.$filePath);
        // Ensure the temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        // Modified command to output WAV format
        $command = "ffmpeg -i \"{$filePath}\" -ar 16000 -ac 1 \"{$processedPath}\"";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to process audio file');

            return;
        }

        $this->info('Audio file processed successfully');

        // Use the processed file instead of the original
        $filePath = $processedPath;

        $this->info('Adding media file: '.$filePath);

        $missionSession = MissionSession::query()
            ->where('ulid', '01jhp8zkarryrc12ftvg5q7svt')
            ->firstOrFail();

        $this->info('Adding media file to mission session: '.$missionSession->ulid);

        set_time_limit(0); // 0 = no limit (in seconds)
        $missionSession
            ->addMedia($filePath)
            ->toMediaCollection(
                Arr::first(
                    MissionSession::MEDIA_COLLECTIONS,
                    fn ($collection) => $collection === 'session-audios'
                )
            );

        $this->info('Done');
    }
}
