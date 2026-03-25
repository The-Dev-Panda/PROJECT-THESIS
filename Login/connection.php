<?php
// Central SQLite connection.
// Must be path-portable (use __DIR__) so includes work regardless of current working directory.
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

// MYSQL CONNECTION
// $dsn = 'mysql:host=localhost;dbname=fitstop_db;charset=utf8';
// $username = 'root';
// $password = '';

// try {
//     $pdo = new PDO($dsn, $username, $password);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     echo "Database connected successfully!";
// } catch (PDOException $e) {
//     die('Database connection failed: ' . $e->getMessage());
// }
?>