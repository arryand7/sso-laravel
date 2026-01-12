#!/usr/bin/env php
<?php
declare(strict_types=1);

$options = getopt('', [
    'dry-run',
    'limit::',
    'update-lastname',
]);

$dryRun = array_key_exists('dry-run', $options);
$limit = isset($options['limit']) ? (int) $options['limit'] : null;
$updateLastname = array_key_exists('update-lastname', $options);

$gateEnv = parseEnvFile(__DIR__ . '/../.env');
$gateDb = [
    'host' => $gateEnv['DB_HOST'] ?? '127.0.0.1',
    'port' => $gateEnv['DB_PORT'] ?? '3306',
    'name' => $gateEnv['DB_DATABASE'] ?? 'laravel',
    'user' => $gateEnv['DB_USERNAME'] ?? 'root',
    'pass' => $gateEnv['DB_PASSWORD'] ?? '',
];

$lmsCfg = parseMoodleConfig('/var/www/lms.sabira-iibs.id/moodle/config.php');
$lmsDb = [
    'host' => $lmsCfg['dbhost'] ?? '127.0.0.1',
    'port' => $lmsCfg['dbport'] ?? '3306',
    'name' => $lmsCfg['dbname'] ?? 'open_lms',
    'user' => $lmsCfg['dbuser'] ?? 'root',
    'pass' => $lmsCfg['dbpass'] ?? '',
    'prefix' => $lmsCfg['prefix'] ?? 'mdl_',
];

$gatePdo = pdoConnect($gateDb);
$lmsPdo = pdoConnect($lmsDb);

$prefix = $lmsDb['prefix'];
$userTable = $prefix . 'user';

$gateSql = 'SELECT username, name, email, status FROM users WHERE email IS NOT NULL AND email <> \'\'';
if ($limit !== null && $limit > 0) {
    $gateSql .= ' LIMIT ' . (int) $limit;
}

$gateUsers = $gatePdo->query($gateSql)->fetchAll(PDO::FETCH_ASSOC);

$dupStmt = $lmsPdo->query("SELECT firstname FROM {$userTable} WHERE deleted = 0 GROUP BY firstname HAVING COUNT(*) > 1");
$duplicateFirstnames = [];
foreach ($dupStmt->fetchAll(PDO::FETCH_COLUMN) as $firstname) {
    $duplicateFirstnames[$firstname] = true;
}

$findByFirstname = $lmsPdo->prepare("SELECT id, username, firstname, lastname, email FROM {$userTable} WHERE deleted = 0 AND firstname = ?");
$findByEmail = $lmsPdo->prepare("SELECT id, username, firstname, lastname, email FROM {$userTable} WHERE deleted = 0 AND email = ?");
$findByUsername = $lmsPdo->prepare("SELECT id, username, firstname, lastname, email FROM {$userTable} WHERE deleted = 0 AND username = ?");

$updateUser = $lmsPdo->prepare("UPDATE {$userTable} SET email = ?, timemodified = ? WHERE id = ?");
$updateUserWithLastname = $lmsPdo->prepare("UPDATE {$userTable} SET email = ?, lastname = ?, timemodified = ? WHERE id = ?");

$insertUser = $lmsPdo->prepare(
    "INSERT INTO {$userTable} (auth, confirmed, policyagreed, deleted, suspended, mnethostid, username, password, idnumber, firstname, lastname, email, emailstop, timecreated, timemodified)
     VALUES ('oauth2', 1, 0, 0, ?, 1, ?, '', ?, ?, ?, ?, 0, ?, ?)"
);

$stats = [
    'processed' => 0,
    'matched' => 0,
    'updated' => 0,
    'inserted' => 0,
    'skipped_duplicate_firstname' => 0,
    'skipped_email_conflict' => 0,
    'skipped_existing_email' => 0,
    'skipped_existing_username' => 0,
];

$now = time();

foreach ($gateUsers as $gateUser) {
    $stats['processed']++;
    $username = trim((string) $gateUser['username']);
    $fullName = trim((string) $gateUser['name']);
    $email = strtolower(trim((string) $gateUser['email']));
    $status = trim((string) $gateUser['status']);

    if ($username === '' || $email === '') {
        continue;
    }

    if (isset($duplicateFirstnames[$username])) {
        $stats['skipped_duplicate_firstname']++;
        continue;
    }

    $findByFirstname->execute([$username]);
    $match = $findByFirstname->fetch(PDO::FETCH_ASSOC);

    if ($match) {
        $stats['matched']++;
        $findByEmail->execute([$email]);
        $emailOwner = $findByEmail->fetch(PDO::FETCH_ASSOC);
        if ($emailOwner && (int) $emailOwner['id'] !== (int) $match['id']) {
            $stats['skipped_email_conflict']++;
            continue;
        }

        if ($dryRun) {
            $stats['updated']++;
            continue;
        }

        if ($updateLastname) {
            $lastname = $fullName !== '' ? $fullName : $match['lastname'];
            $updateUserWithLastname->execute([$email, $lastname, $now, $match['id']]);
        } else {
            $updateUser->execute([$email, $now, $match['id']]);
        }
        $stats['updated']++;
        continue;
    }

    $findByEmail->execute([$email]);
    if ($findByEmail->fetch(PDO::FETCH_ASSOC)) {
        $stats['skipped_existing_email']++;
        continue;
    }

    $newUsername = $email;
    $findByUsername->execute([$newUsername]);
    if ($findByUsername->fetch(PDO::FETCH_ASSOC)) {
        $stats['skipped_existing_username']++;
        continue;
    }

    $lastname = $fullName !== '' ? $fullName : $username;
    $suspended = $status === 'active' ? 0 : 1;

    if ($dryRun) {
        $stats['inserted']++;
        continue;
    }

    $insertUser->execute([
        $suspended,
        $newUsername,
        $username,
        $username,
        $lastname,
        $email,
        $now,
        $now,
    ]);
    $stats['inserted']++;
}

echo "Sync completed.\n";
foreach ($stats as $key => $value) {
    echo "{$key}: {$value}\n";
}
if ($dryRun) {
    echo "Dry run enabled: no database changes were saved.\n";
}

function pdoConnect(array $db): PDO
{
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function parseEnvFile(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $data = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $data[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
    }
    return $data;
}

function parseMoodleConfig(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        return [];
    }
    $keys = ['dbtype', 'dbhost', 'dbname', 'dbuser', 'dbpass', 'dbport', 'prefix'];
    $data = [];
    foreach ($keys as $key) {
        $pattern = '/\\$CFG->' . preg_quote($key, '/') . '\\s*=\\s*\\\'([^\\\']*)\\\'\\s*;/';
        if (preg_match($pattern, $contents, $matches)) {
            $data[$key] = $matches[1];
        }
    }
    return $data;
}
