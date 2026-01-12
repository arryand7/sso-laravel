#!/usr/bin/env php
<?php
declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', [
    'students::',
    'staff::',
    'staff-info::',
    'dry-run',
    'update-passwords',
]);

$dataDir = '/var/www/sss.sabira-iibs.id/temp/import/DATA';
$studentsPath = $options['students'] ?? ($dataDir . '/student_email.csv');
$staffPath = $options['staff'] ?? ($dataDir . '/staff_email.csv');
$staffInfoPath = $options['staff-info'] ?? ($dataDir . '/data_guru.xlsx');

$dryRun = array_key_exists('dry-run', $options);
$updatePasswords = array_key_exists('update-passwords', $options);

if (!file_exists($studentsPath) && !file_exists($staffPath)) {
    fwrite(STDERR, "No input files found. Provide --students and/or --staff.\n");
    exit(1);
}

$roles = Role::whereIn('name', ['student', 'teacher', 'staff'])->get()->keyBy('name');
foreach (['student', 'teacher', 'staff'] as $roleName) {
    if (!isset($roles[$roleName])) {
        fwrite(STDERR, "Missing role: {$roleName}. Run RoleSeeder first.\n");
        exit(1);
    }
}

$staffTypeMap = [];
if (file_exists($staffInfoPath)) {
    $staffTypeMap = buildStaffTypeMapFromXlsx($staffInfoPath);
}

$stats = [
    'students' => 0,
    'staff' => 0,
    'created' => 0,
    'updated' => 0,
    'skipped' => 0,
];

if (file_exists($studentsPath)) {
    $stats['students'] = syncCsvUsers($studentsPath, 'student', $staffTypeMap, $dryRun, $updatePasswords, $stats);
}

if (file_exists($staffPath)) {
    $stats['staff'] = syncCsvUsers($staffPath, 'staff', $staffTypeMap, $dryRun, $updatePasswords, $stats);
}

echo "Sync completed.\n";
echo "Students processed: {$stats['students']}\n";
echo "Staff processed: {$stats['staff']}\n";
echo "Created: {$stats['created']}\n";
echo "Updated: {$stats['updated']}\n";
echo "Skipped: {$stats['skipped']}\n";
if ($dryRun) {
    echo "Dry run enabled: no database changes were saved.\n";
}

function syncCsvUsers(
    string $path,
    string $defaultType,
    array $staffTypeMap,
    bool $dryRun,
    bool $updatePasswords,
    array &$stats
): int {
    $handle = fopen($path, 'r');
    if ($handle === false) {
        fwrite(STDERR, "Failed to open {$path}\n");
        return 0;
    }

    $header = null;
    $processed = 0;
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        if ($header === null) {
            $header = buildHeaderMap($row);
            continue;
        }

        if (count(array_filter($row, fn($val) => trim((string) $val) !== '')) === 0) {
            continue;
        }

        $data = mapRowToUserData($row, $header, $defaultType, $staffTypeMap);
        if ($data === null) {
            $stats['skipped']++;
            continue;
        }

        $processed++;
        $result = upsertUser($data, $dryRun, $updatePasswords);
        $stats[$result]++;
    }

    fclose($handle);
    return $processed;
}

function buildHeaderMap(array $row): array
{
    $map = [];
    foreach ($row as $index => $value) {
        $key = normalizeHeader((string) $value);
        if ($key !== '') {
            $map[$key] = $index;
        }
    }
    return $map;
}

function normalizeHeader(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '', $value);
    return $value ?? '';
}

function getHeaderIndex(array $header, array $candidates): ?int
{
    foreach ($candidates as $candidate) {
        if (isset($header[$candidate])) {
            return $header[$candidate];
        }
    }
    return null;
}

