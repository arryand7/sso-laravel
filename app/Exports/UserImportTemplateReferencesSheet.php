<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserImportTemplateReferencesSheet implements FromArray, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'References';
    }

    public function array(): array
    {
        return [
            ['Type', 'Status'],
            ['student', 'active'],
            ['teacher', 'suspended'],
            ['parent', 'pending'],
            ['staff', ''],
            ['admin', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
