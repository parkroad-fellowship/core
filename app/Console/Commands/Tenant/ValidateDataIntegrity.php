<?php

namespace App\Console\Commands\Tenant;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateDataIntegrity extends Command
{
    protected $signature = 'tenants:validate-data';

    protected $description = 'Validate tenant data integrity';

    public function handle(): int
    {
        $errors = [];

        $orphanRows = DB::table('missions as m')
            ->leftJoin('tenants as t', 't.id', '=', 'm.tenant_id')
            ->whereNull('t.id')
            ->count();

        if ($orphanRows > 0) {
            $errors[] = "Found {$orphanRows} mission rows with orphan tenant_id values.";
        }

        $nullTenantRows = DB::table('missions')->whereNull('tenant_id')->count();

        if ($nullTenantRows > 0) {
            $errors[] = "Found {$nullTenantRows} mission rows with null tenant_id.";
        }

        $invalidPivotRows = DB::table('tenant_user as tu')
            ->leftJoin('tenants as t', 't.id', '=', 'tu.tenant_id')
            ->leftJoin('users as u', 'u.id', '=', 'tu.user_id')
            ->where(function ($q) {
                $q->whereNull('t.id')->orWhereNull('u.id');
            })
            ->count();

        if ($invalidPivotRows > 0) {
            $errors[] = "Found {$invalidPivotRows} tenant_user rows with missing tenant/user references.";
        }

        $duplicateEmails = DB::table('users')
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicateEmails > 0) {
            $errors[] = "Found {$duplicateEmails} duplicated global user email values.";
        }

        $mismatchedMembers = DB::table('members as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
            ->whereNotNull('m.email')
            ->where(function ($q) {
                $q->whereNull('u.id')
                    ->orWhereRaw('LOWER(u.email) != LOWER(m.email)');
            })
            ->count();

        if ($mismatchedMembers > 0) {
            $errors[] = "Found {$mismatchedMembers} member rows with missing or mismatched user email links.";
        }

        if (! empty($errors)) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('Tenant data integrity checks passed.');

        return self::SUCCESS;
    }
}
