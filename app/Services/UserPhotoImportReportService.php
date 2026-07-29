<?php

namespace App\Services;

use App\Exports\UserPhotoImportReportExport;
use App\Models\UserPhotoImportBatch;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserPhotoImportReportService
{
    /**
     * Download preview or final report as XLSX file.
     */
    public function downloadReport(UserPhotoImportBatch $batch): BinaryFileResponse
    {
        $filename = "photo_import_report_{$batch->uuid}.xlsx";

        return Excel::download(new UserPhotoImportReportExport($batch), $filename);
    }
}
