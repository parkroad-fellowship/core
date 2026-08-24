<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                    DO $$
                    DECLARE
                        policy_name text;
                    BEGIN
                        FOR policy_name IN
                            SELECT policyname FROM pg_policies WHERE tablename = 'connected_accounts'
                        LOOP
                            EXECUTE format('DROP POLICY %I ON connected_accounts', policy_name);
                        END LOOP;
                    END $$;
                SQL);
        }

        Schema::table('connected_accounts', function ($table) {
            $table->string('tenant_id', 36)->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("COMMENT ON COLUMN connected_accounts.tenant_id IS 'no-rls'");
        }
    }

    public function down(): void
    {
        // Backward migration is not supported for this change, as it may lead to data loss if any tenant_id values are null.
    }
};
