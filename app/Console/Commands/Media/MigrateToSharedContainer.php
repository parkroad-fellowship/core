<?php

namespace App\Console\Commands\Media;

use App\Models\Media;
use App\Models\MissionSocialMediaPost;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateToSharedContainer extends Command
{
    protected $signature = 'media:migrate-to-shared-container
        {--tenant= : Only migrate a single tenant ULID}
        {--target-container=gospel-flood-core-container : Target Azure container (defaults to the shared container)}
        {--target-prefix=gospel-flood-core : Leading path segment used in the target container}
        {--dry-run : Report what would happen without changing storage or the database}
        {--keep-source : Keep source blobs after a successful copy instead of deleting them}
        {--batch=100 : Number of media rows processed per chunk}';

    protected $description = 'Backfill tenant ids and migrate media blobs from legacy per-tenant containers into a shared container using tenant-scoped paths';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $batch = (int) $this->option('batch');
        $targetContainer = $this->targetContainer();
        $targetPrefix = (string) $this->option('target-prefix');

        $backfill = $this->backfillTenantIds($dryRun, $batch);
        $this->line('');

        $migrated = 0;
        $failed = 0;
        $skipped = 0;
        $urlsRewritten = 0;

        foreach ($this->configuredTenants() as $tenantId => $entry) {
            $container = Arr::get($entry, 'container');
            $prefix = Arr::get($entry, 'prefix', 'prf-core');

            if (blank($container)) {
                $this->error("Tenant [{$tenantId}] has no container configured; skipping.");

                continue;
            }

            $this->info("Migrating tenant [{$tenantId}] from container [{$container}] → [{$targetContainer}]");

            $result = $this->migrateTenant(
                tenantId: $tenantId,
                mediaIds: $backfill['mediaIdsByTenant'][$tenantId] ?? [],
                container: $container,
                prefix: $prefix,
                targetContainer: $targetContainer,
                targetPrefix: $targetPrefix,
                dryRun: $dryRun,
                batch: $batch,
            );

            $migrated += $result['migrated'];
            $skipped += $result['skipped'];
            $failed += $result['failed'];
            $urlsRewritten += $this->rewritePersistedUrls(
                tenantId: $tenantId,
                container: $container,
                prefix: $prefix,
                targetContainer: $targetContainer,
                targetPrefix: $targetPrefix,
                md5Dirs: $result['md5Dirs'],
                dryRun: $dryRun,
            );
        }

        $this->newLine();
        $this->table([
            'Backfilled tenant ids',
            'Blobs migrated',
            'Blobs skipped',
            'Blobs failed',
            'Persisted URLs rewritten',
        ], [[$backfill['backfilled'], $migrated, $skipped, $failed, $urlsRewritten]]);

        if ($backfill['unresolved'] > 0) {
            $this->warn(
                "{$backfill['unresolved']} media rows could not be resolved to a tenant and remain on the legacy path.",
            );
        }

        if ($dryRun) {
            $this->warn('Dry run completed — no files or records were changed.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredTenants(): array
    {
        $map = (array) config('tenant-media-containers');

        if ($tenant = $this->option('tenant')) {
            if (!array_key_exists($tenant, $map)) {
                $this->error("Tenant [{$tenant}] is not present in config('tenant-media-containers').");

                return [];
            }

            return [$tenant => $map[$tenant]];
        }

        return $map;
    }

    private function targetContainer(): string
    {
        return (string) $this->option('target-container');
    }

    private function azureDisk(string $container): Filesystem
    {
        return Storage::build([
            'driver' => 'azure-storage-blob',
            'connection_string' => config('filesystems.disks.azure.connection_string'),
            'container' => $container,
        ]);
    }

    /**
     * @return array{backfilled: int, unresolved: int, mediaIdsByTenant: array<string, list<int>>}
     */
    private function backfillTenantIds(bool $dryRun, int $batch): array
    {
        $this->info('Backfilling tenant ids on media rows...');

        $stats = ['backfilled' => 0, 'unresolved' => 0, 'mediaIdsByTenant' => []];

        Media::query()
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(
                $batch,
                function ($chunk) use (&$stats, $dryRun) {
                    $parents = $this->loadParents($chunk);

                    foreach ($chunk as $media) {
                        $parent = $parents[$media->model_type][$media->model_id] ?? null;
                        $tenantId = $parent?->tenant_id;

                        if (blank($tenantId)) {
                            $stats['unresolved']++;

                            continue;
                        }

                        $stats['mediaIdsByTenant'][$tenantId][] = $media->id;

                        if (!$dryRun) {
                            $media->forceFill(['tenant_id' => $tenantId])->saveQuietly();
                        }

                        $stats['backfilled']++;
                    }
                },
                column: 'id',
            );

        $this->info("Backfilled {$stats['backfilled']} media rows; {$stats['unresolved']} unresolved.");

        return $stats;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Media>  $chunk
     * @return array<string, array<int|string, mixed>>
     */
    private function loadParents($chunk): array
    {
        $parents = [];

        $chunk
            ->groupBy('model_type')
            ->each(function ($group, $modelType) use (&$parents) {
                $modelClass = Relation::getMorphedModel($modelType) ?? $modelType;

                if (!class_exists($modelClass)) {
                    return;
                }

                $query = $modelClass::query();

                if (method_exists($modelClass, 'withTrashed')) {
                    $query->withTrashed();
                }

                $parents[$modelType] = $query->whereKey($group->pluck('model_id'))->get()->keyBy('id');
            });

        return $parents;
    }

    /**
     * @param  list<int>  $mediaIds
     * @return array{migrated: int, skipped: int, failed: int, md5Dirs: array<string, true>}
     */
    private function migrateTenant(
        string $tenantId,
        array $mediaIds,
        string $container,
        string $prefix,
        string $targetContainer,
        string $targetPrefix,
        bool $dryRun,
        int $batch,
    ): array {
        $sourceDisk = $this->azureDisk($container);
        $targetDisk = $this->azureDisk($targetContainer);

        $environment = App::environment();
        $stats = ['migrated' => 0, 'skipped' => 0, 'failed' => 0, 'md5Dirs' => []];

        $ids = collect($mediaIds)
            ->merge(Media::query()->where('tenant_id', $tenantId)->pluck('id'))
            ->unique()
            ->sort()
            ->values();

        foreach ($ids->chunk($batch) as $chunk) {
            foreach ($chunk as $id) {
                $md5Dir = md5($id);
                $sourceRoot = "{$prefix}/{$environment}/media-library/{$md5Dir}/";
                $targetRoot = "{$targetPrefix}/{$environment}/media-library/{$tenantId}/{$md5Dir}/";

                try {
                    $result = $this->copyDirectory($sourceDisk, $targetDisk, $sourceRoot, $targetRoot, $dryRun);
                } catch (Throwable $exception) {
                    $stats['failed']++;
                    $this->error("Failed to migrate media #{$id} ({$sourceRoot}): {$exception->getMessage()}");

                    continue;
                }

                if ($result === 'migrated') {
                    $stats['migrated']++;
                    $stats['md5Dirs'][$md5Dir] = true;

                    continue;
                }

                if ($result === 'skipped') {
                    $stats['skipped']++;
                    $stats['md5Dirs'][$md5Dir] = true;

                    continue;
                }

                if ($result === 'empty') {
                    $stats['skipped']++;

                    continue;
                }

                $stats['failed']++;
                $this->error("Failed to migrate media #{$id} ({$sourceRoot})");
            }
        }

        return $stats;
    }

    private function copyDirectory(
        Filesystem $sourceDisk,
        Filesystem $targetDisk,
        string $sourceRoot,
        string $targetRoot,
        bool $dryRun,
    ): string {
        $files = $sourceDisk->allFiles($sourceRoot);

        if (empty($files)) {
            return 'empty';
        }

        $alreadyThere = true;

        foreach ($files as $sourcePath) {
            $relativePath = substr($sourcePath, strlen($sourceRoot));
            $targetPath = $targetRoot . $relativePath;

            if ($targetDisk->exists($targetPath) && $targetDisk->size($targetPath) === $sourceDisk->size($sourcePath)) {
                continue;
            }

            $alreadyThere = false;

            if ($dryRun) {
                continue;
            }

            $this->writeStream($targetDisk, $targetPath, $sourceDisk, $sourcePath);
        }

        if ($dryRun) {
            return $alreadyThere ? 'skipped' : 'migrated';
        }

        if ($alreadyThere) {
            return 'skipped';
        }

        if (!$this->option('keep-source')) {
            $sourceDisk->delete($files);
        }

        return 'migrated';
    }

    private function writeStream(
        Filesystem $targetDisk,
        string $targetPath,
        Filesystem $sourceDisk,
        string $sourcePath,
    ): void {
        $stream = $sourceDisk->readStream($sourcePath);

        if (!$stream) {
            throw new \RuntimeException("Unable to read source blob [{$sourcePath}]");
        }

        try {
            $targetDisk->writeStream($targetPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function rewritePersistedUrls(
        string $tenantId,
        string $container,
        string $prefix,
        string $targetContainer,
        string $targetPrefix,
        array $md5Dirs,
        bool $dryRun,
    ): int {
        if ($md5Dirs === []) {
            return 0;
        }

        $updated = 0;

        MissionSocialMediaPost::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->chunkById(
                100,
                function ($posts) use (
                    &$updated,
                    $container,
                    $prefix,
                    $targetContainer,
                    $targetPrefix,
                    $tenantId,
                    $md5Dirs,
                    $dryRun,
                ) {
                    foreach ($posts as $post) {
                        $changed = false;

                        $imageUrls = $post->image_urls ?? [];
                        $rewrittenImages = array_map(function ($url) use (
                            &$changed,
                            $container,
                            $prefix,
                            $targetContainer,
                            $targetPrefix,
                            $tenantId,
                            $md5Dirs,
                        ) {
                            $rewritten = $this->rewriteAzureUrl(
                                $url,
                                container: $container,
                                prefix: $prefix,
                                targetContainer: $targetContainer,
                                targetPrefix: $targetPrefix,
                                tenantId: $tenantId,
                                md5Dirs: $md5Dirs,
                            );

                            if ($rewritten !== null) {
                                $changed = true;

                                return $rewritten;
                            }

                            return $url;
                        }, $imageUrls);

                        $videoUrl = $post->video_url
                            ? $this->rewriteAzureUrl(
                                $post->video_url,
                                container: $container,
                                prefix: $prefix,
                                targetContainer: $targetContainer,
                                targetPrefix: $targetPrefix,
                                tenantId: $tenantId,
                                md5Dirs: $md5Dirs,
                            )
                            : null;

                        if ($videoUrl !== null) {
                            $changed = true;
                        }

                        if (!$changed) {
                            continue;
                        }

                        $updated++;

                        if ($dryRun) {
                            continue;
                        }

                        $post->forceFill([
                            'image_urls' => $rewrittenImages,
                            'video_url' => $videoUrl ?? $post->video_url,
                        ])->saveQuietly();
                    }
                },
                column: 'id',
            );

        return $updated;
    }

    private function rewriteAzureUrl(
        string $url,
        string $container,
        string $prefix,
        string $targetContainer,
        string $targetPrefix,
        string $tenantId,
        array $md5Dirs,
    ): ?string {
        $parts = parse_url($url);

        if (!isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return null;
        }

        $path = ltrim($parts['path'], '/');
        $segments = explode('/', $path);

        if (Arr::first($segments) !== $container) {
            return null;
        }

        $blobPath = implode('/', array_slice($segments, 1));
        $leading = "{$prefix}/";

        if (!str_starts_with($blobPath, $leading)) {
            return null;
        }

        $rest = substr($blobPath, strlen($leading));

        if (!preg_match('#^([^/]+)/media-library/([0-9a-f]{32})(/|$)#', $rest, $matches)) {
            return null;
        }

        if (!isset($md5Dirs[$matches[2]])) {
            return null;
        }

        $newBlobPath =
            "{$targetPrefix}/{$matches[1]}/media-library/{$tenantId}/{$matches[2]}/"
            . substr($rest, strlen($matches[0]));

        $authority = $parts['host'];

        if (isset($parts['port'])) {
            $authority .= ':' . $parts['port'];
        }

        return "{$parts['scheme']}://{$authority}/{$targetContainer}/{$newBlobPath}";
    }
}
