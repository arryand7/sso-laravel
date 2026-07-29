<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Photo Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings and safety limits for bulk photo zip imports.
    |
    */

    // Maximum allowed zip upload file size in bytes (default 500 MB)
    'max_upload_size_bytes' => env('USER_PHOTO_IMPORT_MAX_UPLOAD_BYTES', 524288000),

    // Maximum number of entries inside a zip file
    'max_entries_count' => env('USER_PHOTO_IMPORT_MAX_ENTRIES', 2000),

    // Maximum size of a single photo input file before processing in bytes (default 10 MB)
    'max_single_file_bytes' => env('USER_PHOTO_IMPORT_MAX_SINGLE_FILE_BYTES', 10485760),

    // Maximum total uncompressed size for extracted zip content in bytes (default 2 GB)
    'max_total_extracted_bytes' => env('USER_PHOTO_IMPORT_MAX_EXTRACTED_BYTES', 2147483648),

    // Maximum allowed directory depth inside the ZIP file
    'max_directory_depth' => env('USER_PHOTO_IMPORT_MAX_DEPTH', 3),

    // Maximum allowed compression ratio per file (zip bomb guard)
    'max_compression_ratio' => env('USER_PHOTO_IMPORT_MAX_RATIO', 100),

    // Private storage disk for import staging
    'storage_disk' => 'local',

    // Base storage directory path for extracted import batches
    'storage_directory' => 'private/user-photo-imports',

    // Batch chunk processing size
    'chunk_size' => 50,

    // Retention duration in hours before uncommitted or temporary batches expire
    'cleanup_expiration_hours' => 24,
];
