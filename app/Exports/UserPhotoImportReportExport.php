<?php

namespace App\Exports;

use App\Models\UserPhotoImportBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserPhotoImportReportExport implements FromArray, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(
        protected UserPhotoImportBatch $batch
    ) {}

    public function title(): string
    {
        return 'Photo Import Report';
    }

    public function array(): array
    {
        $headers = [
            'original_filename',
            'identifier_type',
            'identifier',
            'user_id',
            'user_name',
            'user_type',
            'existing_photo',
            'status',
            'planned_action',
            'error_code',
            'reason',
            'old_photo_path',
            'new_photo_path',
            'input_size',
            'output_size',
            'processed_at',
        ];

        $rows = [$headers];

        $items = $this->batch->items()->with('user')->get();

        foreach ($items as $item) {
            $user = $item->user;
            $rows[] = [
                $this->sanitizeForExcel($item->original_filename),
                strtoupper($item->identifier_type),
                $this->sanitizeForExcel((string) $item->identifier),
                $user ? $user->id : '-',
                $user ? $this->sanitizeForExcel($user->name) : '-',
                $user ? strtoupper($user->type) : '-',
                $user && $user->photo_path ? 'Yes' : 'No',
                $item->status,
                $item->planned_action,
                $item->error_code ?? '-',
                $this->sanitizeForExcel($item->error_message ?? '-'),
                $this->sanitizeForExcel($item->old_photo_path ?? '-'),
                $this->sanitizeForExcel($item->new_photo_path ?? '-'),
                $item->input_size ? number_format($item->input_size).' B' : '-',
                $item->output_size ? number_format($item->output_size).' B' : '-',
                $item->processed_at ? $item->processed_at->toDateTimeString() : '-',
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // identifier (preserve leading zeros)
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(20);
        $sheet->getColumnDimension('K')->setWidth(40);
        $sheet->getColumnDimension('L')->setWidth(30);
        $sheet->getColumnDimension('M')->setWidth(30);
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(20);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'], // slate-800
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
