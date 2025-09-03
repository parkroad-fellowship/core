<?php

namespace App\Exports\AccountingEvent;

use App\Enums\PRFEntryType;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
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

class Export extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithMapping, WithProperties, WithStyles, WithTitle
{
    private AccountingEvent $accountingEvent;

    public function __construct(
        public int $accountingEventId,
    ) {
        //
    }

    public function title(): string
    {
        $accountingEvent = $this->getAccountingEvent();
        $eventName = Str::limit($accountingEvent->name, 30);
        $date = $accountingEvent->created_at->format('Y-m-d');

        return "Accounting Event - {$eventName} - {$date}";
    }

    public function properties(): array
    {
        $accountingEvent = $this->getAccountingEvent();

        return [
            'creator' => 'Parkroad Fellowship',
            'lastModifiedBy' => 'Parkroad Fellowship Accounting System',
            'title' => 'Accounting Event Report',
            'description' => "Financial report for accounting event: {$accountingEvent->name}",
            'subject' => 'Accounting Event Financial Report',
            'keywords' => 'accounting,event,financial,report,parkroad,fellowship',
            'category' => 'Financial Reports',
            'company' => 'Parkroad Fellowship',
        ];
    }

    private function getAccountingEvent(): AccountingEvent
    {
        if (! isset($this->accountingEvent)) {
            $this->accountingEvent = AccountingEvent::query()
                ->with([
                    'requisitions.member',
                    'requisitions.requisitionItems.expenseCategory',
                    'allocationEntries.member',
                    'allocationEntries.expenseCategory',
                    'accountingEventable',
                ])
                ->findOrFail($this->accountingEventId);
        }

        return $this->accountingEvent;
    }

    public function query()
    {
        return AccountingEvent::query()
            ->with([
                'requisitions.member',
                'requisitions.requisitionItems.expenseCategory',
                'allocationEntries.member',
                'allocationEntries.expenseCategory',
                'accountingEventable',
            ])
            ->where('id', $this->accountingEventId)
            ->limit(1);
    }

    public function map($accountingEvent): array
    {
        $headerRows = [
            ['PARKROAD FELLOWSHIP'],
            ['ACCOUNTING EVENT REPORT'],
            [],
            ['EVENT DETAILS:', ''],
            ['Event Name:', $accountingEvent->name ?? 'N/A'],
            ['Description:', $accountingEvent->description ?? 'N/A'],
            ['Due Date:', $accountingEvent->due_date?->format('d/m/Y') ?? 'N/A'],
            ['Status:', $accountingEvent->status ?? 'N/A'],
            ['Responsible Desk:', $accountingEvent->responsible_desk ?? 'N/A'],
            ['Event Balance:', $accountingEvent->balance], // Keep as numeric
            [],
        ];

        // Credits Summary Section
        $credits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::CREDIT->value);
        $totalCredits = $credits->sum('amount');

        $creditsRows = [
            ['CREDITS SUMMARY:', '', '', '', '', ''],
            ['Total Credits (KES):', $totalCredits],
        ];

        foreach ($credits as $credit) {
            $creditsRows[] = [
                '',
                'Credit Entry',
                $credit->member?->full_name ?? 'N/A',
                $credit->amount,
                $credit->narration ?? '',
                $credit->created_at->format('d/m/Y'),
                '',
                '',
                '',
            ];
        }

        $creditsRows[] = [];

        // Debits/Expenses Table Header
        $debitsTableHeader = [
            [
                'NO.',
                'CATEGORY',
                'UNIT COST (KES)',
                'QUANTITY',
                'CHARGE (KES)',
                'AMOUNT (KES)',
                'NARRATION',
                'DATE',
                'CONFIRMATION',
                'MADE BY',
                'CHARGE TYPE',
                'RECEIPTS',
            ],
        ];

