<?php

namespace App\Services\Finance;

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Jobs\AccountTransfer\CreateJob as CreateTransferJob;
use App\Jobs\FinancialAccount\CreateJob as CreateAccountJob;
use App\Jobs\LedgerCategory\CreateJob as CreateCategoryJob;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\LedgerImport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

/**
 * Reads the treasurer's cashbook workbook (one sheet per account) into ledger lines.
 *
 * The `Handover` sheet is never loaded. Columns are detected by header text, amounts use
 * calculated values, and every posted line carries an import `source_key`, so re-importing
 * the same workbook only adds new rows. Imported income gets receipt numbers but never
 * sends receipts.
 *
 * @phpstan-type ParsedRow array{sheet: string, row: int, date: Carbon, counterparty: string|null, description: string|null, reference: string|null, amount: int, flow: PRFLedgerFlow, label: string|null, normalised: string, kind: string, category_code: string|null, is_new_category: bool}
 */
class WorkbookImporter
{
    private const TRANSFER_LABELS = ['inter a/c transfer', 'inter account transfer', 'inter-account transfer'];

    public function __construct(
        private readonly ChartOfAccounts $chart,
        private readonly Ledger $ledger,
    ) {}

    /**
     * Workbook label → category code: lowercase, trimmed, single spaces, quotes unified.
     */
    public static function normaliseLabel(?string $label): string
    {
        $label = str_replace(['’', '‘', '`', 'ʼ'], "'", (string) $label);
        $label = mb_strtolower(trim($label));

        return (string) preg_replace('/\s+/', ' ', $label);
    }

    public function preview(string $path, int $year, array $mapping = []): WorkbookPreview
    {
        $parsed = $this->parse($path, $year, $mapping);
        $pairs = $this->pairTransfers($parsed['rows']);

        $preview = new WorkbookPreview();
        $accountNames = $this->defaultAccountNames();

        foreach ($parsed['sheets'] as $sheetName => $sheet) {
            /** @var PRFFinancialAccountType $type */
            $type = $sheet['type'];
            $account = FinancialAccount::query()->active()->where('type', $type)->orderBy('id')->first();
            $rows = array_values(array_filter($parsed['rows'], fn(array $row): bool => $row['sheet'] === $sheetName));

            $receipts = 0;
            $payments = 0;
            $mapped = 0;
            $unmapped = 0;
            $samples = [];

            foreach ($rows as $row) {
                if ($row['flow'] === PRFLedgerFlow::RECEIPT) {
                    $receipts += $row['amount'];
                } else {
                    $payments += $row['amount'];
                }

                if ($row['kind'] === 'unmapped') {
                    $unmapped++;
                } else {
                    $mapped++;
                }

                if (count($samples) < 5) {
                    $samples[] = [
                        'date' => $row['date']->toDateString(),
                        'counterparty' => $row['counterparty'],
                        'description' => $row['description'],
                        'amount' => $row['amount'],
                        'flow' => $row['flow']->getLabel(),
                        'category' => $row['category_code'] ?? ($row['kind'] === 'transfer' ? 'transfer' : 'unmapped'),
                    ];
                }

                if ($row['kind'] !== 'unmapped' && $row['category_code'] !== null) {
                    $preview->mappedCounts[$row['category_code']] =
                        ($preview->mappedCounts[$row['category_code']] ?? 0) + 1;
                }

                if ($row['kind'] === 'opening') {
                    $preview->openingBalances[] = [
                        'sheet' => $sheetName,
                        'date' => $row['date']->toDateString(),
                        'amount' => $row['amount'],
                        'flow' => $row['flow']->getLabel(),
                    ];
                }
            }

            if ($account === null) {
                $preview->accountsToCreate[] = [
                    'type' => $type->getLabel(),
                    'name' => $accountNames[$type->value] ?? $sheetName,
                ];
            }

            $preview->sheets[] = [
                'sheet' => $sheetName,
                'account_type' => $type->getLabel(),
                'account_name' => $account?->name,
                'account_exists' => $account !== null,
                'rows' => count($rows),
                'mapped' => $mapped,
                'unmapped' => $unmapped,
                'receipts' => $receipts,
                'payments' => $payments,
                'samples' => $samples,
            ];

            $preview->totalRows += count($rows);
            $preview->mappedRows += $mapped;
        }

        foreach ($parsed['unmapped'] as $key => $entry) {
            $preview->unmapped[$key] = $entry;
        }

        $preview->pairedTransfers = $pairs['paired'];
        $preview->unpairedTransfers = $pairs['unpaired'];
        $preview->skippedRows = count($parsed['skipped']);
        $preview->skippedSamples = array_slice($parsed['skipped'], 0, 10);

        return $preview;
    }

