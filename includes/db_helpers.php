<?php
/**
 * Cross-database utility helpers.
 *
 * These functions abstract away differences between SQLite, MySQL, and
 * PostgreSQL so that the rest of the application can be database-agnostic.
 */

/**
 * Returns all column names for a given table.
 *
 * Works with SQLite (PRAGMA table_info), MySQL, and PostgreSQL
 * (information_schema.columns).
 *
 * @param  PDO    $pdo       Active database connection.
 * @param  string $tableName Name of the table to inspect.
 * @return string[]          Array of lower-cased column names.
 */
function getTableColumns(PDO $pdo, string $tableName): array
{
    $driver  = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columns = [];

    if ($driver === 'sqlite') {
        // PRAGMA does not support parameter binding, but the table name is
        // always supplied by the application (not user input), so quoting is
        // sufficient.
        $quoted = str_replace('"', '""', $tableName);
        $stmt   = $pdo->query('PRAGMA table_info("' . $quoted . '")');
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (isset($row['name'])) {
                    $columns[] = strtolower((string)$row['name']);
                }
            }
        }
    } elseif ($driver === 'mysql') {
        $stmt = $pdo->prepare(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$tableName]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = strtolower((string)$row['COLUMN_NAME']);
        }
    } elseif ($driver === 'pgsql') {
        $stmt = $pdo->prepare(
            'SELECT column_name FROM information_schema.columns
              WHERE table_schema = current_schema() AND table_name = ?'
        );
        $stmt->execute([$tableName]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = strtolower((string)$row['column_name']);
        }
    }

    return $columns;
}

/**
 * Returns true if the given table exists in the database.
 *
 * Works with SQLite, MySQL, and PostgreSQL.
 *
 * @param  PDO    $pdo       Active database connection.
 * @param  string $tableName Name of the table to check.
 * @return bool
 */
function tableExists(PDO $pdo, string $tableName): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
        );
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    } elseif ($driver === 'mysql') {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
        );
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    } elseif ($driver === 'pgsql') {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables
              WHERE table_schema = current_schema() AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$tableName]);
        return (bool)$stmt->fetchColumn();
    }

    return false;
}
