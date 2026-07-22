<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("COMMENT ON COLUMN personal_access_tokens.api_client_id IS 'no-rls'");
    }

    public function down(): void
    {
        DB::statement('COMMENT ON COLUMN personal_access_tokens.api_client_id IS NULL');
    }
};