    /**
     * Post every mapped row of the import. Idempotent via `source_key`: rows already posted
     * (even by an earlier import of the same workbook) are skipped, never duplicated.
     *
     * @return array{sheets: array<string, array{rows: int, posted: int, skipped: int, receipts: int, payments: int}>, posted: int, skipped: int, unmapped: int, transfers_paired: int, transfers_unpaired: int, accounts_created: list<string>, categories_created: list<string>}
     */
    public function import(LedgerImport $import): array
    {
        $mapping = $import->mapping ?? [];
        $parsed = $this->parse($this->resolvePath($import), $import->year, $mapping);

        $accounts = [];
        $accountsCreated = [];
        foreach ($parsed['sheets'] as $sheetName => $sheet) {
            $accounts[$sheetName] = $this->ensureAccount($sheet['type'], $sheetName, $accountsCreated);
        }

        [$labelToCategoryId, $categoriesCreated] = $this->resolveCategories($parsed['rows'], $mapping);

        $this->demoteRowsWithMissingCategories($parsed, $labelToCategoryId);

        $pairs = $this->pairTransfers($parsed['rows']);
        $pairedKeys = [];
        foreach ($pairs['paired'] as $pair) {
            $pairedKeys[$pair['out_key']] = true;
            $pairedKeys[$pair['in_key']] = true;
        }

        $summary = [
            'sheets' => [],
            'posted' => 0,
            'skipped' => 0,
            'unmapped' => 0,
            'transfers_paired' => 0,
            'transfers_unpaired' => 0,
            'accounts_created' => $accountsCreated,
            'categories_created' => $categoriesCreated,
        ];

        foreach ($parsed['rows'] as $row) {
            $sheet = $row['sheet'];
            $summary['sheets'][$sheet] ??= [
                'rows' => 0,
                'posted' => 0,
                'skipped' => 0,
                'receipts' => 0,
                'payments' => 0,
            ];
            $summary['sheets'][$sheet]['rows']++;

            if ($row['flow'] === PRFLedgerFlow::RECEIPT) {
                $summary['sheets'][$sheet]['receipts'] += $row['amount'];
            } else {
                $summary['sheets'][$sheet]['payments'] += $row['amount'];
            }

            if ($row['kind'] === 'unmapped') {
                $summary['unmapped']++;

                continue;
            }

            if ($row['kind'] === 'transfer' && !isset($pairedKeys[$sheet . '|' . $row['row']])) {
                $this->postTransferLine($import, $accounts[$sheet], $row);
                $summary['sheets'][$sheet]['posted']++;
                $summary['posted']++;
                $summary['transfers_unpaired']++;

                continue;
            }

            if ($row['kind'] === 'transfer') {
                continue;
            }

            $categoryId = $labelToCategoryId[$row['normalised']] ?? null;

            if ($categoryId === null) {
                $summary['unmapped']++;

                continue;
            }

            $sourceKey = $this->sourceKey($row);

            if (LedgerEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                $summary['sheets'][$sheet]['skipped']++;
                $summary['skipped']++;

                continue;
            }

            $this->ledger->post([
                'financial_account_id' => $accounts[$sheet]->id,
                'ledger_category_id' => $categoryId,
                'flow' => $row['flow'],
                'channel' => PRFLedgerChannel::defaultFor($accounts[$sheet]->type),
                'amount' => $row['amount'],
                'transacted_on' => $row['date']->toDateString(),
                'counterparty' => $row['counterparty'],
                'description' => $row['description'],
                'reference' => $row['reference'],
                'ledger_import_id' => $import->id,
                'recorded_by' => $import->imported_by,
                'source_key' => $sourceKey,
            ]);

            $summary['sheets'][$sheet]['posted']++;
            $summary['posted']++;
        }

        foreach ($pairs['paired'] as $pair) {
            if ($this->postTransferPair($import, $accounts, $pair)) {
                $summary['transfers_paired']++;
                $summary['posted'] += 2;
            } else {
                $summary['skipped'] += 2;
            }
        }

        return $summary;
    }

