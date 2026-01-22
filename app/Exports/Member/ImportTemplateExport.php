<?php

namespace App\Exports\Member;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'John',           // first_name
                'Doe',            // last_name
                'Michael',        // other_names (optional)
                '+254712345678',  // phone_number
                'john.doe@example.com', // email_address (optional)
            ],
            [
                'Jane',
                'Smith',
                '',
                '+254723456789',
                'jane.smith@example.com',
            ],
            [
                'Peter',
                'Wanjiku',
                'Mwangi',
                '+254734567890',
                'peter.wanjiku@example.com',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'first_name',      // Required
            'last_name',       // Required
            'other_names',     // Optional
            'phone_number',    // Required - Format: +254XXXXXXXXX
            'email_address',   // Optional
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
            // Add some basic styling to data rows
            'A:E' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
        ];
    }
}
