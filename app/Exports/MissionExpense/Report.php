<?php

namespace App\Exports\MissionExpense;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionExpense;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Report extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithMapping, WithProperties, WithStyles, WithTitle
{
    private MissionExpense $missionExpense;

    public function __construct(
        public int $missionExpenseId,
    ) {}

    public function title(): string
    {
        $missionExpense = $this->getMissionExpense();
        $missionDate = $missionExpense->mission->start_date->format('Y-m-d');
        $schoolName = Str::limit($missionExpense->mission->school->name, 20);

        return "Mission Expense - {$schoolName} - {$missionDate}";
    }

    public function properties(): array
    {
        $missionExpense = $this->getMissionExpense();

        return [
            'creator' => 'Parkroad Fellowship',
            'lastModifiedBy' => 'Parkroad Fellowship Missions System',
            'title' => 'Mission Expense Report',
            'description' => "Expense report for mission to {$missionExpense->mission->school->name}",
            'subject' => 'Mission Financial Report',
            'keywords' => 'mission,expense,financial,report,parkroad,fellowship',
            'category' => 'Financial Reports',
            'company' => 'Parkroad Fellowship',
        ];
    }

    private function getMissionExpense(): MissionExpense
    {
        if (! isset($this->missionExpense)) {
            $this->missionExpense = MissionExpense::query()
                ->with([
                    'mission',
                    'mission.school',
                    'expenses',
                    'expenses.expenseCategory',
                    'expenses.receipts',
                    'mission.missionSubscriptions.member',
                ])
                ->findOrFail($this->missionExpenseId);
        }

        return $this->missionExpense;
    }

    public function query()
    {
        return MissionExpense::query()
            ->with([
                'mission',
                'mission.school',
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
        $leader = $missionExpense->mission
            ->missionSubscriptions
            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
            ->firstWhere('mission_role', PRFMissionRole::LEADER->value)
            ?->member ?? $missionExpense
            ->mission
            ->missionSubscriptions
            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value)
            ->first()?->member;

        $headerRows = [
            ['PARKROAD FELLOWSHIP'],
            ['FUNDS EXPENSE REPORT'],
            [],
            ['MISSION DETAILS:', '', '', '', '', ''],
            ['School:', '', '', '', '', $missionExpense->mission->school->name ?? 'N/A'],
            ['Mission Date:', '', '', '', '', $missionExpense->mission->start_date->format('d/m/Y')],
            ['Mission Theme:', '', '', '', '', $missionExpense->mission->theme ?? 'N/A'],
            [],
            ['FINANCIAL SUMMARY:', '', '', '', '', ''],
            ['Person Expensing:', '', '', '', '', $leader?->full_name ?? 'N/A'],
            ['Desk:', '', '', '', '', 'MISSIONS DESK'],
            ['Date Amount Received:', '', '', '', '', $missionExpense->created_at->format('d/m/Y')],
            ['Amount Received (KES):', '', '', '', '', ($missionExpense->amount_received)],
            [],
        ];

        $expenseTableHeader = [
            [
                'NO.',
                'DESCRIPTION',
                'UNIT COST (KES)',
                'QUANTITY',
                'AMOUNT (KES)',
                'CHARGES (KES)',
                'TOTAL (KES)',
                'NARRATION',
                'CONFIRMATION',
                'RECEIPT(S)',
            ],
        ];

        $expenseRows = $missionExpense->expenses->map(function ($expense, $index) {
            return [
                $index + 1,
                $expense->expenseCategory->name ?? 'N/A',
                ($expense->unit_cost),
                $expense->quantity,
                ($expense->line_total),
                ($expense->charge),
                (($expense->line_total + $expense->charge)),
                $expense->narration ?? '',
                $expense->confirmation_message ?? '',
                $expense->receipts->map(fn ($receipt) => Str::of($receipt->getTemporaryUrl(now()->addDays(3)))
                    ->replace('prfcorestorage.blob.core.windows.net', 'media.parkroadfellowship.org')
                    ->__toString()
                )->join(', '),
            ];
        })->toArray();

        $summaryRows = [
            [],
            ['FINANCIAL SUMMARY', '', '', '', '', '', '', '', '', ''],
            ['Total Amount Utilized (KES)', '', '', '', '', '', ($missionExpense->amount_spent), '', '', ''],
            ['Balance (KES)', '', '', '', '', '', ($missionExpense->balance), '', '', ''],
            [],
            ['REFUND DETAILS', '', '', '', '', '', '', '', '', ''],
            ['Token Amount (KES)', '', '', '', '', '', ($missionExpense->token_amount), '', '', ''],
            ['Total Amount to Refund (KES)', '', '', '', '', '', ($missionExpense->amount_to_refund), '', '', ''],
            ['Refund Charge (KES)', '', '', '', '', '', ($missionExpense->refund_charge), '', '', ''],
            [],
            ['Generated on:', '', '', '', '', '', now()->format('d/m/Y H:i:s'), '', '', ''],
            ['Report ID:', '', '', '', '', '', $missionExpense->ulid, '', '', ''],
        ];

        return array_merge($headerRows, $expenseTableHeader, $expenseRows, $summaryRows);
    }

    public function styles(Worksheet $sheet): array
    {
        $missionExpense = $this->getMissionExpense();
        $expenseCount = $missionExpense->expenses->count();
        $headerRowCount = 14; // Number of header rows before expense table
        $expenseTableHeaderRow = $headerRowCount + 1;
        $expenseStartRow = $expenseTableHeaderRow + 1;
        $expenseEndRow = $expenseStartRow + $expenseCount - 1;
        $summaryStartRow = $expenseEndRow + 2;

        // Merge cells for headers
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(30);

        return [
            // Main title
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '17154c']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            ],
            // Subtitle
            2 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '17154c']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            ],
            // Section headers (Mission Details, Financial Summary, etc.)
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            9 => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            // Field labels
            'B5:B12' => ['font' => ['bold' => true]],
            // Expense table header
            $expenseTableHeaderRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17154c']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ],
            // Expense rows
            "A{$expenseStartRow}:J{$expenseEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ],
            // Summary section headers
            $summaryStartRow + 1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '17154c']],
            ],
            $summaryStartRow + 5 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '17154c']],
            ],
            // Summary values styling
            "A{$summaryStartRow}:J".($summaryStartRow + 10) => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Apply special formatting to summary labels
        $summaryLabels = [
            'FINANCIAL SUMMARY',
            'REFUND DETAILS',
            'Total Amount Utilized (KES)',
            'Balance (KES)',
            'Token Amount (KES)',
            'Total Amount to Refund (KES)',
            'Refund Charge (KES)',
        ];

        if (in_array($value, $summaryLabels)) {
            $cell->getStyle()->getFont()->setBold(true);

            if (in_array($value, ['FINANCIAL SUMMARY', 'REFUND DETAILS'])) {
                $cell->getStyle()->getFont()->getColor()->setRGB('17154c');
                $cell->getStyle()->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8E7F3');
            }
        }

        // Handle currency formatting - ensure numbers remain as numeric data type
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            // Number formatting with thousands separator for currency columns
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Unit Cost
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Amount
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Charges
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total

            // Numeric formatting for quantity
            'D' => NumberFormat::FORMAT_NUMBER,

            // Text formatting for longer text columns
            'H' => NumberFormat::FORMAT_TEXT, // Narration
            'I' => NumberFormat::FORMAT_TEXT, // Confirmation
            'J' => NumberFormat::FORMAT_TEXT, // Receipts
        ];
    }
}
