<?php

namespace App\Exports\Requisition;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFPaymentMethod;
use App\Helpers\Utils;
use App\Models\Requisition;
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
    private Requisition $requisition;

    public function __construct(
        public int $requisitionId,
    ) {}

    public function title(): string
    {
        $requisition = $this->getRequisition();
        $requisitionDate = $requisition->requisition_date->format('Y-m-d');
        $eventName = Str::limit($requisition->accountingEvent->name, 20);

        return "Requisition - {$eventName} - {$requisitionDate}";
    }

    public function properties(): array
    {
        $requisition = $this->getRequisition();

        return [
            'creator' => 'Parkroad Fellowship',
            'lastModifiedBy' => 'Parkroad Fellowship Finance System',
            'title' => 'Requisition Report',
            'description' => "Requisition report for {$requisition->accountingEvent->name}",
            'subject' => 'Financial Requisition Report',
            'keywords' => 'requisition,finance,payment,report,parkroad,fellowship',
            'category' => 'Financial Reports',
            'company' => 'Parkroad Fellowship',
        ];
    }

    private function getRequisition(): Requisition
    {
        if (! isset($this->requisition)) {
            $this->requisition = Requisition::query()
                ->with([
                    'member',
                    'accountingEvent',
                    'appointedApprover',
                    'approvedBy',
                    'requisitionItems',
                    'requisitionItems.expenseCategory',
                    'paymentInstruction',
                ])
                ->findOrFail($this->requisitionId);
        }

        return $this->requisition;
    }

    public function query()
    {
        return Requisition::query()
            ->with([
                'member',
                'accountingEvent',
                'appointedApprover',
                'approvedBy',
                'requisitionItems',
                'requisitionItems.expenseCategory',
                'paymentInstruction',
            ])
            ->where('id', $this->requisitionId)
            ->limit(1);
    }

    public function map($requisition): array
    {
        $headerRows = [
            ['PARKROAD FELLOWSHIP', '', '', '', '', ''],
            ['FUNDS REQUISITION FORM', '', '', '', '', ''],
            ['', '', '', '', '', ''],
            ['REQUISITION DETAILS:', '', '', '', '', ''],
            ['Requisition ID:', '', '', '', '', $requisition->ulid],
            ['Event:', '', '', '', '', $requisition->accountingEvent->name ?? 'N/A'],
            ['Event Description:', '', '', '', '', $requisition->accountingEvent->description ?? 'N/A'],
            ['Requisition Date:', '', '', '', '', $requisition->requisition_date->format('d/m/Y')],
            ['Total Amount (KES):', '', '', '', '', $requisition->total_amount],
            ['', '', '', '', '', ''],
            ['REQUISITOR DETAILS:', '', '', '', '', ''],
            ['Requested By:', '', '', '', '', $requisition->member->full_name ?? 'N/A'],
            ['Member Email:', '', '', '', '', $requisition->member->email ?? 'N/A'],
            ['Member Phone:', '', '', '', '', Utils::formatPhoneNumber($requisition->member->phone_number)],
            ['', '', '', '', '', ''],
            ['APPROVAL DETAILS:', '', '', '', '', ''],
            ['Approval Status:', '', '', '', '', PRFApprovalStatus::fromValue($requisition->approval_status)->getLabel()],
            ['Appointed Approver:', '', '', '', '', $requisition->appointedApprover->full_name ?? 'N/A'],
            ['Approved By:', '', '', '', '', $requisition->approvedBy->full_name ?? 'N/A'],
            ['Approval Notes:', '', '', '', '', $requisition->approval_notes ?? 'N/A'],
            ['', '', '', '', '', ''],
        ];

        $itemsTableHeader = [
            [
                'NO.',
                'ITEM NAME',
                'CATEGORY',
                'UNIT PRICE (KES)',
                'QUANTITY',
                'TOTAL PRICE (KES)',
            ],
        ];

        $itemRows = $requisition->requisitionItems->map(function ($item, $index) {
            return [
                $index + 1,
                $item->item_name ?? 'N/A',
                $item->expenseCategory->name ?? 'N/A',
                $item->unit_price,
                $item->quantity,
                $item->total_price,
            ];
        })->toArray();

        $summaryRows = [
            ['', '', '', '', '', ''],
            ['ITEMS SUMMARY', '', '', '', '', ''],
            ['Total Items:', '', '', '', '', $requisition->requisitionItems->count()],
            ['Grand Total (KES):', '', '', '', '', $requisition->total_amount],
            ['', '', '', '', '', ''],
        ];

        $paymentRows = $this->getPaymentInstructionRows($requisition);

        $footerRows = [
            ['', '', '', '', '', ''],
            ['Generated on:', '', '', '', '', now()->format('d/m/Y H:i:s')],
        ];

        return array_merge($headerRows, $itemsTableHeader, $itemRows, $summaryRows, $paymentRows, $footerRows);
    }

    private function getPaymentInstructionRows($requisition): array
    {
        $paymentInstruction = $requisition->paymentInstruction;

        if (! $paymentInstruction) {
            return [
                ['PAYMENT INSTRUCTIONS', '', '', '', '', ''],
                ['No payment instructions available', '', '', '', '', ''],
            ];
        }

        $rows = [
            ['PAYMENT INSTRUCTIONS', '', '', '', '', ''],
            ['Payment Method:', '', '', '', '', PRFPaymentMethod::fromValue($paymentInstruction->payment_method)->getLabel()],
            ['Recipient Name:', '', '', '', '', $paymentInstruction->recipient_name ?? 'N/A'],
            ['Amount (KES):', '', '', '', '', $paymentInstruction->amount],
            ['Reference:', '', '', '', '', $paymentInstruction->reference ?? 'N/A'],
        ];

        // Add method-specific fields
        switch ($paymentInstruction->payment_method) {
            case 1: // M-Pesa
                $rows[] = ['M-Pesa Phone:', '', '', '', '', Utils::formatPhoneNumber($paymentInstruction->mpesa_phone_number)];
                break;
            case 2: // Bank Transfer
                $rows[] = ['Bank Name:', '', '', '', '', $paymentInstruction->bank_name ?? 'N/A'];
                $rows[] = ['Account Number:', '', '', '', '', $this->asText($paymentInstruction->bank_account_number)];
                $rows[] = ['Account Name:', '', '', '', '', $paymentInstruction->bank_account_name ?? 'N/A'];
                $rows[] = ['Branch:', '', '', '', '', $paymentInstruction->bank_branch ?? 'N/A'];
                if ($paymentInstruction->bank_swift_code) {
                    $rows[] = ['Swift Code:', '', '', '', '', $paymentInstruction->bank_swift_code];
                }
                break;
            case 3: // Paybill
                $rows[] = ['Paybill Number:', '', '', '', '', $this->asText($paymentInstruction->paybill_number)];
                $rows[] = ['Account Number:', '', '', '', '', $this->asText($paymentInstruction->paybill_account_number)];
                break;
            case 4: // Till Number
                $rows[] = ['Till Number:', '', '', '', '', $this->asText($paymentInstruction->till_number)];
                break;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $requisition = $this->getRequisition();
        $itemCount = $requisition->requisitionItems->count();
        $headerRowCount = 21; // Number of header rows before items table
        $itemsTableHeaderRow = $headerRowCount + 1;
        $itemsStartRow = $itemsTableHeaderRow + 1;
        $itemsEndRow = $itemsStartRow + $itemCount - 1;
        $summaryStartRow = $itemsEndRow + 2;

        // Merge cells for titles
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        // Merge label cells (A:E) in header section so labels display fully
        $labelRows = [5, 6, 7, 8, 9, 12, 13, 14, 17, 18, 19, 20];
        foreach ($labelRows as $row) {
            $sheet->mergeCells("A{$row}:E{$row}");
        }

        // Merge section header cells across full width
        foreach ([4, 11, 16] as $row) {
            $sheet->mergeCells("A{$row}:F{$row}");
        }

        // Merge summary section label rows (A:E for labels, A:F for section header)
        $sheet->mergeCells('A'.($summaryStartRow + 1).':F'.($summaryStartRow + 1)); // ITEMS SUMMARY
        $sheet->mergeCells('A'.($summaryStartRow + 2).':E'.($summaryStartRow + 2)); // Total Items
        $sheet->mergeCells('A'.($summaryStartRow + 3).':E'.($summaryStartRow + 3)); // Grand Total

        // Merge payment instruction section header
        $paymentStartRow = $summaryStartRow + 5;
        $sheet->mergeCells("A{$paymentStartRow}:F{$paymentStartRow}"); // PAYMENT INSTRUCTIONS

        // Merge payment instruction label rows (variable count based on payment method)
        $paymentInstruction = $requisition->paymentInstruction;
        $paymentLabelCount = $paymentInstruction ? match ($paymentInstruction->payment_method) {
            1 => 5, // M-Pesa: 4 common + 1 phone
            2 => 7 + ($paymentInstruction->bank_swift_code ? 1 : 0), // Bank: 4 common + 3-4 bank fields
            3 => 6, // Paybill: 4 common + 2
            4 => 5, // Till: 4 common + 1
            default => 4,
        } : 1;

        for ($i = 1; $i <= $paymentLabelCount; $i++) {
            $row = $paymentStartRow + $i;
            $sheet->mergeCells("A{$row}:E{$row}");
        }

        // Merge footer row
        $footerRow = $paymentStartRow + $paymentLabelCount + 2;
        $sheet->mergeCells("A{$footerRow}:E{$footerRow}"); // Generated on

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(28);

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
            // Section headers (REQUISITION DETAILS, REQUISITOR DETAILS, etc.)
            4 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '17154c']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E7F3']],
            ],
            11 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '17154c']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E7F3']],
            ],
            16 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '17154c']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E7F3']],
            ],
            // Field labels in column A (bold formatting)
            'A5:A20' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '2C2A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            // Data values in column F (left alignment with text wrapping)
            'F5:F20' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
            ],
            // Items table header
            $itemsTableHeaderRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17154c']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ],
            // Items rows with borders and alternating background
            "A{$itemsStartRow}:F{$itemsEndRow}" => [
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
            // Summary rows styling
            $summaryStartRow + 1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '17154c']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E7F3']],
            ],
            // Payment instruction header styling
            $summaryStartRow + 5 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '17154c']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E7F3']],
            ],
        ];
    }

    /**
     * Mark a value to be stored as explicit text in the spreadsheet.
     * Uses a \x01 byte prefix that bindValue() strips before writing.
     */
    private function asText(mixed $value): string
    {
        return "\x01".((string) ($value ?? 'N/A'));
    }

    public function bindValue(Cell $cell, $value)
    {
        // Handle explicit text markers from asText()
        if (is_string($value) && str_starts_with($value, "\x01")) {
            $cell->setValueExplicit(substr($value, 1), DataType::TYPE_STRING);

            return true;
        }

        // Apply special formatting to section headers
        $sectionHeaders = [
            'REQUISITION DETAILS:',
            'REQUISITOR DETAILS:',
            'APPROVAL DETAILS:',
            'ITEMS SUMMARY',
            'PAYMENT INSTRUCTIONS',
        ];

        if (in_array($value, $sectionHeaders)) {
            $cell->getStyle()->getFont()->setBold(true);
            $cell->getStyle()->getFont()->getColor()->setRGB('17154c');
            $cell->getStyle()->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E8E7F3');
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
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Unit Price
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Total Price

            // Numeric formatting for quantity
            'E' => NumberFormat::FORMAT_NUMBER,

            // Text formatting for text columns
            'A' => NumberFormat::FORMAT_TEXT, // Labels/Numbers
            'B' => NumberFormat::FORMAT_TEXT, // Item Name
            'C' => NumberFormat::FORMAT_TEXT, // Category
        ];
    }
}
