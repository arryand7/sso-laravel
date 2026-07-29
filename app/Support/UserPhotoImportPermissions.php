<?php

namespace App\Support;

final class UserPhotoImportPermissions
{
    public const PATCH_ID = 'add-user-photo-import-permissions';

    public const GUARD = 'web';

    public const ROLE = 'superadmin';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'users.bulk-import-photos',
            'users.bulk-import-photos.preview',
            'users.bulk-import-photos.apply',
            'users.bulk-import-photos.download-report',
        ];
    }
}
