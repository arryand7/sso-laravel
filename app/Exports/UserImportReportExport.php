<?php

namespace App\Exports;

use App\Models\UserImportBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserImportReportExport implements FromArray, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(
        protected UserImportBatch $batch
    ) {}

    public function title(): string
    {
        return 'Error Report';
    }

    public function array(): array
    {
        $rows = [
            ['row_number', 'username', 'field', 'value', 'error_code', 'reason', 'suggested_fix'],
        ];

        $importRows = $this->batch->rows()
            ->where('status', 'invalid')
            ->orderBy('row_number')
            ->get();

        foreach ($importRows as $importRow) {
            $errors = $importRow->errors ?? [];
            $payload = $importRow->payload ?? [];

            foreach ($errors as $error) {
                $value = $this->sanitizeForExcel($error['value'] ?? '');
                $rows[] = [
                    $importRow->row_number,
                    $this->sanitizeForExcel($payload['username'] ?? '-'),
                    $error['field'] ?? '-',
                    $value,
                    $error['code'] ?? '-',
                    $error['reason'] ?? '-',
                    $error['suggested_fix'] ?? '-',
                ];
            }
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(35);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC2626'],
                ],
            ],
        ];
    }

    /**
     * Prevent formula injection by neutralizing dangerous prefixes.
     */
    protected function sanitizeForExcel(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = mb_substr($value, 0, 1);
        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
