<?php

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFResponsibleDesk;

/*
 | The treasurer's chart of accounts, seeded for every new tenant (FinancialAccountSeeder,
 | LedgerCategorySeeder). Categories with a `code` are posted to automatically by the app, so
 | the treasurer may rename them but not delete them. Lines mirror the fellowship's Income Statement.
 */

$desks = [
    ['desk' => PRFResponsibleDesk::CHAIRPERSON, 'expense' => "Chairman's Desk", 'refund' => 'Chairman Refund'],
    [
        'desk' => PRFResponsibleDesk::VICE_CHAIRPERSON_DESK,
        'expense' => "Vice Chair's Desk",
        'refund' => 'Vice Chairman Refund',
    ],
    ['desk' => PRFResponsibleDesk::TREASURER_DESK, 'expense' => "Treasurer's Desk", 'refund' => 'Treasurer Refund'],
    [
        'desk' => PRFResponsibleDesk::ORGANISING_SECRETARY_DESK,
        'expense' => "Organizing Secretary's Desk",
        'refund' => 'OS Refund',
    ],
    ['desk' => PRFResponsibleDesk::MISSIONS_DESK, 'expense' => 'Mission Desk', 'refund' => 'Mission Refund'],
    ['desk' => PRFResponsibleDesk::PRAYER_DESK, 'expense' => 'Prayer Desk', 'refund' => 'Prayer Desk Refund'],
    ['desk' => PRFResponsibleDesk::MUSIC_DESK, 'expense' => 'Music Desk', 'refund' => 'Music Desk Refund'],
    ['desk' => PRFResponsibleDesk::FOLLOW_UP_DESK, 'expense' => 'Follow-up Desk', 'refund' => 'Follow-up Desk Refund'],
];

$categories = [
    // Receipts, in Income Statement order.
    ['code' => 'income.member_contribution', 'name' => 'Member Contribution', 'kind' => PRFLedgerCategoryKind::INCOME],
    [
        'code' => 'income.mission_contribution',
        'name' => 'Mission Contribution',
        'kind' => PRFLedgerCategoryKind::INCOME,
    ],
    [
        'code' => 'income.appreciation_from_schools',
        'name' => 'Appreciation From Schools',
        'kind' => PRFLedgerCategoryKind::INCOME,
    ],
    ['code' => 'income.camp_contribution', 'name' => 'Camp Contribution', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.member_subscription', 'name' => 'Member Subscription', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.tithe_and_offering', 'name' => 'Tithe & Offering', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.dinner_contribution', 'name' => 'Dinner Contribution', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.interest', 'name' => 'Interest on Fixed Deposit', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.gik', 'name' => 'GIK - Rent & Instruments', 'kind' => PRFLedgerCategoryKind::INCOME],
    ['code' => 'income.other', 'name' => 'Other Receipts', 'kind' => PRFLedgerCategoryKind::INCOME],
];

foreach ($desks as $desk) {
    $categories[] = [
        'code' => 'expense.desk.' . $desk['desk']->value,
        'name' => $desk['expense'],
        'kind' => PRFLedgerCategoryKind::EXPENSE,
        'responsible_desk' => $desk['desk'],
    ];
}

$categories = [
    ...$categories,
    [
        'code' => 'expense.bot',
        'name' => 'BOT Expenses - Strategic Plan',
        'kind' => PRFLedgerCategoryKind::EXPENSE,
        'responsible_desk' => PRFResponsibleDesk::CHAIRPERSON,
    ],
    ['code' => 'expense.camp', 'name' => 'Camp Expenses', 'kind' => PRFLedgerCategoryKind::EXPENSE],
    [
        'code' => 'expense.agm',
        'name' => 'AGM Expense',
        'kind' => PRFLedgerCategoryKind::EXPENSE,
        'responsible_desk' => PRFResponsibleDesk::ORGANISING_SECRETARY_DESK,
    ],
    [
        'code' => 'expense.dinner',
        'name' => 'Dinner Expenses',
        'kind' => PRFLedgerCategoryKind::EXPENSE,
        'responsible_desk' => PRFResponsibleDesk::ORGANISING_SECRETARY_DESK,
    ],
    ['code' => 'expense.other', 'name' => 'Other Expenses', 'kind' => PRFLedgerCategoryKind::EXPENSE],
];

// Refunds return money to a desk: they reduce that desk's expense line and are never income.
foreach ($desks as $desk) {
    $categories[] = [
        'code' => 'refund.desk.' . $desk['desk']->value,
        'name' => $desk['refund'],
        'kind' => PRFLedgerCategoryKind::REFUND,
        'responsible_desk' => $desk['desk'],
        'statement_line' => $desk['expense'],
    ];
}

$categories = [
    ...$categories,
    [
        'code' => 'charge.transaction_costs',
        'name' => 'Transaction costs (M-Pesa, bank & Paystack charges)',
        'kind' => PRFLedgerCategoryKind::CHARGE,
        'responsible_desk' => PRFResponsibleDesk::TREASURER_DESK,
        'statement_line' => "Treasurer's Desk",
    ],
    ['code' => 'transfer', 'name' => 'Inter-account transfer', 'kind' => PRFLedgerCategoryKind::TRANSFER],
    ['code' => 'opening_balance', 'name' => 'Opening Balance', 'kind' => PRFLedgerCategoryKind::OPENING_BALANCE],
];

return [
    'receipt_number_prefix' => 'PRF',

    'accounts' => [
        ['name' => 'Paybill', 'type' => PRFFinancialAccountType::PAYBILL],
        ['name' => 'M-Pesa', 'type' => PRFFinancialAccountType::MPESA],
        ['name' => 'Bank', 'type' => PRFFinancialAccountType::BANK],
        ['name' => 'Cash', 'type' => PRFFinancialAccountType::CASH],
        ['name' => 'M-Shwari', 'type' => PRFFinancialAccountType::MSHWARI],
        ['name' => 'Paystack', 'type' => PRFFinancialAccountType::PAYSTACK],
    ],

    'categories' => $categories,

    // Online giving (PaymentType name) → the category its gifts are booked under.
    'payment_type_categories' => [
        'Missions' => 'income.mission_contribution',
        'Camp' => 'income.camp_contribution',
        'Bible Study Fun Day' => 'income.other',
    ],

    'import' => [
        // Cashbook sheets read from the treasurer's workbook, by account type; {year} is replaced.
        'sheets' => [
            'M-shwari' => PRFFinancialAccountType::MSHWARI,
            'Cash' => PRFFinancialAccountType::CASH,
            'M-pesa {year}' => PRFFinancialAccountType::MPESA,
            'bank {year}' => PRFFinancialAccountType::BANK,
            'Paybill {year}' => PRFFinancialAccountType::PAYBILL,
        ],
    ],
];
