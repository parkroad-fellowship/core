<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = DB::getDriverName() === 'sqlite' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        $onConflict = DB::getDriverName() === 'sqlite' ? 'ON CONFLICT DO NOTHING' : 'ON CONFLICT (tenant_id, user_id) DO NOTHING';

        DB::statement("
            INSERT INTO tenant_user (tenant_id, user_id, role, created_at, updated_at)
            SELECT u.tenant_id, u.id, 'member', {$now}, {$now}
            FROM users u
            WHERE u.tenant_id IS NOT NULL
            {$onConflict}
        ");
    }

    public function down(): void
    {
        DB::statement('DELETE FROM tenant_user');
    }
};
