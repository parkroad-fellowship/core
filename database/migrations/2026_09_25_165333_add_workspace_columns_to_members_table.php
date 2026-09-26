<?php

use App\Enums\PRFWorkspaceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('workspace_user_id')->nullable()->after('email');
            $table
                ->tinyInteger('workspace_status')
                ->default(PRFWorkspaceStatus::NOT_APPLICABLE->value)
                ->after('workspace_user_id');
            $table->text('workspace_error')->nullable()->after('workspace_status');
            $table->timestamp('workspace_provisioned_at')->nullable()->after('workspace_error');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'workspace_user_id',
                'workspace_status',
                'workspace_error',
                'workspace_provisioned_at',
            ]);
        });
    }
};