function mapRowToUserData(array $row, array $header, string $defaultType, array $staffTypeMap): ?array
{
    $nameIndex = getHeaderIndex($header, ['name', 'nama']);
    $emailIndex = getHeaderIndex($header, ['emailaddress', 'email', 'emailaddressrecovery']);
    $passwordIndex = getHeaderIndex($header, ['password', 'katasandi', 'katasandi']);
    $nisIndex = getHeaderIndex($header, ['nis']);
    $nipIndex = getHeaderIndex($header, ['nip']);

    $name = $nameIndex !== null ? trim((string) ($row[$nameIndex] ?? '')) : '';
    $email = $emailIndex !== null ? trim((string) ($row[$emailIndex] ?? '')) : '';
    $passwordRaw = $passwordIndex !== null ? trim((string) ($row[$passwordIndex] ?? '')) : '';
    $nis = $nisIndex !== null ? trim((string) ($row[$nisIndex] ?? '')) : '';
    $nip = $nipIndex !== null ? trim((string) ($row[$nipIndex] ?? '')) : '';

    $username = $nis !== '' ? $nis : $nip;
    if ($username === '') {
        return null;
    }

    $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    if ($passwordRaw === '') {
        $passwordRaw = Str::random(12);
    }

    $type = $defaultType;
    if ($defaultType === 'staff' && $nip !== '') {
        $type = resolveStaffType($staffTypeMap[$nip] ?? '');
    }

    return [
        'name' => $name !== '' ? $name : $username,
        'username' => $username,
        'email' => $email,
        'password_raw' => $passwordRaw,
        'type' => $type,
        'nis' => $nis !== '' ? $nis : null,
        'nip' => $nip !== '' ? $nip : null,
    ];
}

function resolveStaffType(string $jenisPegawai): string
{
    $value = strtolower(trim($jenisPegawai));
    if ($value === '') {
        return 'staff';
    }
    if (str_contains($value, 'guru') || str_contains($value, 'teacher') || str_contains($value, 'ustadz') || str_contains($value, 'ustad')) {
        return 'teacher';
    }
    return 'staff';
}

function upsertUser(array $data, bool $dryRun, bool $updatePasswords): string
{
    $user = User::where('username', $data['username'])->first();
    if (!$user && $data['email']) {
        $user = User::where('email', $data['email'])->first();
    }

    $updates = [
        'name' => $data['name'],
        'email' => $data['email'],
        'type' => $data['type'],
        'nis' => $data['nis'],
        'nip' => $data['nip'],
        'status' => 'active',
    ];

    if (!$user) {
        $updates['password'] = Hash::make($data['password_raw']);
        $updates['email_verified_at'] = $data['email'] ? Carbon::now() : null;
        if ($dryRun) {
            return 'created';
        }
        $user = User::create(array_merge($updates, [
            'username' => $data['username'],
        ]));
        $user->syncRoles([$data['type']]);
        return 'created';
    }

    if ($updatePasswords) {
        $updates['password'] = Hash::make($data['password_raw']);
    }

    if ($dryRun) {
        return 'updated';
    }

    $user->update($updates);
    $user->syncRoles([$data['type']]);
    return 'updated';
}

function buildStaffTypeMapFromXlsx(string $path): array
{
    $rows = readXlsxRows($path);
    if ($rows === []) {
        return [];
    }

    $header = buildHeaderMap($rows[0]);
    $nipIndex = getHeaderIndex($header, ['nip', 'nipp']);
    $jenisIndex = getHeaderIndex($header, ['jenispegawai', 'jabatan']);
    if ($nipIndex === null || $jenisIndex === null) {
        return [];
    }

    $map = [];
    foreach (array_slice($rows, 1) as $row) {
        $nip = trim((string) ($row[$nipIndex] ?? ''));
        $jenis = trim((string) ($row[$jenisIndex] ?? ''));
        if ($nip !== '') {
            $map[$nip] = $jenis;
        }
    }

    return $map;
}

function readXlsxRows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return [];
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $root = simplexml_load_string($sharedXml);
        if ($root !== false) {
            foreach ($root->si as $si) {
                $text = '';
                foreach ($si->t as $t) {
                    $text .= (string) $t;
                }
                $sharedStrings[] = $text;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        return [];
    }

    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        $zip->close();
        return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            if ($ref === '') {
                continue;
            }
            $colIndex = columnToIndex($ref);
            $value = '';
            if (isset($cell->v)) {
                $value = (string) $cell->v;
                if ((string) $cell['t'] === 's') {
                    $value = $sharedStrings[(int) $value] ?? $value;
                }
            }
            $cells[$colIndex] = $value;
        }

        if ($cells === []) {
            continue;
        }

        $maxCol = max(array_keys($cells));
        $rowVals = array_fill(0, $maxCol + 1, '');
        foreach ($cells as $index => $value) {
            $rowVals[$index] = $value;
        }
        $rows[] = $rowVals;
    }

    $zip->close();
    return $rows;
}

function columnToIndex(string $ref): int
{
    $letters = '';
    for ($i = 0; $i < strlen($ref); $i++) {
        $ch = $ref[$i];
        if ($ch >= 'A' && $ch <= 'Z') {
            $letters .= $ch;
        } else {
            break;
        }
    }

    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
    }
    return $index - 1;
}
