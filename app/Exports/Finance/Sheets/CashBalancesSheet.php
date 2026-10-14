<?php

namespace App\Exports\Finance\Sheets;

use App\Exports\Finance\Concerns\StylesTreasurerSheets;
use App\Models\FinancialAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashBalancesSheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    /**
     * @param  Collection<int, array{account: FinancialAccount, opening: int, receipts: int, payments: int, closing: int}>  $balances
     */
    public function __construct(
        private readonly Collection $balances,
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly string $organisation,
    ) {}

    public function title(): string
    {
        return 'Cash Balances';
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
            'A1:E1',
            "{$this->organisation} Cash Balances, {$this->from->format('j M Y')} to {$this->to->format('j M Y')}",
        );
        $sheet->fromArray([['Account', 'Opening Balances', 'Receipts', 'Payments', 'Closing Balances']], null, 'A2');
        $this->header($sheet, 'A2:E2');

        $row = 2;
        foreach ($this->balances as $balance) {
            $row++;
            $sheet->fromArray(
                [[
                    strtoupper($balance['account']->name),
                    $balance['opening'],
                    $balance['receipts'],
                    $balance['payments'],
                    "=B{$row}+C{$row}-D{$row}",
                ]],
                null,
                "A{$row}",
            );
        }

        $total = $row + 1;
        $sheet->setCellValue("A{$total}", 'TOTAL');
        foreach (['B', 'C', 'D', 'E'] as $column) {
            $sheet->setCellValue("{$column}{$total}", "=SUM({$column}3:{$column}{$row})");
        }

        $this->money($sheet, "B3:E{$total}");
        $this->borders($sheet, "A2:E{$total}");
        $this->totals($sheet, "A{$total}:E{$total}");
        $this->widths($sheet, ['A' => 22, 'B' => 18, 'C' => 16, 'D' => 16, 'E' => 18]);
    }
}
