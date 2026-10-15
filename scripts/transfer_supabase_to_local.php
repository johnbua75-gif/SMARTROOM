<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

Dotenv::createImmutable($root)->safeLoad();

$environment = array_merge($_ENV, $_SERVER);
$getEnvironment = static fn (string $key, ?string $default = null): ?string => isset($environment[$key])
    ? (string) $environment[$key]
    : (($value = getenv($key)) !== false ? (string) $value : $default);

$sourceHost = $getEnvironment('DB_HOST');
$sourcePort = $getEnvironment('DB_PORT', '5432');
$sourceDatabase = $getEnvironment('DB_DATABASE');
$sourceUsername = $getEnvironment('DB_USERNAME');
$sourcePassword = $getEnvironment('DB_PASSWORD');

if (
    $getEnvironment('DB_CONNECTION') !== 'pgsql'
    || $sourceHost === null
    || ! str_contains($sourceHost, 'supabase.com')
    || $sourceDatabase === null
    || $sourceUsername === null
    || $sourcePassword === null
) {
    throw new RuntimeException('Refusing export: .env does not resolve to the expected Supabase PostgreSQL connection.');
}

$localSettings = parse_ini_file($root.'/.env.local-migrate', false, INI_SCANNER_RAW);

if (
    ! is_array($localSettings)
    || ($localSettings['DB_CONNECTION'] ?? null) !== 'mysql'
    || ($localSettings['DB_HOST'] ?? null) !== '127.0.0.1'
    || ($localSettings['DB_PORT'] ?? null) !== '3306'
    || ($localSettings['DB_DATABASE'] ?? null) !== 'smartroom_local'
) {
    throw new RuntimeException('Refusing import: .env.local-migrate does not identify the expected local database.');
}

$tables = [
    'users',
    'classrooms',
    'courses',
    'schedules',
    'classroom_user_access',
    'reservations',
    'access_cards',
    'devices',
    'attendance_sessions',
    'personal_access_tokens',
    'access_logs',
];

$source = new PDO(
    sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', $sourceHost, $sourcePort, $sourceDatabase),
    $sourceUsername,
    $sourcePassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);

$sourceRows = [];

foreach ($tables as $table) {
    $statement = $source->query(sprintf('SELECT * FROM public."%s" ORDER BY "id"', $table));
    $sourceRows[$table] = $statement->fetchAll();
}

$snapshotPath = $root.'/backups/supabase-readonly-export-'.gmdate('Ymd-His').'.json';
$snapshot = json_encode($sourceRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

if (file_put_contents($snapshotPath, $snapshot, LOCK_EX) === false) {
    throw new RuntimeException('Unable to save the read-only Supabase export before local import.');
}

chmod($snapshotPath, 0600);

$local = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=smartroom_local;charset=utf8mb4',
    (string) $localSettings['DB_USERNAME'],
    (string) ($localSettings['DB_PASSWORD'] ?? ''),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);

if ($local->query('SELECT DATABASE()')->fetchColumn() !== 'smartroom_local') {
    throw new RuntimeException('Refusing import: local connection selected an unexpected database.');
}

$local->exec("SET time_zone = '+00:00'");

foreach ($tables as $table) {
    $count = (int) $local->query(sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();

    if ($count !== 0) {
        throw new RuntimeException(sprintf('Refusing import: local table %s is not empty.', $table));
    }
}

$local->beginTransaction();

try {
    foreach ($tables as $table) {
        foreach ($sourceRows[$table] as $row) {
            if ($table === 'users') {
                $row['must_change_password'] = in_array($row['must_change_password'], [true, 1, '1', 't', 'true'], true) ? 1 : 0;
            }

            if ($table === 'devices' && $row['last_seen_at'] !== null) {
                $timestamp = (new DateTimeImmutable((string) $row['last_seen_at']))->setTimezone(new DateTimeZone('UTC'));

                if ($timestamp->format('u') !== '000000') {
                    throw new RuntimeException('A device timestamp has fractional seconds that the local schema cannot preserve.');
                }

                $row['last_seen_at'] = $timestamp->format('Y-m-d H:i:s');
            }

            if ($table === 'access_logs' && $row['metadata'] !== null) {
                json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR);
            }

            $columns = array_keys($row);
            $quotedColumns = array_map(static fn (string $column): string => sprintf('`%s`', $column), $columns);
            $placeholders = array_fill(0, count($columns), '?');
            $insert = $local->prepare(sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s)',
                $table,
                implode(', ', $quotedColumns),
                implode(', ', $placeholders),
            ));
            $insert->execute(array_values($row));
        }
    }

    $local->commit();
} catch (Throwable $exception) {
    if ($local->inTransaction()) {
        $local->rollBack();
    }

    throw $exception;
}

