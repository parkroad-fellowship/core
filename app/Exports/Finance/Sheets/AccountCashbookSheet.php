<?php

namespace App\Exports\Finance\Sheets;

use App\Exports\Finance\Concerns\StylesTreasurerSheets;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One account's cashbook, like the treasurer's "Paybill 2026" sheet: opening balance, every
 * receipt and payment with its desk/category, and a running balance kept as formulas.
 */
class AccountCashbookSheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    public function __construct(
        private readonly FinancialAccount $account,
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly int $openingBalance,
        private readonly string $organisation,
    ) {}

    public function title(): string
    {
        return self::sheetTitle("{$this->account->name} {$this->from->format('Y')}");
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [AfterSheet::class => fn(AfterSheet $event) => $this->write($event->sheet->getDelegate())];
    }

    private function write(Worksheet $sheet): void
    {
        $this->heading(
            $sheet,
            'A1:H1',
            "{$this->organisation} {$this->from->format('Y')} {$this->account->name} Account",
        );

        $sheet->fromArray(
            [[
                'Date',
                'Payment To / Receipt From',
                'Description/particulars',
                'Receipt No.',
                'Receipts',
                'Payments',
                'Balance',
                'Desk / Category',
            ]],
            null,
            'A2',
        );
        $this->header($sheet, 'A2:H2');
        $sheet->freezePane('A3');

        $sheet->fromArray(
            [[
                Date::PHPToExcel($this->from->copy()->startOfDay()),
                'Opening Balance',
                'Opening Balance',
                null,
                null,
                null,
                $this->openingBalance,
                null,
            ]],
            null,
            'A3',
        );

        $row = 3;

        LedgerEntry::query()
            ->where('financial_account_id', $this->account->id)
            ->between($this->from, $this->to)
            ->with('ledgerCategory')
            ->orderBy('transacted_on')
            ->orderBy('id')
            ->cursor()
            ->each(function (LedgerEntry $entry) use ($sheet, &$row): void {
                $row++;
                $isReceipt = $entry->signed_amount > 0;

                $sheet->fromArray(
                    [[
                        Date::PHPToExcel($entry->transacted_on),
                        $entry->counterparty,
                        $entry->description,
                        $entry->receipt_number ?? $entry->reference,
                        $isReceipt ? $entry->amount : null,
                        $isReceipt ? null : $entry->amount,
                        '=G' . ($row - 1) . "+E{$row}-F{$row}",
                        $entry->ledgerCategory?->name,
                    ]],
                    null,
                    "A{$row}",
                );
            });

        $total = $row + 1;
        $sheet->setCellValue("B{$total}", 'TOTAL');
        $sheet->setCellValue("E{$total}", "=SUM(E4:E{$row})");
        $sheet->setCellValue("F{$total}", "=SUM(F4:F{$row})");
        $sheet->setCellValue("G{$total}", "=G{$row}");

        $sheet->getStyle("A3:A{$total}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $this->money($sheet, "E3:G{$total}");
        $this->borders($sheet, "A2:H{$total}");
        $this->totals($sheet, "A{$total}:H{$total}");
        $this->widths($sheet, ['A' => 12, 'B' => 30, 'C' => 38, 'D' => 18, 'E' => 14, 'F' => 14, 'G' => 15, 'H' => 28]);
    }
}