        // Debits/Expenses Rows
        $debits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::DEBIT->value);
        $debitsRows = $debits->map(function ($debit, $index) {
            $receipts = $debit->receipts->map(
                fn ($receipt) => Utils::convertAzureURLToMediaURL($receipt->getTemporaryUrl(now()->addYears(7)))
            )->join(', ');

            return [
                $index + 1,
                $debit->expenseCategory?->name ?? 'N/A',
                $debit->unit_cost ?? 0,
                $debit->quantity ?? 0,
                $debit->charge ?? 0,
                $debit->amount,
                $debit->narration ?? '',
                $debit->created_at->format('d/m/Y'),
                $debit->confirmation_message ?? 'N/A',
                $debit->member?->full_name ?? 'N/A',
                PRFTransactionType::fromValue($debit->charge_type)->getLabel() ?? 'N/A',
                $receipts,
            ];
        })->toArray();

        // Requisitions Summary Section
        $requisitionsRows = [
            [],
            ['REQUISITIONS SUMMARY:', '', '', '', '', '', '', '', '', '', '', ''],
            [
                'NO.',
                'REQUISITION ID',
                'MEMBER',
                'APPROVAL STATUS',
                'TOTAL AMOUNT (KES)',
                'APPROVED BY',
                'DATE',
                'REMARKS',
                '',
                '',
                '',
                '',
            ],
        ];

        foreach ($accountingEvent->requisitions as $index => $requisition) {
            $requisitionsRows[] = [
                $index + 1,
                $requisition->ulid,
                $requisition->member?->full_name ?? 'N/A',
                $requisition->approval_status ?? 'Pending',
                $requisition->total_amount,
                $requisition->approvedBy?->full_name ?? 'N/A',
                $requisition->requisition_date->format('d/m/Y'),
                $requisition->remarks ?? '',
                '',
                '',
                '',
                '',
            ];
        }

        // Financial Summary
        $totalDebits = $debits->sum('amount');
        $summaryRows = [
            [],
            ['FINANCIAL SUMMARY',  '', '', '', '', '', '', '', ''],
            ['Total Credits (KES)', '', '', $totalCredits], // Keep as numeric
            ['Total Debits (KES)', '', '', $totalDebits], // Keep as numeric
            ['Balance (KES)', '', '', ($totalCredits - $totalDebits)], // Keep as numeric
            [],
            ['Report Generated on:', '', '',  now()->format('d/m/Y H:i:s')],
            ['Event ID:', '', '', $accountingEvent->ulid],
        ];

        return array_merge(
            $headerRows,
            $creditsRows,
            $debitsTableHeader,
            $debitsRows,
            $requisitionsRows,
            $summaryRows
        );
    }

    public function styles(Worksheet $sheet): array
    {
        $accountingEvent = $this->getAccountingEvent();
        $credits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::CREDIT->value);
        $debits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::DEBIT->value);

        $headerRowCount = 11; // Number of header rows (1-11)
        $creditsHeaderRow = $headerRowCount + 1; // Row 12: "CREDITS SUMMARY:"
        $creditsCount = $credits->count();
        $creditsEndRow = $creditsHeaderRow + 2 + $creditsCount; // 12 + 2 + credits count

        // Debits table header comes immediately after credits section (no extra spacing)
        $debitsTableHeaderRow = $creditsEndRow + 1;
        $debitsStartRow = $debitsTableHeaderRow + 1;
        $debitsEndRow = $debitsStartRow + $debits->count() - 1;

        // Requisitions section starts after debits + spacing
        $requisitionsHeaderRow = $debitsEndRow + 1; // Empty row
        $requisitionsTableHeaderRow = $requisitionsHeaderRow + 2; // "REQUISITIONS SUMMARY:" + table header
        $requisitionsStartRow = $requisitionsTableHeaderRow + 1;
        $requisitionsCount = $accountingEvent->requisitions->count();
        $requisitionsEndRow = $requisitionsStartRow + $requisitionsCount - 1;

        // Merge cells for headers
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);   // NO.
        $sheet->getColumnDimension('B')->setWidth(20);  // CATEGORY
        $sheet->getColumnDimension('C')->setWidth(20);  // MEMBER
        $sheet->getColumnDimension('D')->setWidth(15);  // CHARGE TYPE
        $sheet->getColumnDimension('E')->setWidth(12);  // UNIT COST
        $sheet->getColumnDimension('F')->setWidth(10);  // QUANTITY
        $sheet->getColumnDimension('G')->setWidth(12);  // CHARGE
        $sheet->getColumnDimension('H')->setWidth(15);  // AMOUNT
        $sheet->getColumnDimension('I')->setWidth(30);  // NARRATION
        $sheet->getColumnDimension('J')->setWidth(12);  // DATE
        $sheet->getColumnDimension('K')->setWidth(20);  // CONFIRMATION
        $sheet->getColumnDimension('L')->setWidth(25);  // RECEIPTS

        return [
            // Main title
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Subtitle
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Section headers
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            $creditsHeaderRow => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            ($requisitionsHeaderRow + 1) => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],

            // Table headers - corrected positioning
            $debitsTableHeaderRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17154c']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
            $requisitionsTableHeaderRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17154c']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],

            // Data rows with borders
            "A{$debitsStartRow}:L{$debitsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ],

            // Requisitions table rows with borders
            "A{$requisitionsStartRow}:L{$requisitionsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ],

            // Summary section styling
            ($requisitionsEndRow + 2) => [
                'font' => ['bold' => true, 'color' => ['rgb' => '17154c']],
            ],
            'A'.($requisitionsEndRow + 3).':I'.($requisitionsEndRow + 6) => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Apply special formatting to summary labels
        $summaryLabels = [
            'FINANCIAL SUMMARY',
            'CREDITS SUMMARY:',
            'REQUISITIONS SUMMARY:',
            'Total Credits (KES)',
            'Total Debits (KES)',
            'Balance (KES)',
        ];

        if (in_array($value, $summaryLabels)) {
            $cell->getStyle()->getFont()->setBold(true);

            if (in_array($value, ['FINANCIAL SUMMARY', 'CREDITS SUMMARY:', 'REQUISITIONS SUMMARY:'])) {
                $cell->getStyle()->getFont()->getColor()->setRGB('17154c');
            }
        }

        // Handle currency formatting - ensure numbers remain as numeric data type
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            // Text formatting for charge type
            'D' => NumberFormat::FORMAT_TEXT, // Charge Type

            // Number formatting with thousands separator for currency columns
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Unit Cost
            'F' => NumberFormat::FORMAT_NUMBER, // Quantity
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Charge
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Amount

            // Text formatting for longer text columns
            'I' => NumberFormat::FORMAT_TEXT, // Narration
            'J' => NumberFormat::FORMAT_TEXT, // Date (will be handled as text)
            'K' => NumberFormat::FORMAT_TEXT, // Confirmation
            'L' => NumberFormat::FORMAT_TEXT, // Receipts/Remarks
        ];
    }
}
