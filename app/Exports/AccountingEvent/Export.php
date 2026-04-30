<?php

namespace App\Exports\AccountingEvent;

use App\Enums\PRFAccountEventStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFResponsibleDesk;
use App\Enums\PRFTransactionType;
use App\Helpers\Utils;
use App\Models\AccountingEvent;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Export extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithCustomValueBinder, WithMapping, WithProperties, WithStyles, WithTitle
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
                    'refunds',
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
                'refunds',
            ])
            ->where('id', $this->accountingEventId)
            ->limit(1);
    }

    public function map($accountingEvent): array
    {
        $headerRows = [
            ['PARKROAD FELLOWSHIP'],
            ['ACCOUNTING REPORT'],
            [],
            ['EVENT DETAILS:', ''],
            ['Event Name:', $accountingEvent->name ?? 'N/A'],
            ['Description:', $accountingEvent->description ?? 'N/A'],
            ['Due Date:', $accountingEvent->due_date?->format('d/m/Y') ?? 'N/A'],
            ['Status:', PRFAccountEventStatus::fromValue($accountingEvent->status)->getLabel() ?? 'N/A'],
            ['Responsible Desk:', PRFResponsibleDesk::fromValue($accountingEvent->responsible_desk)->getLabel() ?? 'N/A'],
            ['Balance:', $accountingEvent->balance],
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

        // Refunds Summary Section
        $refundsRows = [
            [],
            ['REFUNDS SUMMARY:', '', '', '', '', '', '', '', '', '', '', ''],
            [
                'NO.',
                'REFUND ID',
                'AMOUNT (KES)',
                'CHARGE (KES)',
                'DEFICIT (KES)',
                'DATE',
                'CONFIRMATION',
                '',
                '',
                '',
                '',
                '',
            ],
        ];

        foreach ($accountingEvent->refunds as $index => $refund) {
            $refundsRows[] = [
                $index + 1,
                $refund->ulid,
                $refund->amount,
                $refund->charge,
                $refund->deficit_amount,
                $refund->created_at->format('d/m/Y'),
                $refund->confirmation_message ?? 'N/A',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        $totalRefunds = $accountingEvent->refunds->sum('amount');
        $totalRefundCharges = $accountingEvent->refunds->sum('charge');
        $latestDeficit = $accountingEvent->refunds->sortByDesc('created_at')->first()?->deficit_amount ?? $accountingEvent->amount_to_refund;

        $refundsRows[] = [];
        $refundsRows[] = ['Total Refunded (KES):', '', $totalRefunds];
        $refundsRows[] = ['Total Charges (KES):', '', $totalRefundCharges];
        $refundsRows[] = ['Current Deficit (KES):', '', $latestDeficit];

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
            $refundsRows,
            $requisitionsRows,
            $summaryRows
        );
    }

    public function styles(Worksheet $sheet): array
    {
        $accountingEvent = $this->getAccountingEvent();
        $credits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::CREDIT->value);
        $debits = $accountingEvent->allocationEntries->where('entry_type', PRFEntryType::DEBIT->value);
        $refunds = $accountingEvent->refunds;

        $headerRowCount = 11; // Number of header rows (1-11)
        $creditsHeaderRow = $headerRowCount + 1; // Row 12: "CREDITS SUMMARY:"
        $creditsCount = $credits->count();
        $creditsEndRow = $creditsHeaderRow + 2 + $creditsCount; // 12 + 2 + credits count

        // Debits table header comes immediately after credits section (no extra spacing)
        $debitsTableHeaderRow = $creditsEndRow + 1;
        $debitsStartRow = $debitsTableHeaderRow + 1;
        $debitsEndRow = $debitsStartRow + $debits->count() - 1;

        // Refunds section starts after debits + spacing
        $refundsHeaderRow = $debitsEndRow + 1; // Empty row
        $refundsTableHeaderRow = $refundsHeaderRow + 2; // "REFUNDS SUMMARY:" + table header
        $refundsStartRow = $refundsTableHeaderRow + 1;
        $refundsCount = $refunds->count();
        $refundsEndRow = $refundsStartRow + $refundsCount - 1;
        $refundsSummaryStart = $refundsEndRow + 2; // After empty row

        // Requisitions section starts after refunds summary + spacing
        $requisitionsHeaderRow = $refundsSummaryStart + 3; // After refund summary (3 rows) + empty row
        $requisitionsTableHeaderRow = $requisitionsHeaderRow + 2; // "REQUISITIONS SUMMARY:" + table header
        $requisitionsStartRow = $requisitionsTableHeaderRow + 1;
        $requisitionsCount = $accountingEvent->requisitions->count();
        $requisitionsEndRow = $requisitionsStartRow + $requisitionsCount - 1;

        // Merge cells for headers
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        // Set column widths (must accommodate both header labels and table data)
        $sheet->getColumnDimension('A')->setWidth(20);  // Header labels / NO.
        $sheet->getColumnDimension('B')->setWidth(28);  // Header values / CATEGORY / ULID (26 chars)
        $sheet->getColumnDimension('C')->setWidth(18);  // UNIT COST (KES) / MEMBER
        $sheet->getColumnDimension('D')->setWidth(12);  // QUANTITY / APPROVAL STATUS
        $sheet->getColumnDimension('E')->setWidth(14);  // CHARGE (KES) / TOTAL AMOUNT
        $sheet->getColumnDimension('F')->setWidth(15);  // AMOUNT (KES) / APPROVED BY
        $sheet->getColumnDimension('G')->setWidth(22);  // NARRATION / DATE
        $sheet->getColumnDimension('H')->setWidth(12);  // DATE / REMARKS
        $sheet->getColumnDimension('I')->setWidth(30);  // CONFIRMATION
        $sheet->getColumnDimension('J')->setWidth(15);  // MADE BY
        $sheet->getColumnDimension('K')->setWidth(18);  // CHARGE TYPE
        $sheet->getColumnDimension('L')->setWidth(40);  // RECEIPTS

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
            // Header section labels and values
            'A4:A11' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '17154c']],
            ],
            'B5:B10' => [
                'alignment' => ['wrapText' => true],
            ],
            // Section headers
            4 => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            $creditsHeaderRow => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            ($refundsHeaderRow + 1) => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],
            ($requisitionsHeaderRow + 1) => ['font' => ['bold' => true, 'color' => ['rgb' => '17154c']]],

            // Table headers - corrected positioning
            $debitsTableHeaderRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17154c']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
            ],
            $refundsTableHeaderRow => [
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

            // Data rows with borders and text wrapping for long-content columns
            "A{$debitsStartRow}:L{$debitsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ],

            // Refunds table rows with borders
            "A{$refundsStartRow}:L{$refundsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ],

            // Requisitions table rows with borders
            "A{$requisitionsStartRow}:L{$requisitionsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
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
        // Handle explicit text markers from asText()
        if (is_string($value) && str_starts_with($value, "\x01")) {
            $cell->setValueExplicit(substr($value, 1), DataType::TYPE_STRING);

            return true;
        }

        // Apply special formatting to summary labels
        $summaryLabels = [
            'FINANCIAL SUMMARY',
            'CREDITS SUMMARY:',
            'REFUNDS SUMMARY:',
            'REQUISITIONS SUMMARY:',
            'Total Credits (KES)',
            'Total Debits (KES)',
            'Balance (KES)',
            'Total Refunded (KES):',
            'Total Charges (KES):',
            'Current Deficit (KES):',
        ];

        if (in_array($value, $summaryLabels)) {
            $cell->getStyle()->getFont()->setBold(true);

            if (in_array($value, ['FINANCIAL SUMMARY', 'CREDITS SUMMARY:', 'REFUNDS SUMMARY:', 'REQUISITIONS SUMMARY:'])) {
                $cell->getStyle()->getFont()->getColor()->setRGB('17154c');
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