$report = [
    'snapshot_file' => $snapshotPath,
    'row_counts' => [],
    'users_by_id' => [],
    'reservation_spot_checks' => [],
    'sanctum_tokens' => [],
    'type_conversions' => [
        'users.supabase_user_id' => 'PostgreSQL UUID text copied unchanged to MySQL CHAR(36).',
        'users.must_change_password' => 'PostgreSQL boolean mapped to MySQL TINYINT(1), true/false to 1/0.',
        'access_logs.metadata' => 'Valid JSON text copied unchanged; Laravel migration maps MySQL JSON to LONGTEXT on MariaDB.',
        'devices.last_seen_at' => 'PostgreSQL timestamptz normalized to UTC for MySQL TIMESTAMP; export aborts if fractional seconds cannot be represented.',
        'timestamps_without_time_zone' => 'Copied as wall-clock strings unchanged; local MySQL session is UTC.',
        'serial_ids' => 'Original IDs inserted explicitly; MySQL AUTO_INCREMENT reset to MAX(id) + 1.',
    ],
];

foreach ($tables as $table) {
    $sourceCount = count($sourceRows[$table]);
    $localCount = (int) $local->query(sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();
    $sourceCountAfterImport = (int) $source->query(sprintf('SELECT COUNT(*) FROM public."%s"', $table))->fetchColumn();

    $report['row_counts'][$table] = [
        'supabase_export' => $sourceCount,
        'supabase_after_import' => $sourceCountAfterImport,
        'smartroom_local' => $localCount,
        'match' => $sourceCount === $sourceCountAfterImport && $sourceCount === $localCount,
    ];
}

$sourceUsers = $sourceRows['users'];
$localUsers = $local->query('SELECT id, email, password FROM users ORDER BY id')->fetchAll();
$localUsersById = [];

foreach ($localUsers as $user) {
    $localUsersById[(string) $user['id']] = $user;
}

$userMismatches = 0;

foreach ($sourceUsers as $user) {
    $localUser = $localUsersById[(string) $user['id']] ?? null;

    if ($localUser === null || $localUser['email'] !== $user['email']) {
        $userMismatches++;
    }
}

$report['users_by_id_email'] = [
    'supabase_count' => count($sourceUsers),
    'local_count' => count($localUsers),
    'mismatch_count' => $userMismatches,
    'all_match' => count($sourceUsers) === count($localUsers) && $userMismatches === 0,
];

foreach (array_slice($sourceUsers, 0, 3) as $user) {
    $localUser = $localUsersById[(string) $user['id']] ?? null;
    $report['password_hash_spot_checks'][] = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'password_hash_match' => $localUser !== null && hash_equals((string) $user['password'], (string) $localUser['password']),
    ];
}

foreach (array_slice($sourceRows['reservations'], 0, 3) as $reservation) {
    $statement = $local->prepare('SELECT id, user_id, classroom_id, start_at, end_at, status FROM reservations WHERE id = ?');
    $statement->execute([$reservation['id']]);
    $localReservation = $statement->fetch();
    $reservationMatches = $localReservation !== false;

    foreach (['user_id', 'classroom_id', 'start_at', 'end_at', 'status'] as $column) {
        $reservationMatches = $reservationMatches && (string) $localReservation[$column] === (string) $reservation[$column];
    }

    $report['reservation_spot_checks'][] = [
        'id' => (int) $reservation['id'],
        'matches' => $reservationMatches,
    ];
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
