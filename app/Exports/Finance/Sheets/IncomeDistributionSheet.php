<?php

namespace App\Exports\Finance\Sheets;

use App\Exports\Finance\Concerns\StylesTreasurerSheets;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Income per month by designation, then by means of giving (Paybill, M-Pesa, bank, cash…).
 */
class IncomeDistributionSheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    /**
     * @param  array{months: list<string>, byLine: array<string, array<string, int>>, byChannel: array<string, array<string, int>>}  $distribution
     */
    public function __construct(
        private readonly array $distribution,
        private readonly string $organisation,
    ) {}

    public function title(): string
    {
        return 'Income Distribution';
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
        $months = $this->distribution['months'];
        $lastColumn = Coordinate::stringFromColumnIndex(count($months) + 2);

        $this->heading($sheet, "A1:{$lastColumn}1", "{$this->organisation} Income Distribution");

        $row = $this->table($sheet, 3, 'Designated for', $this->distribution['byLine']);
        $this->table($sheet, $row + 3, 'Means of giving', $this->distribution['byChannel']);

        $sheet->getColumnDimension('A')->setWidth(34);
        for ($column = 2; $column <= (count($months) + 2); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(14);
        }
    }

    /**
     * @param  array<string, array<string, int>>  $rows
     * @return int the totals row
     */
    private function table(Worksheet $sheet, int $row, string $heading, array $rows): int
    {
        $months = $this->distribution['months'];
        $monthCount = count($months);
        $totalColumn = Coordinate::stringFromColumnIndex($monthCount + 2);
        $lastMonthColumn = Coordinate::stringFromColumnIndex($monthCount + 1);

        $sheet->fromArray([[$heading, ...$months, 'Total']], null, "A{$row}");
        $this->header($sheet, "A{$row}:{$totalColumn}{$row}");

        $first = $row + 1;
        foreach ($rows as $label => $amounts) {
            $row++;
            $sheet->fromArray([[$label, ...array_values($amounts)]], null, "A{$row}");
            $sheet->setCellValue("{$totalColumn}{$row}", "=SUM(B{$row}:{$lastMonthColumn}{$row})");
        }

        $total = $row + 1;
        $sheet->setCellValue("A{$total}", 'TOTAL');
        for ($column = 2; $column <= ($monthCount + 2); $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->setCellValue("{$letter}{$total}", "=SUM({$letter}{$first}:{$letter}{$row})");
        }

        $this->money($sheet, "B{$first}:{$totalColumn}{$total}");
        $this->borders($sheet, "A{$first}:{$totalColumn}{$total}");
        $this->totals($sheet, "A{$total}:{$totalColumn}{$total}");

        return $total;
    }
}
