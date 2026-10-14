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

/**
 * The one-page summary the treasurer shares with the chair: where the money is and how the
 * period went.
 */
class TreasurerReportSheet implements WithEvents, WithTitle
{
    use StylesTreasurerSheets;

    /**
     * @param  Collection<int, array{account: FinancialAccount, opening: int, receipts: int, payments: int, closing: int}>  $balances
     * @param  array{receipts: array<string, int>, expenditure: array<string, int>}  $statement
     */
    public function __construct(
        private readonly Collection $balances,
        private readonly array $statement,
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly string $organisation,
    ) {}

    public function title(): string
    {
        return 'Treasurer Report';
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
        $this->heading($sheet, 'A1:F1', strtoupper($this->organisation) . ' TREASURER REPORT');
        $this->heading($sheet, 'A2:F2', 'AS AT ' . strtoupper($this->to->format('jS F Y')), 11);

        $sheet->fromArray([['Cash balances', 'Opening', 'Current']], null, 'A4');
        $this->header($sheet, 'A4:C4');
        $row = 4;
        foreach ($this->balances as $balance) {
            $row++;
            $sheet->fromArray(
                [[strtoupper($balance['account']->name), $balance['opening'], $balance['closing']]],
                null,
                "A{$row}",
            );
        }
        $balancesTotal = $row + 1;
        $sheet->fromArray([['TOTAL', '=SUM(B5:B' . $row . ')', '=SUM(C5:C' . $row . ')']], null, "A{$balancesTotal}");
        $this->totals($sheet, "A{$balancesTotal}:C{$balancesTotal}");

        $sheet->fromArray([['Receipts', 'Amount']], null, 'E4');
        $this->header($sheet, 'E4:F4');
        $row = 4;
        foreach ($this->statement['receipts'] as $line => $amount) {
            $row++;
            $sheet->fromArray([[$line, $amount]], null, "E{$row}");
        }
        $receiptsTotal = $row + 1;
        $sheet->fromArray([['TOTAL RECEIPTS', "=SUM(F5:F{$row})"]], null, "E{$receiptsTotal}");
        $this->totals($sheet, "E{$receiptsTotal}:F{$receiptsTotal}");

        $expenditureHeader = $receiptsTotal + 2;
        $sheet->fromArray([['Expenditure', 'Amount']], null, "E{$expenditureHeader}");
        $this->header($sheet, "E{$expenditureHeader}:F{$expenditureHeader}");
        $row = $expenditureHeader;
        foreach ($this->statement['expenditure'] as $line => $amount) {
            $row++;
            $sheet->fromArray([[$line, $amount]], null, "E{$row}");
        }
        $expenditureTotal = $row + 1;
        $sheet->fromArray(
            [['TOTAL EXPENDITURE', '=SUM(F' . ($expenditureHeader + 1) . ":F{$row})"]],
            null,
            "E{$expenditureTotal}",
        );
        $this->totals($sheet, "E{$expenditureTotal}:F{$expenditureTotal}");

        $surplus = $expenditureTotal + 2;
        $sheet->fromArray([['SURPLUS / (DEFICIT)', "=F{$receiptsTotal}-F{$expenditureTotal}"]], null, "E{$surplus}");
        $this->totals($sheet, "E{$surplus}:F{$surplus}");

        $this->money($sheet, "B5:C{$balancesTotal}");
        $this->money($sheet, "F5:F{$surplus}");
        $this->widths($sheet, ['A' => 22, 'B' => 16, 'C' => 16, 'D' => 4, 'E' => 40, 'F' => 16]);
    }
}
