<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserImportTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            'Users' => new UserImportTemplateUsersSheet,
            'Instructions' => new UserImportTemplateInstructionsSheet,
            'References' => new UserImportTemplateReferencesSheet,
        ];
    }
}
