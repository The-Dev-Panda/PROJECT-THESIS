<?php
// Central database connection.
// Supports SQLite (local development), MySQL, and PostgreSQL.
// Set the DB_DRIVER environment variable to 'mysql' or 'pgsql' for production.
// When DB_DRIVER is not set (or is 'sqlite'), the local SQLite file is used.

// Load environment variables from file if not already set by the platform.
if (!function_exists('loadEnv')) {
    require_once __DIR__ . '/../load_env.php';
}

$_dbDriver = strtolower($_ENV['DB_DRIVER'] ?? (getenv('DB_DRIVER') ?: 'sqlite'));

if ($_dbDriver === 'mysql') {
    $_dbHost   = $_ENV['DB_HOST']   ?? (getenv('DB_HOST')   ?: 'localhost');
    $_dbPort   = $_ENV['DB_PORT']   ?? (getenv('DB_PORT')   ?: '3306');
    $_dbName   = $_ENV['DB_NAME']   ?? (getenv('DB_NAME')   ?: '');
    $_dbUser   = $_ENV['DB_USER']   ?? (getenv('DB_USER')   ?: '');
    $_dbPass   = $_ENV['DB_PASS']   ?? (getenv('DB_PASS')   ?: '');
    $_dsn = "mysql:host={$_dbHost};port={$_dbPort};dbname={$_dbName};charset=utf8mb4";
    $pdo = new PDO($_dsn, $_dbUser, $_dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} elseif ($_dbDriver === 'pgsql') {
    $_dbHost   = $_ENV['DB_HOST']   ?? (getenv('DB_HOST')   ?: 'localhost');
    $_dbPort   = $_ENV['DB_PORT']   ?? (getenv('DB_PORT')   ?: '5432');
    $_dbName   = $_ENV['DB_NAME']   ?? (getenv('DB_NAME')   ?: '');
    $_dbUser   = $_ENV['DB_USER']   ?? (getenv('DB_USER')   ?: '');
    $_dbPass   = $_ENV['DB_PASS']   ?? (getenv('DB_PASS')   ?: '');
    $_dsn = "pgsql:host={$_dbHost};port={$_dbPort};dbname={$_dbName}";
    $pdo = new PDO($_dsn, $_dbUser, $_dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} else {
    // SQLite – default for local development.
    $dbPath = __DIR__ . '/../Database/DB.sqlite';
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 10);
    $pdo->exec('PRAGMA busy_timeout = 10000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
}

// Clean up temporary variables so they do not pollute the including script's scope.
unset($_dbDriver, $_dbHost, $_dbPort, $_dbName, $_dbUser, $_dbPass, $_dsn);