    /**
     * Absolute path of an import's uploaded file: storage-relative first, then absolute (CLI).
     */
    public function resolvePath(LedgerImport $import): string
    {
        $disk = Storage::disk(LedgerImport::DISK);

        if (is_string($import->file_path) && $import->file_path !== '' && $disk->exists($import->file_path)) {
            return $disk->path($import->file_path);
        }

        if (is_string($import->file_path) && is_file($import->file_path)) {
            return $import->file_path;
        }

        throw new RuntimeException("Import file not found: {$import->file_path}");
    }

    /**
     * @return array{rows: list<array<string, mixed>>, sheets: array<string, array{type: PRFFinancialAccountType}>, skipped: list<array{sheet: string, row: int, reason: string}>, unmapped: array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>}
     */
    private function parse(string $path, int $year, array $mapping): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Workbook not found: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $configured = [];
        foreach ((array) config('prf.finance.import.sheets', []) as $pattern => $type) {
            $configured[self::normaliseLabel(str_replace('{year}', (string) $year, (string) $pattern))] = $type;
        }

        $toLoad = [];
        foreach ($reader->listWorksheetNames($path) as $name) {
            $normalised = self::normaliseLabel($name);

            if ($normalised === 'handover') {
                continue;
            }

            if (isset($configured[$normalised]) && !isset($toLoad[$name])) {
                $type = $configured[$normalised];
                $toLoad[$name] = $type instanceof PRFFinancialAccountType
                    ? $type
                    : PRFFinancialAccountType::from((int) $type);
            }
        }

        if ($toLoad === []) {
            throw new RuntimeException(
                'No cashbook sheets found. Expected: '
                    . implode(', ', array_keys((array) config('prf.finance.import.sheets', []))),
            );
        }

        $reader->setLoadSheetsOnly(array_keys($toLoad));
        $book = $reader->load($path);

        $parsed = ['rows' => [], 'sheets' => [], 'skipped' => [], 'unmapped' => []];

        foreach ($toLoad as $sheetName => $type) {
            $sheet = $book->getSheetByName($sheetName);

            if (!$sheet instanceof Worksheet) {
                continue;
            }

            $parsed['sheets'][$sheetName] = ['type' => $type];
            $this->parseSheet($parsed, $sheet, $sheetName, $type, $year, $mapping);
        }

        $book->disconnectWorksheets();

