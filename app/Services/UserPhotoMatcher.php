<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class UserPhotoMatcher
{
    protected const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    protected const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Process extracted file list and determine preview status for each item.
     *
     * @param  array<int, array{
     *     original_filename: string,
     *     temporary_path: string,
     *     full_temporary_path: string,
     *     size: int,
     *     extension: string,
     *     mime: string|null,
     *     is_security_rejected: bool,
     *     security_reason: string|null
     * }>  $extractedFiles
     * @param  string  $matchingType  'nis' or 'nip'
     * @param  string  $existingPolicy  'skip' or 'replace'
     * @return array<int, array{
     *     original_filename: string,
     *     temporary_path: string,
     *     detected_extension: string,
     *     detected_mime: string|null,
     *     identifier_type: string,
     *     identifier: string,
     *     user_id: int|null,
     *     status: string,
     *     planned_action: string,
     *     error_code: string|null,
     *     error_message: string|null,
     *     old_photo_path: string|null,
     *     input_size: int
     * }>
     */
    public function matchFiles(array $extractedFiles, string $matchingType, string $existingPolicy): array
    {
        // 1. Extract base identifiers
        $parsedItems = [];
        $identifierCounts = [];

        foreach ($extractedFiles as $file) {
            $filename = $file['original_filename'];
            $extension = strtolower($file['extension']);

            if ($file['is_security_rejected']) {
                $parsedItems[] = [
                    'file' => $file,
                    'identifier' => '',
                    'status' => 'SECURITY_REJECTED',
                    'error_code' => $file['security_reason'] ?? 'SECURITY_REJECTED',
                    'error_message' => 'File ditolak karena kendala keamanan.',
                ];

                continue;
            }

            // Extract base filename without extension
            $baseIdentifier = $this->extractBaseIdentifier($filename);

            if (! $this->isValidFilenameIdentifier($filename, $baseIdentifier)) {
                $parsedItems[] = [
                    'file' => $file,
                    'identifier' => $baseIdentifier,
                    'status' => 'INVALID_FILENAME',
                    'error_code' => 'INVALID_FILENAME',
                    'error_message' => 'Nama file tidak sesuai format identifier exact (misal: 22001001.jpg).',
                ];

                continue;
            }

            if (! in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
                $parsedItems[] = [
                    'file' => $file,
                    'identifier' => $baseIdentifier,
                    'status' => 'UNSUPPORTED_FORMAT',
                    'error_code' => 'UNSUPPORTED_FORMAT',
                    'error_message' => 'Format file gambar tidak didukung.',
                ];

                continue;
            }

            // Verify image integrity & dimensions
            $imageInfo = @getimagesize($file['full_temporary_path']);
            if ($imageInfo === false) {
                $parsedItems[] = [
                    'file' => $file,
                    'identifier' => $baseIdentifier,
                    'status' => 'CORRUPTED_IMAGE',
                    'error_code' => 'CORRUPTED_IMAGE',
                    'error_message' => 'File gambar tidak dapat dibaca atau rusak.',
                ];

                continue;
            }

            [$width, $height] = $imageInfo;
            if ($width > 8000 || $height > 8000 || ($width * $height) > 64_000_000) {
                $parsedItems[] = [
                    'file' => $file,
                    'identifier' => $baseIdentifier,
                    'status' => 'IMAGE_DIMENSION_TOO_LARGE',
                    'error_code' => 'IMAGE_DIMENSION_TOO_LARGE',
                    'error_message' => "Dimensi gambar ({$width}x{$height}) melebihi batas aman.",
                ];

                continue;
            }

            // Count identifier occurrences in ZIP
            $identifierCounts[$baseIdentifier] = ($identifierCounts[$baseIdentifier] ?? 0) + 1;

            $parsedItems[] = [
                'file' => $file,
                'identifier' => $baseIdentifier,
                'status' => null, // Pending DB match
                'error_code' => null,
                'error_message' => null,
            ];
        }

        // 2. Query DB Users in bulk for matching identifiers
        $validIdentifiers = array_keys(array_filter($identifierCounts, fn ($count) => $count === 1));
        $usersMap = $this->queryUsersByIdentifier($validIdentifiers, $matchingType);

        // 3. Construct preview items
        $results = [];
        foreach ($parsedItems as $item) {
            $file = $item['file'];
            $identifier = $item['identifier'];

            // Already flagged (security, invalid filename, format, corrupted, dimension)
            if ($item['status'] !== null) {
                $results[] = [
                    'original_filename' => $file['original_filename'],
                    'temporary_path' => $file['temporary_path'],
                    'detected_extension' => $file['extension'],
                    'detected_mime' => $file['mime'],
                    'identifier_type' => $matchingType,
                    'identifier' => $identifier,
                    'user_id' => null,
                    'status' => $item['status'],
                    'planned_action' => 'none',
                    'error_code' => $item['error_code'],
                    'error_message' => $item['error_message'],
                    'old_photo_path' => null,
                    'input_size' => $file['size'],
                ];

                continue;
            }

            // Check Duplicate File Identifier in ZIP
            if (($identifierCounts[$identifier] ?? 0) > 1) {
                $results[] = [
                    'original_filename' => $file['original_filename'],
                    'temporary_path' => $file['temporary_path'],
                    'detected_extension' => $file['extension'],
                    'detected_mime' => $file['mime'],
                    'identifier_type' => $matchingType,
                    'identifier' => $identifier,
                    'user_id' => null,
                    'status' => 'DUPLICATE_FILE_IDENTIFIER',
                    'planned_action' => 'none',
                    'error_code' => 'DUPLICATE_FILE_IDENTIFIER',
                    'error_message' => 'Terdapat lebih dari satu file dengan identifier yang sama dalam ZIP.',
                    'old_photo_path' => null,
                    'input_size' => $file['size'],
                ];

                continue;
            }

            // Check DB Users
            $users = $usersMap->get($identifier, collect());

            if ($users->count() === 0) {
                $results[] = [
                    'original_filename' => $file['original_filename'],
                    'temporary_path' => $file['temporary_path'],
                    'detected_extension' => $file['extension'],
                    'detected_mime' => $file['mime'],
                    'identifier_type' => $matchingType,
                    'identifier' => $identifier,
                    'user_id' => null,
                    'status' => 'USER_NOT_FOUND',
                    'planned_action' => 'none',
                    'error_code' => 'USER_NOT_FOUND',
                    'error_message' => "Tidak ada user dengan {$matchingType} '{$identifier}'.",
                    'old_photo_path' => null,
                    'input_size' => $file['size'],
                ];

                continue;
            }

            if ($users->count() > 1) {
                $results[] = [
                    'original_filename' => $file['original_filename'],
                    'temporary_path' => $file['temporary_path'],
                    'detected_extension' => $file['extension'],
                    'detected_mime' => $file['mime'],
                    'identifier_type' => $matchingType,
                    'identifier' => $identifier,
                    'user_id' => null,
                    'status' => 'DUPLICATE_USER_IDENTIFIER',
                    'planned_action' => 'none',
                    'error_code' => 'DUPLICATE_USER_IDENTIFIER',
                    'error_message' => "Terdapat lebih dari satu user dengan {$matchingType} '{$identifier}'.",
                    'old_photo_path' => null,
                    'input_size' => $file['size'],
                ];

                continue;
            }

            // Single user matched
            /** @var User $user */
            $user = $users->first();
            $hasExistingPhoto = ! empty($user->photo_path);

            if (! $hasExistingPhoto) {
                $status = 'MATCHED_NEW';
                $action = 'import';
                $message = 'User cocok dan belum memiliki foto profil.';
            } else {
                if ($existingPolicy === 'skip') {
                    $status = 'SKIPPED_EXISTING';
                    $action = 'none';
                    $message = 'User cocok dan sudah memiliki foto profil (Kebijakan: Lewati).';
                } else {
                    $status = 'MATCHED_REPLACE';
                    $action = 'replace';
                    $message = 'User cocok dan foto profil existing akan diganti.';
                }
            }

            $results[] = [
                'original_filename' => $file['original_filename'],
                'temporary_path' => $file['temporary_path'],
                'detected_extension' => $file['extension'],
                'detected_mime' => $file['mime'],
                'identifier_type' => $matchingType,
                'identifier' => $identifier,
                'user_id' => $user->id,
                'status' => $status,
                'planned_action' => $action,
                'error_code' => null,
                'error_message' => $message,
                'old_photo_path' => $user->photo_path,
                'input_size' => $file['size'],
            ];
        }

        return $results;
    }

    /**
     * Extract base identifier from filename (e.g. 22001001.jpg -> 22001001).
     */
    public function extractBaseIdentifier(string $filename): string
    {
        $info = pathinfo($filename);
        $basename = $info['filename'] ?? '';

        return trim($basename);
    }

    /**
     * Check if filename is exact NIS/NIP identifier without additional suffixes or prefixes.
     */
    protected function isValidFilenameIdentifier(string $filename, string $baseIdentifier): bool
    {
        if ($baseIdentifier === '') {
            return false;
        }

        // Must not contain spaces, parentheses, or unwanted symbols e.g. "Ahmad-22001001.jpg", "22001001 (1).jpg"
        if (preg_match('/[\s\(\)\-\_]/', $baseIdentifier)) {
            return false;
        }

        // Must strictly equal trimmed base identifier
        return true;
    }

    /**
     * Query users from database by exact string identifier for given matching type.
     *
     * @param  array<int, string>  $identifiers
     * @return Collection<string, Collection<int, User>>
     */
    protected function queryUsersByIdentifier(array $identifiers, string $matchingType): Collection
    {
        if (empty($identifiers)) {
            return collect();
        }

        $query = User::query();

        if ($matchingType === 'nis') {
            $query->where('type', 'student')->whereIn('nis', $identifiers);
        } else {
            $query->whereIn('type', ['teacher', 'staff'])->whereIn('nip', $identifiers);
        }

        $users = $query->get();

        // Group by exact NIS/NIP string to preserve leading zeros
        return $users->groupBy(function (User $user) use ($matchingType) {
            return (string) ($matchingType === 'nis' ? $user->nis : $user->nip);
        });
    }
}
