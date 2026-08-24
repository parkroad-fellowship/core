<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private string $fk;

    public function up(): void
    {
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (!$teams) {
            return;
        }

        $this->fk = $columnNames['team_foreign_key'];

        $this->ensureColumnAndType('roles', true);
        $this->ensureColumnAndType('model_has_permissions', false);
        $this->ensureColumnAndType('model_has_roles', false);

        $this->addPrimaryKeys();
    }

    private function ensureColumnAndType(string $table, bool $isRoles): void
    {
        if (!Schema::hasColumn($table, $this->fk)) {
            $this->addColumn($table, $isRoles);

            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $currentType = DB::select('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [
            $table,
            $this->fk,
        ]);

        if (!empty($currentType) && $currentType[0]->data_type === 'bigint') {
            $this->convertBigintToString($table);
        }
    }

    private function addColumn(string $table, bool $isRoles): void
    {
        Schema::table($table, function (Blueprint $table) use ($isRoles) {
            $col = $table->string($this->fk, 36);
            if ($isRoles) {
                $col->nullable()->after('id');
            } else {
                $col->nullable();
            }
        });

        if ($isRoles) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique([$this->fk, 'name', 'guard_name']);
                $table->index($this->fk, 'roles_team_foreign_key_index');
            });
        }

        if ($table === 'model_has_permissions') {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropPrimary('model_has_permissions_pkey');
                $table->index($this->fk, 'model_has_permissions_team_foreign_key_index');
            });
        }

        if ($table === 'model_has_roles') {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropPrimary('model_has_roles_pkey');
                $table->index($this->fk, 'model_has_roles_team_foreign_key_index');
            });
        }
    }

    private function addPrimaryKeys(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->primary([
                $this->fk,
                'permission_id',
                'model_id',
                'model_type',
            ], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->primary([
                $this->fk,
                'role_id',
                'model_id',
                'model_type',
            ], 'model_has_roles_role_model_type_primary');
        });
    }

    private function convertBigintToString(string $table): void
    {
        $pkey = match ($table) {
            'model_has_roles' => 'model_has_roles_pkey',
            'model_has_permissions' => 'model_has_permissions_pkey',
            default => null,
        };

        $index = match ($table) {
            'roles' => 'roles_team_foreign_key_index',
            'model_has_roles' => 'model_has_roles_team_foreign_key_index',
            'model_has_permissions' => 'model_has_permissions_team_foreign_key_index',
            default => null,
        };

        $unique = $table === 'roles' ? 'roles_tenant_id_name_guard_name_unique' : null;

        if ($unique && $this->hasIndex($table, $unique)) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$unique}");
        }

        if ($pkey) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$pkey}");
        }

        if ($index && $this->hasIndex($table, $index)) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }

        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$this->fk} TYPE varchar(36) USING {$this->fk}::varchar(36)");
        DB::statement("ALTER TABLE {$table} ALTER COLUMN {$this->fk} DROP NOT NULL");

        if ($unique) {
            DB::statement("ALTER TABLE {$table} ADD UNIQUE ({$this->fk}, name, guard_name)");
        }

        if ($index) {
            DB::statement("CREATE INDEX {$index} ON {$table} ({$this->fk})");
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (bool) DB::select('SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?', [$table, $index]);
    }

    public function down(): void
    {
        $columnNames = config('permission.column_names');
        $fk = $columnNames['team_foreign_key'];

        Schema::table('model_has_roles', function (Blueprint $table) use ($fk) {
            if (Schema::hasColumn('model_has_roles', $fk)) {
                $table->dropPrimary('model_has_roles_role_model_type_primary');
                $table->dropIndex('model_has_roles_team_foreign_key_index');
                $table->dropColumn($fk);
                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        Schema::table('model_has_permissions', function (Blueprint $table) use ($fk) {
            if (Schema::hasColumn('model_has_permissions', $fk)) {
                $table->dropPrimary('model_has_permissions_permission_model_type_primary');
                $table->dropIndex('model_has_permissions_team_foreign_key_index');
                $table->dropColumn($fk);
                $table->primary(
                    ['permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary',
                );
            }
        });

        Schema::table('roles', function (Blueprint $table) use ($fk) {
            if (Schema::hasColumn('roles', $fk)) {
                $table->dropIndex('roles_team_foreign_key_index');
                $table->dropColumn($fk);
            }
        });
    }
};
