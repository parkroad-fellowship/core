<?php

namespace App\Exports\Finance\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The look of the treasurer's workbooks: a bold merged title, green header rows, KES number
 * formats and bordered tables — so exports read like the sheets the treasurer already keeps.
 */
trait StylesTreasurerSheets
{
    protected const MONEY_FORMAT = '#,##0;[Red]-#,##0';

    protected const HEADER_FILL = 'FF00B050';

    protected const TOTAL_FILL = 'FFE2EFDA';

    protected function heading(Worksheet $sheet, string $range, string $text, int $size = 13): void
    {
        [$first] = explode(':', $range);
        $sheet->setCellValue($first, $text);
        $sheet->mergeCells($range);
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize($size);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    protected function header(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER_FILL);
        $style
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
    }

    protected function totals(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
        $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
    }

    protected function money(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode(self::MONEY_FORMAT);
    }

    protected function borders(Worksheet $sheet, string $range): void
    {
        $sheet
            ->getStyle($range)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB('FFBFBFBF');
    }

    /**
     * @param  array<string, float|int>  $widths  column letter => width
     */
    protected function widths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * Excel limits sheet titles to 31 characters and a few symbols.
     */
    protected static function sheetTitle(string $title): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $title), 0, 31);
    }
}
