<?php

use App\Console\Commands\Tenant\ImportLegacySQLCommand;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

function buildImportCommandWithDbContext(?string $container): ImportLegacySQLCommand
{
    $command = buildImportCommandWithContainer($container);

    $reflection = new ReflectionClass(ImportLegacySQLCommand::class);

    foreach ([
        'tenantId' => '01test_tenant_00000000000000000',
        'tempDatabase' => 'prf_import_test',
        'dbConfig' => [
            'host' => 'localhost',
            'port' => '5432',
            'username' => 'postgres',
            'database' => 'prf',
            'password' => 'secret',
        ],
    ] as $name => $value) {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($command, $value);
    }

    return $command;
}

/**
 * @param  array<int, string>  $dumpColumns
 * @return array{0: string, 1: string}|null
 */
function invokeBuildParentRemap(ImportLegacySQLCommand $command, string $parent, string $column, array $dumpColumns): ?array
{
    $method = (new ReflectionClass(ImportLegacySQLCommand::class))->getMethod('buildParentRemap');
    $method->setAccessible(true);

    return $method->invoke($command, $parent, $column, $dumpColumns);
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

it('tenant-scopes the member ulid and email remap joins', function () {
    $command = buildImportCommandWithDbContext(null);

    [$join, $mapped] = invokeBuildParentRemap($command, 'members', 'member_id', ['id', 'ulid', 'email']);

    expect($join)->toContain('mp_member_id."tenant_id" = \'01test_tenant_00000000000000000\'')
        ->and($join)->toContain('mp_member_id_email."tenant_id" = \'01test_tenant_00000000000000000\'')
        ->and($mapped)->toBe('COALESCE(mp_member_id."id", mp_member_id_email."id")');
});

it('tenant-scopes the member ulid remap when the dump has no email column', function () {
    $command = buildImportCommandWithDbContext(null);

    [$join, $mapped] = invokeBuildParentRemap($command, 'members', 'member_id', ['id', 'ulid']);

    expect($join)->toContain('mp_member_id."tenant_id" = \'01test_tenant_00000000000000000\'')
        ->and($join)->not->toContain('mp_member_id_email')
        ->and($mapped)->toBe('mp_member_id."id"');
});

it('does not tenant-scope global table remaps like users', function () {
    $command = buildImportCommandWithDbContext(null);

    [$join, $mapped] = invokeBuildParentRemap($command, 'users', 'user_id', ['id', 'ulid']);

    expect($join)->not->toContain('tenant_id')
        ->and($mapped)->toBe('mp_user_id."id"');
});

it('does not tenant-scope the permissions name/guard_name remap', function () {
    $command = buildImportCommandWithDbContext(null);

    [$join, $mapped] = invokeBuildParentRemap($command, 'permissions', 'permission_id', ['id', 'name', 'guard_name']);

    expect($join)->not->toContain('tenant_id')
        ->and($join)->toContain('guard_name')
        ->and($mapped)->toBe('mp_permission_id."id"');
});

it('tenant-scopes the roles name/guard_name remap', function () {
    $command = buildImportCommandWithDbContext(null);

    [$join, $mapped] = invokeBuildParentRemap($command, 'roles', 'role_id', ['id', 'name', 'guard_name']);

    expect($join)->toContain('mp_role_id."tenant_id" = \'01test_tenant_00000000000000000\'')
        ->and($mapped)->toBe('mp_role_id."id"');
});

it('retries guarded tables that inserted no rows until their parents merge', function () {
    $command = buildImportCommandWithDbContext(null);

    $method = (new ReflectionClass(ImportLegacySQLCommand::class))->getMethod('hasCompletedMerge');
    $method->setAccessible(true);

    $invoke = fn (bool $guarded, int $inserted): bool => $method->invoke($command, $guarded, $inserted);

    expect($invoke(true, 0))->toBeFalse()      // waiting on parents -> retry
        ->and($invoke(true, 5))->toBeTrue()    // parents merged, rows landed
        ->and($invoke(false, 0))->toBeTrue()   // dedup (e.g. permissions) -> done
        ->and($invoke(false, 8))->toBeTrue();
});

it('scopes member unique constraints per tenant so identities can coexist', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requires a PostgreSQL test database.');
    }

    expect(collect(Schema::getIndexes('members'))->pluck('name'))
        ->toContain('members_tenant_id_personal_email_unique')
        ->toContain('members_tenant_id_phone_number_unique')
        ->toContain('members_tenant_id_ulid_unique');

    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $createMember = function (Tenant $tenant, string $personalEmail): void {
        DB::table('members')->insert([
            'tenant_id' => $tenant->getKey(),
            'ulid' => (string) Str::ulid(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'personal_email' => $personalEmail,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $createMember($tenantA, 'jane@example.com');
    $createMember($tenantB, 'jane@example.com');

    expect(DB::table('members')->where('personal_email', 'jane@example.com')->count())->toBe(2);

    expect(fn () => $createMember($tenantA, 'jane@example.com'))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
