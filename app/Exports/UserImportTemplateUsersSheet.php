<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserImportTemplateUsersSheet implements FromArray, WithColumnFormatting, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Users';
    }

    public function array(): array
    {
        return [
            ['username', 'name', 'email', 'type', 'nis', 'nip', 'status', 'qr_code', 'password', 'user_id'],
            // Example row (commented values for guidance)
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // username
            'E' => NumberFormat::FORMAT_TEXT, // nis
            'F' => NumberFormat::FORMAT_TEXT, // nip
            'H' => NumberFormat::FORMAT_TEXT, // qr_code
            'I' => NumberFormat::FORMAT_TEXT, // password
            'J' => NumberFormat::FORMAT_TEXT, // user_id
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Add data validation for type column (D)
        $typeValidation = $sheet->getCell('D2')->getDataValidation();
        $typeValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $typeValidation->setFormula1('References!$A$2:$A$6');
        $typeValidation->setAllowBlank(false);
        $typeValidation->setShowDropDown(true);
        $typeValidation->setShowErrorMessage(true);
        $typeValidation->setErrorTitle('Tipe Invalid');
        $typeValidation->setError('Pilih tipe yang valid dari daftar.');
        // Copy validation to rows 3-1001
        for ($row = 3; $row <= 1001; $row++) {
            $sheet->getCell("D{$row}")->setDataValidation(clone $typeValidation);
        }

        // Add data validation for status column (G)
        $statusValidation = $sheet->getCell('G2')->getDataValidation();
        $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $statusValidation->setFormula1('References!$B$2:$B$4');
        $statusValidation->setAllowBlank(false);
        $statusValidation->setShowDropDown(true);
        $statusValidation->setShowErrorMessage(true);
        $statusValidation->setErrorTitle('Status Invalid');
        $statusValidation->setError('Pilih status yang valid dari daftar.');
        for ($row = 3; $row <= 1001; $row++) {
            $sheet->getCell("G{$row}")->setDataValidation(clone $statusValidation);
        }

        // Write template version in cell K1
        $sheet->setCellValue('K1', 'SABIRA_USER_IMPORT_V1');
        $sheet->getStyle('K1')->getFont()->setColor(
            new \PhpOffice\PhpSpreadsheet\Style\Color('FF999999')
        )->setSize(8);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2463EB'],
                ],
            ],
        ];
    }
}
