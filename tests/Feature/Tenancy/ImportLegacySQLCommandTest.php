<?php

use App\Console\Commands\Tenant\ImportLegacySQLCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Mockery;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

function buildImportCommandWithContainer(?string $container): ImportLegacySQLCommand
{
    $command = new ImportLegacySQLCommand;
    $command->setLaravel(app());

    $input = Mockery::mock(InputInterface::class);
    $input->shouldReceive('getOption')->with('backup-container')->andReturn($container);

    $inputProperty = (new ReflectionClass(Command::class))->getProperty('input');
    $inputProperty->setAccessible(true);
    $inputProperty->setValue($command, $input);

    $outputProperty = (new ReflectionClass(Command::class))->getProperty('output');
    $outputProperty->setAccessible(true);
    $outputProperty->setValue($command, new BufferedOutput);

    return $command;
}

function invokeGetBackupStorage(ImportLegacySQLCommand $command, string $disk): Filesystem
{
    $method = (new ReflectionClass(ImportLegacySQLCommand::class))->getMethod('getBackupStorage');
    $method->setAccessible(true);

    return $method->invoke($command, $disk);
}

function makeImportTestRoot(): string
{
    $root = sys_get_temp_dir().'/legacy-import-'.Str::lower((string) Str::uuid());

    mkdir($root);

    return $root;
}

it('applies the container override when building the backup disk', function () {
    $root = makeImportTestRoot();

    $builtConfig = null;

    Storage::extend('container_capture', function ($app, $config) use (&$builtConfig, $root) {
        $builtConfig = $config;

        $adapter = new LocalFilesystemAdapter($root);

        return new FilesystemAdapter(
            new \League\Flysystem\Filesystem($adapter),
            $adapter,
            $config,
        );
    });

    config([
        'filesystems.disks.override_test' => [
            'driver' => 'container_capture',
            'root' => $root,
            'connection_string' => 'fake-connection-string',
        ],
    ]);

    $storage = invokeGetBackupStorage(buildImportCommandWithContainer('hmt-core-container'), 'override_test');

    expect($storage)->toBeInstanceOf(Filesystem::class);
    expect($builtConfig['container'])->toBe('hmt-core-container');
    expect($builtConfig['connection_string'])->toBe('fake-connection-string');
    expect($builtConfig['driver'])->toBe('container_capture');

    $storage->put('probe.txt', 'ok');
    expect($storage->exists('probe.txt'))->toBeTrue();

    unlink($root.'/probe.txt');
    rmdir($root);
});

it('uses the configured disk when no backup container override is given', function () {
    $root = makeImportTestRoot();

    config([
        'filesystems.disks.override_test' => [
            'driver' => 'local',
            'root' => $root,
        ],
    ]);

    $storage = invokeGetBackupStorage(buildImportCommandWithContainer(null), 'override_test');

    expect($storage)->toBe(Storage::disk('override_test'));

    rmdir($root);
});

it('rejects a container override on a disk without a connection string', function () {
    config([
        'filesystems.disks.override_test' => [
            'driver' => 'local',
            'root' => sys_get_temp_dir(),
        ],
    ]);

    $command = buildImportCommandWithContainer('hmt-core-container');

    expect(fn () => invokeGetBackupStorage($command, 'override_test'))
        ->toThrow(RuntimeException::class, 'connection string');
});
