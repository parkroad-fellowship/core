<?php

use App\Enums\PRFFinancialAccountType;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;

describe('index', function () {
    it('lists accounts with their balances', function () {
        $account = FinancialAccount::factory()->ofType(PRFFinancialAccountType::PAYBILL)->create();
        LedgerEntry::factory()->for($account)->create(['amount' => 4_000]);
        LedgerEntry::factory()->for($account)->payment()->create(['amount' => 1_500]);

        actingAsTenantUser()
            ->getJson(route('api.financial-accounts.index'))
            ->assertOk()
            ->assertJsonPath('data.0.entity', 'financial-account')
            ->assertJsonPath('data.0.balance', 2_500);
    });
});

describe('store', function () {
    it('creates an account', function () {
        actingAsTenantUser()->postJson(route('api.financial-accounts.store'), [
            'name' => 'NCBA Bank',
            'type' => PRFFinancialAccountType::BANK->value,
            'identifier' => '6443280017',
        ])->assertCreated()->assertJsonPath('data.name', 'NCBA Bank')->assertJsonPath('data.balance', 0);
    });

    it('validates input', function (array $payload, string $field) {
        FinancialAccount::factory()->create(['name' => 'Cash']);

        actingAsTenantUser()
            ->postJson(route('api.financial-accounts.store'), $payload)
            ->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [['type' => 1], 'name'],
        'duplicate name' => [['name' => 'Cash', 'type' => 4], 'name'],
        'unknown type' => [['name' => 'Vault', 'type' => 99], 'type'],
    ]);
});

describe('update', function () {
    it('renames an account', function () {
        $account = FinancialAccount::factory()->create();

        actingAsTenantUser()->patchJson(route('api.financial-accounts.update', $account->ulid), [
            'name' => 'Petty Cash',
        ])->assertOk()->assertJsonPath('data.name', 'Petty Cash');
    });
});

describe('destroy', function () {
    it('soft deletes an account', function () {
        $account = FinancialAccount::factory()->create();

        actingAsTenantUser()->deleteJson(route('api.financial-accounts.destroy', $account->ulid))->assertNoContent();

        $this->assertSoftDeleted($account);
    });
});
