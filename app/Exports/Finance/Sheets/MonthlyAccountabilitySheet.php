<?php

namespace App\Exports\Finance\Sheets;

use App\Exports\Finance\Concerns\StylesTreasurerSheets;
use App\Models\ExpenseCategory;
use App\Services\Finance\AccountabilityRow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One month of the missions/desks accountability workbook: disbursed, real expenses by
 * category, token, surplus to refund, refund done and balance — balance should be zero.
 */
class MonthlyAccountabilitySheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    /**
     * @param  Collection<int, AccountabilityRow>  $rows
     * @param  Collection<int, ExpenseCategory>  $categories
     */
    public function __construct(
        private readonly Carbon $month,
        private readonly Collection $rows,
        private readonly Collection $categories,
    ) {}

    public function title(): string
    {
        return $this->month->format('F Y');
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
        $expenseColumns = $this->categories->count() + 1; // + transaction costs
        $col = fn(int $index): string => Coordinate::stringFromColumnIndex($index);

        // A S/NO, B Date, C Event, D Desk, E Disbursed, then expenses, then the reconciliation columns.
        $firstExpense = 6;
        $lastExpense = $firstExpense + $expenseColumns - 1;
        $token = $lastExpense + 1;
        $toRefund = $token + 1;
        $refunded = $toRefund + 1;
        $balance = $refunded + 1;
        $remarks = $balance + 1;
        $status = $remarks + 1;

        foreach ([
            1 => 'S/NO',
            2 => 'Date',
            3 => 'Accounting event',
            4 => 'Desk',
            5 => 'Amount disbursed (Ksh)',
            $token => 'Token of appreciation',
            $toRefund => "Surplus/\nDeficit (To be refunded)",
            $refunded => 'Refund Done',
            $balance => 'Balance',
            $remarks => 'Remarks',
            $status => 'Status',
        ] as $index => $label) {
            $sheet->setCellValue($col($index) . '1', $label);
            $sheet->mergeCells($col($index) . '1:' . $col($index) . '2');
        }

        $sheet->setCellValue($col($firstExpense) . '1', 'Expenses');
        $sheet->mergeCells($col($firstExpense) . '1:' . $col($lastExpense) . '1');
        foreach ($this->categories->values() as $offset => $category) {
            $sheet->setCellValue($col($firstExpense + $offset) . '2', $category->name);
        }
        $sheet->setCellValue($col($lastExpense) . '2', 'Transaction costs');

        $this->header($sheet, 'A1:' . $col($status) . '2');
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->freezePane('D3');

        $row = 2;
        foreach ($this->rows->values() as $index => $line) {
            $row++;
            $values = [
                $index + 1,
                Date::PHPToExcel($line->event->due_date),
                $line->event->name,
                $line->event->responsible_desk?->getLabel(),
                $line->disbursed,
            ];
            foreach ($this->categories as $category) {
                $values[] = $line->expenses[$category->id] ?? 0;
            }
            $values[] = $line->transactionCosts;
            $values[] = $line->tokens;
            $sheet->fromArray([$values], null, "A{$row}");

            $expenseRange = $col($firstExpense) . $row . ':' . $col($lastExpense) . $row;
            $sheet->setCellValue($col($toRefund) . $row, "=E{$row}-SUM({$expenseRange})+" . $col($token) . $row);
            $sheet->setCellValue($col($refunded) . $row, $line->refunded);
            $sheet->setCellValue($col($balance) . $row, '=' . $col($toRefund) . $row . '-' . $col($refunded) . $row);
            $sheet->setCellValue($col($remarks) . $row, $line->remarks());
            $sheet->setCellValue($col($status) . $row, $line->status()->getLabel());
        }

        $total = $row + 1;
        $sheet->setCellValue("C{$total}", 'TOTAL');
        for ($index = 5; $index <= $balance; $index++) {
            $sheet->setCellValue($col($index) . $total, '=SUM(' . $col($index) . '3:' . $col($index) . "{$row})");
        }

        $sheet->getStyle("B3:B{$total}")->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $this->money($sheet, "E3:{$col($balance)}{$total}");
        $this->borders($sheet, "A1:{$col($status)}{$total}");
        $this->totals($sheet, "A{$total}:{$col($status)}{$total}");
        $this->widths($sheet, ['A' => 6, 'B' => 11, 'C' => 38, 'D' => 18, 'E' => 14]);
        for ($index = $firstExpense; $index <= $balance; $index++) {
            $sheet->getColumnDimension($col($index))->setWidth(13);
        }
        $sheet->getColumnDimension($col($remarks))->setWidth(42);
        $sheet->getColumnDimension($col($status))->setWidth(16);
    }
}
