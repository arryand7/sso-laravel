<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class UserPhotoZipInspector
{
    protected const SYSTEM_FILES = [
        '.ds_store',
        'thumbs.db',
        'desktop.ini',
        '__macosx',
    ];

    /**
     * Inspect and extract a ZIP file into a private temporary storage directory safely.
     *
     * @return array{
     *     total_entries: int,
     *     extracted_files: array<int, array{
     *         original_filename: string,
     *         temporary_path: string,
     *         full_temporary_path: string,
     *         size: int,
     *         extension: string,
     *         mime: string|null,
     *         is_security_rejected: bool,
     *         security_reason: string|null
     *     }>,
     *     security_errors: array<int, string>
     * }
     */
    public function inspectAndExtract(UploadedFile $zipFile, string $batchUuid): array
    {
        $this->validateZipUpload($zipFile);

        $disk = Config::get('user-photo-import.storage_disk', 'local');
        $baseDir = Config::get('user-photo-import.storage_directory', 'private/user-photo-imports');
        $extractDirName = $baseDir.'/'.$batchUuid.'/extracted';

        $fullExtractPath = Storage::disk($disk)->path($extractDirName);
        if (! is_dir($fullExtractPath)) {
            mkdir($fullExtractPath, 0755, true);
        }

        $zip = new ZipArchive;
        $res = $zip->open($zipFile->getPathname());
        if ($res !== true) {
            throw new \InvalidArgumentException('Gagal membuka file ZIP. File mungkin rusak atau terenkripsi.');
        }

        $maxEntries = Config::get('user-photo-import.max_entries_count', 2000);
        $maxSingleSize = Config::get('user-photo-import.max_single_file_bytes', 10485760); // 10 MB
        $maxTotalExtracted = Config::get('user-photo-import.max_total_extracted_bytes', 2147483648); // 2 GB
        $maxDepth = Config::get('user-photo-import.max_directory_depth', 3);
        $maxRatio = Config::get('user-photo-import.max_compression_ratio', 100);

        $numFiles = $zip->numFiles;
        if ($numFiles > $maxEntries) {
            $zip->close();
            throw new \InvalidArgumentException("Jumlah entry dalam file ZIP ({$numFiles}) melebihi batas maksimal ({$maxEntries}).");
        }

        $extractedFiles = [];
        $securityErrors = [];
        $accumulatedExtractedBytes = 0;

        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (! $stat) {
                continue;
            }

            $entryName = $stat['name'];

            // Skip empty directories or trailing slashes
            if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                continue;
            }

            $normalizedName = str_replace('\\', '/', $entryName);
            $parts = explode('/', $normalizedName);
            $filename = end($parts);

            // Filter out system & hidden files
            if ($this->isSystemOrHiddenFile($normalizedName, $filename)) {
                continue;
            }

            // Guard Zip Slip & Directory Depth
            if ($this->hasPathTraversal($normalizedName)) {
                $securityErrors[] = "Zip Slip detected pada entry: {$entryName}";
                $extractedFiles[] = [
                    'original_filename' => $filename,
                    'temporary_path' => '',
                    'full_temporary_path' => '',
                    'size' => $stat['size'],
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'mime' => null,
                    'is_security_rejected' => true,
                    'security_reason' => 'SECURITY_REJECTED',
                ];

                continue;
            }

            if (count($parts) > $maxDepth + 1) {
                $securityErrors[] = "Kedalaman folder melebihi batas (maksimal {$maxDepth} level): {$entryName}";
                $extractedFiles[] = [
                    'original_filename' => $filename,
                    'temporary_path' => '',
                    'full_temporary_path' => '',
                    'size' => $stat['size'],
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'mime' => null,
                    'is_security_rejected' => true,
                    'security_reason' => 'SECURITY_REJECTED',
                ];

                continue;
            }

            // Check single file size & compression ratio
            $uncompressedSize = $stat['size'];
            $compressedSize = $stat['comp_size'];

            if ($uncompressedSize > $maxSingleSize) {
                $extractedFiles[] = [
                    'original_filename' => $filename,
                    'temporary_path' => '',
                    'full_temporary_path' => '',
                    'size' => $uncompressedSize,
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'mime' => null,
                    'is_security_rejected' => true,
                    'security_reason' => 'FILE_TOO_LARGE',
                ];

                continue;
            }

            $accumulatedExtractedBytes += $uncompressedSize;
            if ($accumulatedExtractedBytes > $maxTotalExtracted) {
                $zip->close();
                throw new \InvalidArgumentException('Total ukuran ekstraksi ZIP melebihi batas maksimal (2 GB).');
            }

            if ($compressedSize > 0 && ($uncompressedSize / $compressedSize) > $maxRatio) {
                $securityErrors[] = "Rasio kompresi mencurigakan (Zip Bomb potential): {$entryName}";

                continue;
            }

            // Extract entry to temporary directory
            $stream = $zip->getStream($entryName);
            if (! $stream) {
                $extractedFiles[] = [
                    'original_filename' => $filename,
                    'temporary_path' => '',
                    'full_temporary_path' => '',
                    'size' => $uncompressedSize,
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'mime' => null,
                    'is_security_rejected' => true,
                    'security_reason' => 'CORRUPTED_ZIP_ENTRY',
                ];

                continue;
            }

            $targetRelativePath = $extractDirName.'/'.Str::uuid().'_'.basename($filename);
            $targetFullPath = Storage::disk($disk)->path($targetRelativePath);

            // Canonical path validation
            $targetDirRealPath = realpath(dirname($targetFullPath)) ?: dirname($targetFullPath);
            $baseExtractRealPath = realpath($fullExtractPath) ?: $fullExtractPath;

            if (! str_starts_with($targetDirRealPath, $baseExtractRealPath)) {
                fclose($stream);
                $securityErrors[] = "Target extraction path keluar dari direktori aman: {$entryName}";

                continue;
            }

            $outStream = fopen($targetFullPath, 'wb');
            if ($outStream) {
                stream_copy_to_stream($stream, $outStream);
                fclose($outStream);
            }
            fclose($stream);

            $mime = @mime_content_type($targetFullPath) ?: null;

            $extractedFiles[] = [
                'original_filename' => $filename,
                'temporary_path' => $targetRelativePath,
                'full_temporary_path' => $targetFullPath,
                'size' => filesize($targetFullPath) ?: $uncompressedSize,
                'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                'mime' => $mime,
                'is_security_rejected' => false,
                'security_reason' => null,
            ];
        }

        $zip->close();

        return [
            'total_entries' => $numFiles,
            'extracted_files' => $extractedFiles,
            'security_errors' => $securityErrors,
        ];
    }

    /**
     * Validate ZIP upload file type and header.
     */
    protected function validateZipUpload(UploadedFile $file): void
    {
        $maxBytes = Config::get('user-photo-import.max_upload_size_bytes', 524288000);
        if ($file->getSize() > $maxBytes) {
            throw new \InvalidArgumentException('Ukuran file ZIP melebihi batas maksimal 500 MB.');
        }

        $mime = $file->getMimeType();
        $allowedMimes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'];
        if (! in_array($mime, $allowedMimes, true) && strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw new \InvalidArgumentException('File yang diupload harus berformat .zip');
        }

        // Validate ZIP magic header: PK\x03\x04
        $handle = fopen($file->getPathname(), 'rb');
        if (! $handle) {
            throw new \InvalidArgumentException('Gagal membaca file ZIP.');
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header !== "PK\x03\x04" && $header !== "PK\x05\x06") {
            throw new \InvalidArgumentException('Header file bukan merupakan ZIP archive yang valid.');
        }
    }

    /**
     * Check if a file is a system or hidden file (e.g. __MACOSX, .DS_Store, Thumbs.db).
     */
    protected function isSystemOrHiddenFile(string $normalizedPath, string $filename): bool
    {
        $lowerPath = strtolower($normalizedPath);
        $lowerFilename = strtolower($filename);

        if (str_contains($lowerPath, '__macosx/') || str_starts_with($lowerFilename, '.')) {
            return true;
        }

        foreach (self::SYSTEM_FILES as $systemFile) {
            if ($lowerFilename === $systemFile) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if path contains Zip Slip traversal attempts.
     */
    protected function hasPathTraversal(string $path): bool
    {
        return str_contains($path, '../') || str_contains($path, '..\\') || str_starts_with($path, '/') || str_starts_with($path, '\\');
    }
}
