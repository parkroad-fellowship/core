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
 * @phpstan-type ParsedRow array{sheet: string, row: int, date: Carbon, counterparty: string|null, description: string|null, reference: string|null, amount: int, flow: PRFLedgerFlow, label: string|null, normalised: string, kind: string, category_code: string|null, date_inherited: bool, occurrence: int}
 */
class WorkbookImporter
{
    private const TRANSFER_LABELS = ['inter a/c transfer', 'inter account transfer', 'inter-account transfer'];

    /**
     * @var array<string, string>
     */
    private array $categoryNames = [];

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
                        'category' => $this->categoryLabel($row),
                        'date_inherited' => $row['date_inherited'],
                    ];
                }

                if ($row['kind'] !== 'unmapped' && $row['category_code'] !== null) {
                    $label = $this->categoryLabel($row);
                    $preview->mappedCounts[$label] = ($preview->mappedCounts[$label] ?? 0) + 1;
                }

                if ($row['date_inherited']) {
                    $preview->inheritedDateRows++;
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

        [$codeToCategoryId, $categoriesCreated] = $this->resolveCategories($parsed['rows'], $mapping);

        $this->demoteRowsWithMissingCategories($parsed, $codeToCategoryId);

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

            $categoryId = $codeToCategoryId[(string) $row['category_code']] ?? null;

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
            $result = $this->postTransferPair($import, $accounts, $pair);

            match ($result) {
                'paired' => [$summary['transfers_paired']++, $summary['posted'] += 2],
                'completed' => [$summary['transfers_unpaired']++, $summary['posted']++],
                default => $summary['skipped'] += 2,
            };
        }

        return $summary;
    }

    /**
     * What the treasurer sees for a row's category in the preview: the category name when it
     * exists, the name they typed for a new one, or "Unmapped".
     *
     * @param  array<string, mixed>  $row
     */
    private function categoryLabel(array $row): string
    {
        $code = $row['category_code'];

        if (!is_string($code)) {
            return 'Unmapped';
        }

        if (str_starts_with($code, 'new:')) {
            return 'New: ' . ucwords(substr($code, strlen('new:')));
        }

        return $this->categoryNames[$code] ??= $this->findCategory($code)?->name ?? $code;
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

        // Identical movements on the same sheet and day are told apart by their order.
        $seen = [];
        foreach ($parsed['rows'] as $index => $row) {
            $identity = $this->identity($row);
            $parsed['rows'][$index]['occurrence'] = $seen[$identity] = ($seen[$identity] ?? 0) + 1;
        }

        return $parsed;
    }

    /**
     * @param  array{rows: list<array<string, mixed>>, sheets: array<string, array{type: PRFFinancialAccountType}>, skipped: list<array{sheet: string, row: int, reason: string}>, unmapped: array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>}  $parsed
     * @param  array<string, string|array<string, mixed>>  $mapping
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
        $lastDate = null;

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

            $cell = [
                'sheet' => $sheetName,
                'row' => $rowNumber,
                'counterparty' => $counterparty,
                'description' => $description,
                'reference' => $reference,
                'label' => $label,
            ];

            if ($this->isOpeningBalance($rowNumber, $counterparty, $description, $label)) {
                // An opening balance is the net of the row, dated 1 January.
                $net = ($receipts ?? 0) - ($payments ?? 0);

                if ($net !== 0) {
                    $parsed['rows'][] = [
                        ...$cell,
                        'date' => Carbon::create($year, 1, 1)->startOfDay(),
                        'description' => $description ?? 'Opening balance',
                        'amount' => abs($net),
                        'flow' => $net > 0 ? PRFLedgerFlow::RECEIPT : PRFLedgerFlow::PAYMENT,
                        'normalised' => 'opening_balance',
                        'kind' => 'opening',
                        'category_code' => 'opening_balance',
                        'date_inherited' => false,
                    ];
                }

                continue;
            }

            $date = $this->parseDate(
                $this->cellValue($sheet, $columns['date'], $rowNumber),
                $sheet,
                $columns['date'],
                $rowNumber,
            );
            $dateInherited = false;

            // The treasurer leaves the date blank on follow-on rows of the same day.
            if (!$date instanceof Carbon && $lastDate instanceof Carbon) {
                $date = $lastDate->copy();
                $dateInherited = true;
            }

            if (!$date instanceof Carbon) {
                $parsed['skipped'][] = ['sheet' => $sheetName, 'row' => $rowNumber, 'reason' => 'No date'];

                continue;
            }

            $lastDate = $date;

            // A row can carry both a receipt and a payment (e.g. a refund and its charge).
            foreach ([
                [$receipts, PRFLedgerFlow::RECEIPT],
                [$payments, PRFLedgerFlow::PAYMENT],
            ] as [$amount, $flow]) {
                if ($amount === null) {
                    continue;
                }

                if ($amount < 0) {
                    $amount = abs($amount);
                    $flow = $flow === PRFLedgerFlow::RECEIPT ? PRFLedgerFlow::PAYMENT : PRFLedgerFlow::RECEIPT;
                }

                $this->classify(
                    $parsed,
                    [
                        ...$cell,
                        'date' => $date,
                        'amount' => $amount,
                        'flow' => $flow,
                        'date_inherited' => $dateInherited,
                    ],
                    $type,
                    $mapping,
                );
            }
        }
    }

    /**
     * Decide the category of one cashbook movement and add it to the parsed rows.
     *
     * @param  array{rows: list<array<string, mixed>>, sheets: array<string, array{type: PRFFinancialAccountType}>, skipped: list<array{sheet: string, row: int, reason: string}>, unmapped: array<string, array{label: string, count: int, sheets: list<string>, example: string|null}>}  $parsed
     * @param  array<string, mixed>  $row
     * @param  array<string, string|array<string, mixed>>  $mapping
     */
    private function classify(array &$parsed, array $row, PRFFinancialAccountType $type, array $mapping): void
    {
        $labelNorm = self::normaliseLabel($row['label']);
        $descNorm = self::normaliseLabel($row['description']);
        $key = self::rowKey($row['label'], $row['description'], $row['counterparty']);
        $row['normalised'] = $key;
        $row['kind'] = 'mapped';
        $row['category_code'] = null;

        $explicit = $this->lookupCode($key, $mapping, configured: false);

        // Transaction charges: the whole description says "charges", or the desk is the charges line.
        if (
            $explicit === null
            && (
                preg_match('/^(transaction |bank |m-?pesa |mpesa )?charges?$/', $descNorm) === 1
                || str_contains($labelNorm, 'transaction cost')
                || str_contains($labelNorm, 'charges')
            )
        ) {
            $row['category_code'] = 'charge.transaction_costs';
            $parsed['rows'][] = $row;

            return;
        }

        if ($explicit === null) {
            foreach (self::TRANSFER_LABELS as $transferLabel) {
                if (str_contains($labelNorm, $transferLabel) || str_contains($descNorm, $transferLabel)) {
                    $row['kind'] = 'transfer';
                    $row['category_code'] = 'transfer';
                    $parsed['rows'][] = $row;

                    return;
                }
            }
        }

        $code = $explicit ?? $this->lookupCode($key, $mapping);

        // M-Shwari has no desk column: money in is interest (or other receipts) unless mapped.
        if (
            $code === null
            && $type === PRFFinancialAccountType::MSHWARI
            && $labelNorm === ''
            && $row['flow'] === PRFLedgerFlow::RECEIPT
        ) {
            $code = str_contains($descNorm, 'interest') ? 'income.interest' : 'income.other';
        }

        if ($code === null) {
            $row['kind'] = 'unmapped';
            $parsed['rows'][] = $row;
            $this->trackUnmapped($parsed['unmapped'], $row);

            return;
        }

        // The workbook books refunds under the desk: a receipt against an expense desk is
        // money returned, never income.
        if ($row['flow'] === PRFLedgerFlow::RECEIPT && str_starts_with($code, 'expense.desk.')) {
            $code = 'refund.desk.' . substr($code, strlen('expense.desk.'));
        }

        $row['category_code'] = $code;
        $parsed['rows'][] = $row;
    }

    /**
     * The label a row is mapped by: its desk/category, else its description, else its counterparty.
     */
    public static function rowKey(?string $label, ?string $description, ?string $counterparty): string
    {
        foreach ([$label, $description, $counterparty] as $candidate) {
            $normalised = self::normaliseLabel($candidate);

            if ($normalised !== '') {
                return $normalised;
            }
        }

        return '(blank)';
    }

    private function isOpeningBalance(int $rowNumber, ?string $counterparty, ?string $description, ?string $label): bool
    {
        foreach ([$counterparty, $description, $label] as $text) {
            $normalised = self::normaliseLabel($text);

            if (
                str_contains($normalised, 'opening balance')
                || str_starts_with($normalised, 'bal c/f')
                || str_contains($normalised, 'balance b/f')
                || str_contains($normalised, 'balance c/f')
            ) {
                return true;
            }
        }

        // Row 3 holds the opening balance, unless it already looks like a normal entry.
        return (
            $rowNumber === 3
            && self::normaliseLabel($counterparty) === ''
            && self::normaliseLabel($description) === ''
        );
    }

    /**
     * The treasurer's mapping wins; `new:{label}` means "create the category they described".
     * With $configured, fall back to the built-in workbook map in config/prf/finance.php.
     *
     * @param  array<string, string|array<string, mixed>>  $mapping
     */
    private function lookupCode(string $normalised, array $mapping, bool $configured = true): ?string
    {
        if ($normalised === '' || $normalised === '(blank)') {
            return null;
        }

        if (isset($mapping[$normalised])) {
            return is_array($mapping[$normalised]) ? 'new:' . $normalised : (string) $mapping[$normalised];
        }

        if (!$configured) {
            return null;
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
        $normalised = (string) $row['normalised'];

        $unmapped[$normalised] ??= [
            'label' => $row['label'] ?? $row['description'] ?? $row['counterparty'] ?? '(blank)',
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
                'out' => $out,
                'in' => $in,
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
     * @param  array<string, int>  $codeToCategoryId
     */
    private function demoteRowsWithMissingCategories(array &$parsed, array $codeToCategoryId): void
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

            if (
                in_array($row['kind'], ['mapped', 'opening'], true)
                && !isset($codeToCategoryId[(string) $row['category_code']])
            ) {
                $parsed['rows'][$index]['kind'] = 'unmapped';
                $parsed['rows'][$index]['category_code'] = null;
                $this->trackUnmapped($parsed['unmapped'], $row);
            }
        }
    }

    /**
     * @return array{date: int, counterparty: int|null, description: int|null, reference: int|null, receipts: int, payments: int, desk: int|null}
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

        // Text columns that aren't in this sheet stay empty rather than reading an amount column.
        foreach (['desk', 'reference', 'counterparty', 'description'] as $optional) {
            if (!$found[$optional]) {
                $columns[$optional] = null;
            }
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
     * Resolve every category code used by the rows to an id. `new:{label}` codes create the
     * category the treasurer described in the preview, so their choices become real categories
     * before any line is posted. One workbook label can land in several categories (a desk's
     * payments are expenses, its receipts are refunds), so rows resolve by code, not by label.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string|array<string, mixed>>  $mapping
     * @return array{0: array<string, int>, 1: list<string>}
     */
    private function resolveCategories(array $rows, array $mapping): array
    {
        $byCode = [];
        $created = [];

        foreach ($rows as $row) {
            $code = $row['category_code'];

            if (!in_array($row['kind'], ['mapped', 'opening'], true) || !is_string($code) || isset($byCode[$code])) {
                continue;
            }

            if (str_starts_with($code, 'new:')) {
                $label = substr($code, strlen('new:'));
                $spec = $mapping[$label] ?? null;

                if (is_array($spec)) {
                    $byCode[$code] = $this->ensureMappedCategory($label, $spec, $created)->id;
                }

                continue;
            }

            $category = $this->findCategory($code);

            if ($category instanceof LedgerCategory) {
                $byCode[$code] = $category->id;
            }
        }

        return [$byCode, $created];
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
     * What makes a movement unique in the workbook, independent of its row number, so inserting a
     * row in the sheet doesn't change the keys of the rows below it.
     *
     * @param  array<string, mixed>  $row
     */
    private function identity(array $row): string
    {
        return implode('|', [
            $row['sheet'],
            $row['date']->toDateString(),
            $row['flow']->value,
            $row['amount'],
            self::normaliseLabel($row['counterparty']),
            self::normaliseLabel($row['description']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceKey(array $row): string
    {
        return 'import:' . sha1($this->identity($row) . '|' . ($row['occurrence'] ?? 1));
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
     * lines keep the job's transfer:{id} source keys. If an earlier import already posted one leg
     * on its own (its partner wasn't in that upload), only the missing leg is posted now. A
     * matching transfer already on the books means a re-import.
     *
     * @param  array<string, FinancialAccount>  $accounts
     * @param  array<string, mixed>  $pair
     * @return 'paired'|'completed'|'skipped'
     */
    private function postTransferPair(LedgerImport $import, array $accounts, array $pair): string
    {
        $from = $accounts[$pair['from_sheet']];
        $to = $accounts[$pair['to_sheet']];

        $outPosted = LedgerEntry::withTrashed()->where('source_key', $this->sourceKey($pair['out']))->exists();
        $inPosted = LedgerEntry::withTrashed()->where('source_key', $this->sourceKey($pair['in']))->exists();

        if ($outPosted && $inPosted) {
            return 'skipped';
        }

        if ($outPosted || $inPosted) {
            [$leg, $account] = $outPosted ? [$pair['in'], $to] : [$pair['out'], $from];
            $this->postTransferLine($import, $account, $leg);

            return 'completed';
        }

        $exists = AccountTransfer::query()
            ->where('from_financial_account_id', $from->id)
            ->where('to_financial_account_id', $to->id)
            ->where('amount', $pair['amount'])
            ->whereDate('transferred_on', $pair['transferred_on'])
            ->exists();

        if ($exists) {
            return 'skipped';
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

        return 'paired';
    }
}
