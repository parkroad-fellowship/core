<?php

namespace App\Services\Finance;

/**
 * What the treasurer checks before confirming a workbook import: per-sheet totals to compare
 * against the workbook's Cash Balances, accounts that will be created, mapped and unmapped
 * labels, opening balances, transfer pairs, and a few sample rows per sheet.
 */
class WorkbookPreview
{
    /**
     * @param  list<array{sheet: string, account_type: string, account_name: string|null, account_exists: bool, rows: int, mapped: int, unmapped: int, receipts: int, payments: int, samples: list<array<string, mixed>>}>  $sheets
     * @param  array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>  $unmapped  keyed by normalised label
     * @param  list<array{type: string, name: string}>  $accountsToCreate
     * @param  array<string, int>  $mappedCounts  category code => rows
     * @param  list<array<string, mixed>>  $openingBalances
     * @param  list<array<string, mixed>>  $pairedTransfers
     * @param  list<array<string, mixed>>  $unpairedTransfers
     * @param  list<array<string, mixed>>  $skippedSamples
     */
    public function __construct(
        public array $sheets = [],
        public array $unmapped = [],
        public array $accountsToCreate = [],
        public array $mappedCounts = [],
        public array $openingBalances = [],
        public array $pairedTransfers = [],
        public array $unpairedTransfers = [],
        public int $totalRows = 0,
        public int $mappedRows = 0,
        public int $skippedRows = 0,
        public array $skippedSamples = [],
    ) {}

    public function unmappedRows(): int
    {
        return array_sum(array_map(fn(array $row): int => $row['count'], $this->unmapped));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sheets' => $this->sheets,
            'unmapped' => $this->unmapped,
            'accounts_to_create' => $this->accountsToCreate,
            'mapped_counts' => $this->mappedCounts,
            'opening_balances' => $this->openingBalances,
            'paired_transfers' => $this->pairedTransfers,
            'unpaired_transfers' => $this->unpairedTransfers,
            'total_rows' => $this->totalRows,
            'mapped_rows' => $this->mappedRows,
            'unmapped_rows' => $this->unmappedRows(),
            'skipped_rows' => $this->skippedRows,
            'skipped_samples' => $this->skippedSamples,
        ];
    }
}
