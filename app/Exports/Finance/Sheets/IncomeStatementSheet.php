<?php

namespace App\Exports\Finance\Sheets;

use App\Exports\Finance\Concerns\StylesTreasurerSheets;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Statement of comprehensive income: receipts by line, expenditure by desk, surplus — with the
 * same period of the previous year alongside.
 */
class IncomeStatementSheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    /**
     * @param  array{receipts: array<string, int>, expenditure: array<string, int>}  $current
     * @param  array{receipts: array<string, int>, expenditure: array<string, int>}  $previous
     */
    public function __construct(
        private readonly array $current,
        private readonly array $previous,
        private readonly Carbon $to,
        private readonly string $organisation,
    ) {}

    public function title(): string
    {
        return 'Income Statement';
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
        $this->heading($sheet, 'A1:C1', strtoupper($this->organisation));
        $this->heading($sheet, 'A2:C2', 'STATEMENT OF COMPREHENSIVE INCOME', 12);
        $this->heading($sheet, 'A3:C3', 'FOR THE PERIOD ENDED ' . strtoupper($this->to->format('jS F Y')), 11);

        $year = (int) $this->to->format('Y');
        $row = $this->section($sheet, 5, 'RECEIPTS', $year, 'receipts');
        $receiptsTotal = $row;
        $row = $this->section($sheet, $row + 2, 'EXPENDITURE', $year, 'expenditure');
        $expenditureTotal = $row;

        $surplus = $row + 2;
        $sheet->setCellValue("A{$surplus}", 'SURPLUS / (DEFICIT) FOR THE PERIOD');
        $sheet->setCellValue("B{$surplus}", "=B{$receiptsTotal}-B{$expenditureTotal}");
        $sheet->setCellValue("C{$surplus}", "=C{$receiptsTotal}-C{$expenditureTotal}");
        $this->totals($sheet, "A{$surplus}:C{$surplus}");
        $this->money($sheet, "B6:C{$surplus}");

        $this->widths($sheet, ['A' => 44, 'B' => 18, 'C' => 18]);
    }

    /**
     * @param  'receipts'|'expenditure'  $key
     * @return int the row holding the section total
     */
    private function section(Worksheet $sheet, int $row, string $heading, int $year, string $key): int
    {
        $sheet->fromArray([[$heading, (string) $year, (string) ($year - 1)]], null, "A{$row}");
        $sheet->fromArray([[null, 'Kshs', 'Kshs']], null, 'A' . ($row + 1));
        $this->header($sheet, "A{$row}:C" . ($row + 1));

        $first = $row + 2;
        $row = $first - 1;
        $lines = array_unique([...array_keys($this->current[$key]), ...array_keys($this->previous[$key])]);

        foreach ($lines as $line) {
            $row++;
            $sheet->fromArray(
                [[$line, $this->current[$key][$line] ?? 0, $this->previous[$key][$line] ?? 0]],
                null,
                "A{$row}",
            );
        }

        $total = $row + 1;
        $sheet->setCellValue("A{$total}", 'TOTAL');
        $sheet->setCellValue("B{$total}", "=SUM(B{$first}:B{$row})");
        $sheet->setCellValue("C{$total}", "=SUM(C{$first}:C{$row})");
        $this->borders($sheet, "A{$first}:C{$total}");
        $this->totals($sheet, "A{$total}:C{$total}");

        return $total;
    }
}
