<?php

namespace App\Exports\MissionExpense;

use App\Enums\PRFMissionRole;
use App\Models\MissionExpense;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Report extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithCustomValueBinder, WithMapping, WithStyles
{
    public function __construct(
        public int $missionExpenseId,
    ) {}

    public function query()
    {
        return MissionExpense::query()
            ->with([
                'mission',
                'expenses',
                'expenses.expenseCategory',
                'expenses.receipts',
                'mission.missionSubscriptions.member',
            ])
            ->where('id', $this->missionExpenseId)
            ->limit(1);
    }

    public function map($missionExpense): array
    {
        return [
            [
                'PARKROAD FELLOWSHIP',
            ],
            [
                'FUNDS EXPENSE REPORT',
            ],
            [],
            [
                '',
                'PERSON EXPENSING:',
                '',
                '',
                '',
                '',
                $missionExpense->mission
                    ->missionSubscriptions->firstWhere('mission_role', PRFMissionRole::LEADER->value)
                    ?->member?->full_name,
            ],
            [
                '',
                'DESK:',
                '',
                '',
                '',
                '',
                'MISSIONS DESK',
            ],
            [
                '',
                'DATE AMOUNT RECEIVED:',
                '',
                '',
                '',
                '',
                $missionExpense->created_at->format('d/m/Y'),
            ],
            [
                '',
                'AMOUNT RECEIVED',
                '',
                '',
                '',
                '',
                $missionExpense->amount_received,
            ],
            [],
            [
                'NO.',
                'DESCRIPTION',
                'UNIT COST',
                'QUANTITY',
                'AMOUNT',
                'CHARGES',
                'TOTAL',
                'NARRATION',
                'CONFIRMATION',
                'RECEIPT(S)',
            ],
            ...$missionExpense->expenses->map(function ($expense, $index) {
                return [
                    $index + 1,
                    $expense->expenseCategory->name,
                    $expense->unit_cost,
                    $expense->quantity,
                    $expense->line_total,
                    $expense->charge,
                    $expense->line_total + $expense->charge,
                    $expense->narration,
                    $expense->confirmation_message,
                    $expense->receipts->map(fn ($receipt) => Str::of($receipt->getTemporaryUrl(now()->addDays(3)))
                        ->replace('prfcorestorage.blob.core.windows.net', 'media.parkroadfellowship.org')
                        ->__toString())->join(', '),
                ];
            })->toArray(),
            [],
            [

                'TOTAL AMOUNT UTILIZED',
                '',
                '',
                '',
                '',
                '',
                $missionExpense->amount_spent,
            ],
            [
                'BALANCE',
                '',
                '',
                '',
                '',
                '',
                $missionExpense->balance,
            ],
            [],
            [
                'TOKEN AMOUNT',
                '',
                '',
                '',
                '',
                '',
                $missionExpense->token_amount,
            ],
            [
                'TOTAL AMOUNT TO REFUND (MINUS REFUND CHARGE)',
                '',
                '',
                '',
                '',
                '',
                $missionExpense->amount_to_refund,
            ],
            [
                'REFUND CHARGE',
                '',
                '',
                '',
                '',
                '',
                $missionExpense->refund_charge,
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        return [
            '1' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
            '2' => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
            'B4' => ['font' => ['bold' => true]],
            'B5' => ['font' => ['bold' => true]],
            'B6' => ['font' => ['bold' => true]],
            'B7' => ['font' => ['bold' => true]],
            '9' => ['font' => ['bold' => true]],
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        switch ($value) {
            case 'TOTAL AMOUNT UTILIZED':
            case 'BALANCE':
            case 'TOKEN AMOUNT':
            case 'TOTAL AMOUNT TO REFUND (MINUS REFUND CHARGE)':
            case 'REFUND CHARGE':

                $cell->getStyle()->getFont()->setBold(true);

                break;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
