<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

function requireUserSession(): int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['id']) || empty($_SESSION['user_type']) || strtolower((string)$_SESSION['user_type']) !== 'user') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login as member.']);
        exit();
    }

    return (int)$_SESSION['id'];
}

function ensureMealLogsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS meal_logs (
            meal_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            logged_date TEXT NOT NULL,
            meal_type TEXT NOT NULL,
            food_name TEXT NOT NULL,
            quantity REAL NOT NULL,
            calories INTEGER NOT NULL,
            protein REAL NOT NULL,
            carbs REAL NOT NULL,
            fat REAL NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );
    $db->exec('CREATE INDEX IF NOT EXISTS idx_meal_logs_user_date ON meal_logs(user_id, logged_date)');
}

try {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }

    $sessionUserId = requireUserSession();

    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    if ($days < 1) {
        $days = 1;
    }
    if ($days > 31) {
        $days = 31;
    }

    $endDate = isset($_GET['end_date']) ? trim((string)$_GET['end_date']) : date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        throw new Exception('Invalid end_date format');
    }

    $endTs = strtotime($endDate . ' 00:00:00');
    if ($endTs === false) {
        throw new Exception('Invalid end_date');
    }

    $startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days', $endTs));

    require_once __DIR__ . '/../Login/connection.php';
    $db = $pdo;
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);

    // MySQL table should already exist from migration.

    $stmt = $db->prepare(
        'SELECT meal_id, logged_date, meal_type, food_name, quantity, calories, protein, carbs, fat, created_at
         FROM meal_logs
         WHERE user_id = :user_id
           AND logged_date >= :start_date
           AND logged_date <= :end_date
         ORDER BY logged_date DESC, created_at DESC, meal_id DESC'
    );

    $stmt->execute([
        ':user_id' => $sessionUserId,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'days' => $days,
        'items' => $rows,
    ]);
} catch (Exception $e) {
    http_response_code(http_response_code() >= 400 ? http_response_code() : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