        return $parsed;
    }

    /**
     * @param  array{rows: list<array<string, mixed>>, sheets: array<string, array{type: PRFFinancialAccountType}>, skipped: list<array{sheet: string, row: int, reason: string}>, unmapped: array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>}  $parsed
     */
    private function parseSheet(
        array &$parsed,
        Worksheet $sheet,
        string $sheetName,
        PRFFinancialAccountType $type,
        int $year,
        array $mapping,
    ): void {
        $columns = $this->detectColumns($sheet);
        $highestRow = $sheet->getHighestRow();

        for ($rowNumber = 3; $rowNumber <= $highestRow; $rowNumber++) {
            $receipts = $this->parseAmount($this->cellValue($sheet, $columns['receipts'], $rowNumber));
            $payments = $this->parseAmount($this->cellValue($sheet, $columns['payments'], $rowNumber));

            if ($receipts === null && $payments === null) {
                continue;
            }

            $counterparty = $this->cellText($sheet, $columns['counterparty'], $rowNumber);
            $description = $this->cellText($sheet, $columns['description'], $rowNumber);
            $reference = $this->cellText($sheet, $columns['reference'], $rowNumber);
            $label = $columns['desk'] === null ? null : $this->cellText($sheet, $columns['desk'], $rowNumber);

            foreach ([$counterparty, $description, $reference, $label] as $text) {
                if ($text !== null && str_starts_with(self::normaliseLabel($text), 'total')) {
                    $parsed['skipped'][] = ['sheet' => $sheetName, 'row' => $rowNumber, 'reason' => 'TOTAL row'];

                    continue 2;
                }
            }

            $amount = $receipts ?? $payments;
            $flow = $receipts !== null ? PRFLedgerFlow::RECEIPT : PRFLedgerFlow::PAYMENT;

            if ($amount === null || $amount === 0) {
                $parsed['skipped'][] = ['sheet' => $sheetName, 'row' => $rowNumber, 'reason' => 'No amount'];

                continue;
            }

            if ($amount < 0) {
                $amount = abs($amount);
                $flow = $flow === PRFLedgerFlow::RECEIPT ? PRFLedgerFlow::PAYMENT : PRFLedgerFlow::RECEIPT;
            }

            $descNorm = self::normaliseLabel($description);
            $labelNorm = self::normaliseLabel($label);
            $isMshwari = $type === PRFFinancialAccountType::MSHWARI;

            $isOpening = $this->isOpeningBalance($rowNumber, $descNorm, $labelNorm, $counterparty, $description);

            if ($isOpening) {
                $parsed['rows'][] = [
                    'sheet' => $sheetName,
                    'row' => $rowNumber,
                    'date' => Carbon::create($year, 1, 1)->startOfDay(),
                    'counterparty' => $counterparty,
                    'description' => $description ?? 'Opening balance',
                    'reference' => $reference,
                    'amount' => $amount,
                    'flow' => $flow,
                    'label' => $label,
                    'normalised' => 'opening_balance',
                    'kind' => 'opening',
                    'category_code' => 'opening_balance',
                    'is_new_category' => false,
                ];

                continue;
            }

            $date = $this->parseDate(
                $this->cellValue($sheet, $columns['date'], $rowNumber),
                $sheet,
                $columns['date'],
                $rowNumber,
            );

            if (!$date instanceof Carbon) {
                $parsed['skipped'][] = ['sheet' => $sheetName, 'row' => $rowNumber, 'reason' => 'No date'];

                continue;
            }

            if (str_contains($descNorm, 'charge') || str_contains($labelNorm, 'charge')) {
                $parsed['rows'][] = $this->makeRow(
                    $sheetName,
                    $rowNumber,
                    $date,
                    $counterparty,
                    $description,
                    $reference,
                    $amount,
                    $flow,
                    $label,
                    'charge.transaction_costs',
                );

                continue;
            }

            foreach (self::TRANSFER_LABELS as $transferLabel) {
                if (str_contains($labelNorm, $transferLabel) || str_contains($descNorm, $transferLabel)) {
                    $parsed['rows'][] = $this->makeRow(
                        $sheetName,
                        $rowNumber,
                        $date,
                        $counterparty,
                        $description,
                        $reference,
                        $amount,
                        $flow,
                        $label,
                        'transfer',
                        'transfer',
                    );

                    continue 2;
                }
            }

            if ($isMshwari && $labelNorm === '') {
                $code = str_contains($descNorm, 'interest') ? 'income.interest' : 'income.other';
                $parsed['rows'][] = $this->makeRow(
                    $sheetName,
                    $rowNumber,
                    $date,
                    $counterparty,
                    $description,
                    $reference,
                    $amount,
                    $flow,
                    $label,
                    $code,
                );

                continue;
            }

            $code =
                $this->lookupCode($labelNorm, $mapping)
                ?? ($labelNorm === '' ? $this->lookupCode($descNorm, $mapping) : null);

            if ($code === null) {
                $row = $this->makeRow(
                    $sheetName,
                    $rowNumber,
                    $date,
                    $counterparty,
                    $description,
                    $reference,
                    $amount,
                    $flow,
                    $label,
                    null,
                    'unmapped',
                );
                $parsed['rows'][] = $row;
                $this->trackUnmapped($parsed['unmapped'], $row);

                continue;
            }

            // The workbook books refunds under the desk: a receipt against an expense desk is
            // money returned, never income.
            if ($flow === PRFLedgerFlow::RECEIPT && str_starts_with($code, 'expense.desk.')) {
                $code = 'refund.desk.' . substr($code, strlen('expense.desk.'));
            }

            $parsed['rows'][] = $this->makeRow(
                $sheetName,
                $rowNumber,
                $date,
                $counterparty,
                $description,
                $reference,
                $amount,
                $flow,
                $label,
                $code,
            );
        }
    }

    /**
     * @return array{sheet: string, row: int, date: Carbon, counterparty: string|null, description: string|null, reference: string|null, amount: int, flow: PRFLedgerFlow, label: string|null, normalised: string, kind: string, category_code: string|null, is_new_category: bool}
     */
    private function makeRow(
        string $sheet,
        int $rowNumber,
        Carbon $date,
        ?string $counterparty,
        ?string $description,
        ?string $reference,
        int $amount,
        PRFLedgerFlow $flow,
        ?string $label,
        ?string $code,
        ?string $kind = null,
    ): array {
        $normalised = self::normaliseLabel($label);

        if ($normalised === '' && $description !== null) {
            $normalised = self::normaliseLabel($description);
        }

        return [
            'sheet' => $sheet,
            'row' => $rowNumber,
            'date' => $date,
            'counterparty' => $counterparty,
            'description' => $description,
            'reference' => $reference,
            'amount' => $amount,
            'flow' => $flow,
            'label' => $label,
            'normalised' => $normalised,
            'kind' => $kind ?? 'mapped',
            'category_code' => $code,
            'is_new_category' => false,
        ];
    }

    private function isOpeningBalance(
        int $rowNumber,
        string $descNorm,
        string $labelNorm,
        ?string $counterparty,
        ?string $description,
    ): bool {
        if (
            str_contains($descNorm, 'opening balance')
            || str_contains($labelNorm, 'opening balance')
            || str_starts_with($descNorm, 'bal c/f')
            || str_starts_with($labelNorm, 'bal c/f')
            || str_contains($descNorm, 'balance b/f')
            || str_contains($labelNorm, 'balance b/f')
        ) {
            return true;
        }

        // Row 3 holds the opening balance, unless it already looks like a normal entry.
        return (
            $rowNumber === 3
            && self::normaliseLabel($counterparty) === ''
            && self::normaliseLabel($description) === ''
        );
    }

    private function lookupCode(string $normalised, array $mapping): ?string
    {
        if ($normalised === '') {
            return null;
        }

        if (isset($mapping[$normalised]) && is_string($mapping[$normalised])) {
            return $mapping[$normalised];
        }

        $map = (array) config('prf.finance.import.category_map', []);

        return isset($map[$normalised]) && is_string($map[$normalised]) ? $map[$normalised] : null;
    }

    /**
     * @param  array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>  $unmapped
     * @param  array<string, mixed>  $row
     */
    private function trackUnmapped(array &$unmapped, array $row): void
    {
        /** @var string $normalised */
        $normalised = $row['normalised'] !== '' ? $row['normalised'] : '(blank)';

        $unmapped[$normalised] ??= [
            'label' => is_string($row['label']) && $row['label'] !== '' ? $row['label'] : '(blank)',
            'count' => 0,
            'sheets' => [],
            'example' => null,
        ];
        $unmapped[$normalised]['count']++;

        if (!in_array($row['sheet'], $unmapped[$normalised]['sheets'], true)) {
            $unmapped[$normalised]['sheets'][] = $row['sheet'];
        }

        $unmapped[$normalised]['example'] ??= is_string($row['description']) ? $row['description'] : null;
    }

    /**
     * Pair each transfer row with its opposite-sign row on another sheet: same amount, ±3 days.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{paired: list<array<string, mixed>>, unpaired: list<array<string, mixed>>}
     */
    private function pairTransfers(array $rows): array
    {
        $legs = array_values(array_filter($rows, fn(array $row): bool => $row['kind'] === 'transfer'));

        usort($legs, fn(array $a, array $b): int => $a['date']->timestamp <=> $b['date']->timestamp);

        $paired = [];
        $used = [];

        foreach ($legs as $outIndex => $out) {
            if (isset($used[$outIndex]) || $out['flow'] !== PRFLedgerFlow::PAYMENT) {
                continue;
            }

            $best = null;
            $bestDistance = PHP_INT_MAX;

            foreach ($legs as $inIndex => $in) {
                if (
                    isset($used[$inIndex])
                    || $in['flow'] !== PRFLedgerFlow::RECEIPT
                    || $in['sheet'] === $out['sheet']
                ) {
                    continue;
                }

                if ((int) $in['amount'] !== (int) $out['amount']) {
                    continue;
                }

                $distance = abs($in['date']->diffInDays($out['date'], absolute: true));

                if ($distance <= 3 && $distance < $bestDistance) {
                    $best = $inIndex;
                    $bestDistance = $distance;
                }
            }

            if ($best === null) {
                continue;
            }

            $used[$outIndex] = true;
            $used[$best] = true;

            $in = $legs[$best];
            $paired[] = [
                'from_sheet' => $out['sheet'],
                'to_sheet' => $in['sheet'],
                'amount' => (int) $out['amount'],
                'transferred_on' => $out['date']->toDateString(),
                'out_key' => $out['sheet'] . '|' . $out['row'],
                'in_key' => $in['sheet'] . '|' . $in['row'],
                'reference' => $out['reference'] ?? $in['reference'],
                'description' => $out['description'] ?? $in['description'],
            ];
        }

        $unpaired = [];

        foreach ($legs as $index => $leg) {
            if (isset($used[$index])) {
                continue;
            }

            $unpaired[] = [
                'sheet' => $leg['sheet'],
                'row' => $leg['row'],
                'date' => $leg['date']->toDateString(),
                'amount' => $leg['amount'],
                'flow' => $leg['flow']->getLabel(),
                'description' => $leg['description'],
            ];
        }

        return ['paired' => $paired, 'unpaired' => $unpaired];
    }

    /**
     * Rows whose label doesn't resolve to a chart category — plus every transfer row when the
     * transfer category itself is missing — can't be posted, so they join the unmapped list.
     *
     * @param  array{rows: list<array<string, mixed>>, sheets: array<string, array{type: PRFFinancialAccountType}>, skipped: list<array{sheet: string, row: int, reason: string}>, unmapped: array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>}  $parsed
     * @param  array<string, int>  $labelToCategoryId
     */
    private function demoteRowsWithMissingCategories(array &$parsed, array $labelToCategoryId): void
    {
        $transferMissing = LedgerCategory::query()->where('code', 'transfer')->first() === null;

        foreach ($parsed['rows'] as $index => $row) {
            if ($row['kind'] === 'transfer') {
                if ($transferMissing) {
                    $parsed['rows'][$index]['kind'] = 'unmapped';
                    $parsed['rows'][$index]['category_code'] = null;
                    $this->trackUnmapped($parsed['unmapped'], $row);
                }

                continue;
            }

            if (in_array($row['kind'], ['mapped', 'opening'], true) && !isset($labelToCategoryId[$row['normalised']])) {
                $parsed['rows'][$index]['kind'] = 'unmapped';
                $parsed['rows'][$index]['category_code'] = null;
                $this->trackUnmapped($parsed['unmapped'], $row);
            }
        }
    }

    /**
     * @return array{date: int, counterparty: int, description: int, reference: int, receipts: int, payments: int, desk: int|null}
     */
    private function detectColumns(Worksheet $sheet): array
    {
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $headers = [];

        for ($col = 1; $col <= $maxCol; $col++) {
            $headers[$col] = self::normaliseLabel($this->cellText($sheet, $col, 2));
        }

        $columns = [
            'date' => 1,
            'counterparty' => 2,
            'description' => 3,
            'reference' => 4,
            'receipts' => 5,
            'payments' => 6,
            'desk' => 9,
        ];
        $found = [
            'date' => false,
            'counterparty' => false,
            'description' => false,
            'reference' => false,
            'receipts' => false,
            'payments' => false,
            'desk' => false,
        ];

        foreach ($headers as $col => $header) {
            if ($header === '') {
                continue;
            }

            if (!$found['desk'] && (str_contains($header, 'desk') || $header === 'category' || $header === 'vote')) {
                $columns['desk'] = $col;
                $found['desk'] = true;
            } elseif (!$found['date'] && str_contains($header, 'date')) {
                $columns['date'] = $col;
                $found['date'] = true;
            } elseif (
                !$found['counterparty']
                && (
                    str_contains($header, 'payment to')
                    || str_contains($header, 'receipt from')
                    || str_contains($header, 'received from')
                    || str_contains($header, 'paid to')
                    || str_contains($header, 'to/from')
                    || str_contains($header, 'payee')
                    || $header === 'payer'
                )
            ) {
                $columns['counterparty'] = $col;
                $found['counterparty'] = true;
            } elseif (
                !$found['description']
                && (
                    str_contains($header, 'description')
                    || str_contains($header, 'detail')
                    || str_contains($header, 'narration')
                    || str_contains($header, 'particular')
                )
            ) {
                $columns['description'] = $col;
                $found['description'] = true;
            } elseif (
                !$found['reference']
                && (
                    str_contains($header, 'reference')
                    || str_contains($header, 'receipt no')
                    || str_contains($header, 'receipt number')
                    || str_contains($header, 'voucher')
                    || str_contains($header, 'cheque')
                    || $header === 'ref'
                )
            ) {
                $columns['reference'] = $col;
                $found['reference'] = true;
            } elseif (
                !$found['payments']
                && (
                    str_contains($header, 'payment')
                    || str_contains($header, 'charge')
                    || str_contains($header, 'withdraw')
                    || str_contains($header, 'expense')
                    || str_contains($header, 'paid')
                    || str_contains($header, ' out')
                )
            ) {
                $columns['payments'] = $col;
                $found['payments'] = true;
            } elseif (
                !$found['receipts']
                && (
                    str_contains($header, 'receipt')
                    || str_contains($header, 'deposit')
                    || str_contains($header, 'debit')
                    || str_contains($header, 'credit')
                    || str_contains($header, 'received')
                    || str_contains($header, ' in')
                )
            ) {
                $columns['receipts'] = $col;
                $found['receipts'] = true;
            }
        }

        if (!$found['desk']) {
            $columns['desk'] = null;
        }

        return $columns;
    }

    private function cellValue(Worksheet $sheet, ?int $column, int $row): mixed
    {
        if ($column === null) {
            return null;
        }

        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);

        try {
            return $cell->getCalculatedValue();
        } catch (Throwable) {
            try {
                return $cell->getValue();
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function cellText(Worksheet $sheet, ?int $column, int $row): ?string
    {
        $value = $this->cellValue($sheet, $column, $row);

        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_bool($value)) {
            return null;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function parseAmount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        if (is_bool($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $amount = (int) round((float) $value);

            return $amount === 0 ? null : $amount;
        }

        $cleaned = str_ireplace(['kes', 'ksh', '/=', ',', ' '], '', trim((string) $value));

        if (!is_numeric($cleaned)) {
            return null;
        }

        $amount = (int) round((float) $cleaned);

        return $amount === 0 ? null : $amount;
    }

    private function parseDate(mixed $value, Worksheet $sheet, ?int $column, int $row): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial > 20000 && $serial < 80000) {
                try {
                    return Carbon::instance(Date::excelToDateTimeObject($serial))->startOfDay();
                } catch (Throwable) {
                    return null;
                }
            }

            if ($column !== null) {
                try {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);

                    if (Date::isDateTime($cell)) {
                        return Carbon::instance(Date::excelToDateTimeObject($serial))->startOfDay();
                    }
                } catch (Throwable) {
                    return null;
                }
            }

            return null;
        }

        $text = trim((string) $value);

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'j M Y', 'd M Y', 'j M y', 'd M y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $text);

                return $date->startOfDay();
            } catch (Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($text)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function defaultAccountNames(): array
    {
        $names = [];

        foreach ((array) config('prf.finance.accounts', []) as $account) {
            $type = $account['type'] ?? null;
            $value = $type instanceof PRFFinancialAccountType ? $type->value : (int) $type;
            $names[$value] = (string) ($account['name'] ?? '');
        }

        return $names;
    }

    /**
     * @param  list<string>  $created
     */
    private function ensureAccount(PRFFinancialAccountType $type, string $sheetName, array &$created): FinancialAccount
    {
        $account = FinancialAccount::query()->active()->where('type', $type)->orderBy('id')->first();

        if ($account instanceof FinancialAccount) {
            return $account;
        }

        $names = $this->defaultAccountNames();

        $account = CreateAccountJob::dispatchSync([
            'name' => $names[$type->value] ?? $sheetName,
            'type' => $type->value,
            'is_active' => true,
        ]);

        $created[] = $account->name;

        return $account;
    }

    /**
     * New categories from the import mapping are created here, so the treasurer's inline
     * choices in the preview become real categories before any line is posted.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{array<string, int>, list<string>}
     */
    private function resolveCategories(array $rows, array $mapping): array
    {
        $byId = [];
        $created = [];
        $codes = [];

        foreach ($rows as $row) {
            if (!in_array($row['kind'], ['mapped', 'opening'], true) || !is_string($row['category_code'])) {
                continue;
            }

            $codes[$row['normalised']] = $row['category_code'];
        }

        foreach ($codes as $normalised => $code) {
            $override = $mapping[$normalised] ?? null;

            if (is_array($override)) {
                $byId[$normalised] = $this->ensureMappedCategory($normalised, $override, $created)->id;

                continue;
            }

            $category = $this->findCategory($code);

            if ($category instanceof LedgerCategory) {
                $byId[$normalised] = $category->id;
            }
        }

        foreach ($mapping as $normalised => $override) {
            if (!is_array($override) || isset($byId[$normalised])) {
                continue;
            }

            $byId[$normalised] = $this->ensureMappedCategory((string) $normalised, $override, $created)->id;
        }

        return [$byId, $created];
    }

    /**
     * Find a category by code, or by `ulid:{ulid}` for custom categories created inline.
     */
    private function findCategory(string $code): ?LedgerCategory
    {
        if (str_starts_with($code, 'ulid:')) {
            return LedgerCategory::query()
                ->where('ulid', substr($code, strlen('ulid:')))
                ->first();
        }

        return LedgerCategory::query()->where('code', $code)->first();
    }

    /**
     * @param  array{name?: string, kind?: int|string, responsible_desk?: int|null}  $spec
     * @param  list<string>  $created
     */
    private function ensureMappedCategory(string $normalised, array $spec, array &$created): LedgerCategory
    {
        $name = trim((string) ($spec['name'] ?? ''));

        if ($name === '') {
            $name = ucwords($normalised);
        }

        $existing = LedgerCategory::query()->where('name', $name)->first();

        if ($existing instanceof LedgerCategory) {
            return $existing;
        }

        $kind = $spec['kind'] ?? null;
        $kindValue = $kind instanceof \BackedEnum ? $kind->value : (int) $kind;

        $category = CreateCategoryJob::dispatchSync(array_filter(
            [
                'name' => $name,
                'kind' => $kindValue,
                'responsible_desk' => isset($spec['responsible_desk']) ? (int) $spec['responsible_desk'] : null,
                'is_active' => true,
            ],
            fn(mixed $value): bool => $value !== null,
        ));

        $created[] = $category->name;

        return $category;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceKey(array $row): string
    {
        return 'import:'
        . sha1(implode('|', [
            $row['sheet'],
            $row['row'],
            $row['date']->toDateString(),
            $row['amount'],
            $row['description'] ?? '',
        ]));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function postTransferLine(LedgerImport $import, FinancialAccount $account, array $row): void
    {
        $category = $this->chart->transfer();
        $sourceKey = $this->sourceKey($row);

        if (LedgerEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
            return;
        }

        $this->ledger->post([
            'financial_account_id' => $account->id,
            'ledger_category_id' => $category->id,
            'flow' => $row['flow'],
            'channel' => PRFLedgerChannel::defaultFor($account->type),
            'amount' => $row['amount'],
            'transacted_on' => $row['date']->toDateString(),
            'counterparty' => $row['counterparty'],
            'description' => $row['description'],
            'reference' => $row['reference'],
            'ledger_import_id' => $import->id,
            'recorded_by' => $import->imported_by,
            'source_key' => $sourceKey,
        ]);
    }

    /**
     * A paired transfer becomes one AccountTransfer via its job, so both balances move and the
     * lines keep the job's transfer:{id} source keys (never the import key, which would break
     * the transfer update replay). A matching transfer already on the books means a re-import.
     *
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, mixed>  $pair
     */
    private function postTransferPair(LedgerImport $import, array $accounts, array $pair): bool
    {
        $from = $accounts[$pair['from_sheet']];
        $to = $accounts[$pair['to_sheet']];

        $exists = AccountTransfer::query()
            ->where('from_financial_account_id', $from->id)
            ->where('to_financial_account_id', $to->id)
            ->where('amount', $pair['amount'])
            ->whereDate('transferred_on', $pair['transferred_on'])
            ->exists();

        if ($exists) {
            return false;
        }

        $transfer = CreateTransferJob::dispatchSync([
            'from_financial_account_ulid' => $from->ulid,
            'to_financial_account_ulid' => $to->ulid,
            'amount' => $pair['amount'],
            'transferred_on' => $pair['transferred_on'],
            'reference' => $pair['reference'],
            'description' => $pair['description'] ?? "Transfer from {$from->name} to {$to->name}",
            'recorded_by' => $import->imported_by,
        ]);

        /** Link the transfer's lines to this import without touching their source keys. */
        $transfer
            ->ledgerEntries()
            ->get()
            ->each(fn(LedgerEntry $entry) => $entry->updateQuietly([
                'ledger_import_id' => $import->id,
                'recorded_by' => $import->imported_by,
            ]));

        return true;
    }
}
