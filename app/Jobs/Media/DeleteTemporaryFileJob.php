<?php

namespace App\Jobs\Media;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Storage;

#[Queue('default')]
#[Tries(3)]
class DeleteTemporaryFileJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $disks,
        public string $path,
    ) {}

    public function handle(): void
    {
        foreach ($this->disks as $disk) {
            Storage::disk($disk)->delete($this->path);
        }
    }
}
