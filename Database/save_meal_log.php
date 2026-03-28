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

function validateCsrfOrExit(?string $token): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || !is_string($token) || $token === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
        exit();
    }
}

function ensureMealLogsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS meal_logs (
            meal_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            logged_date DATE NOT NULL,
            meal_type VARCHAR(50) NOT NULL,
            food_name VARCHAR(255) NOT NULL,
            quantity DOUBLE NOT NULL,
            calories INT NOT NULL,
            protein DOUBLE NOT NULL,
            carbs DOUBLE NOT NULL,
            fat DOUBLE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
    $db->exec('CREATE INDEX idx_meal_logs_user_date ON meal_logs(user_id, logged_date)');
}

try {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }

    $sessionUserId = requireUserSession();

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    validateCsrfOrExit(isset($input['csrf_token']) ? (string)$input['csrf_token'] : null);

    $loggedDate = isset($input['logged_date']) ? trim((string)$input['logged_date']) : date('Y-m-d');
    $mealType = isset($input['meal_type']) ? trim((string)$input['meal_type']) : '';
    $foodName = isset($input['food_name']) ? trim((string)$input['food_name']) : '';
    $quantity = isset($input['quantity']) ? (float)$input['quantity'] : 0.0;
    $calories = isset($input['calories']) ? (int)$input['calories'] : 0;
    $protein = isset($input['protein']) ? (float)$input['protein'] : 0.0;
    $carbs = isset($input['carbs']) ? (float)$input['carbs'] : 0.0;
    $fat = isset($input['fat']) ? (float)$input['fat'] : 0.0;

    $allowedMealTypes = ['Breakfast', 'Lunch', 'Snack', 'Dinner'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loggedDate)) {
        throw new Exception('Invalid logged_date format');
    }
    if (!in_array($mealType, $allowedMealTypes, true)) {
        throw new Exception('Invalid meal_type');
    }
    if ($foodName === '') {
        throw new Exception('food_name is required');
    }
    if ($quantity <= 0 || $calories < 0 || $protein < 0 || $carbs < 0 || $fat < 0) {
        throw new Exception('Invalid nutrition values');
    }

    require_once __DIR__ . '/../Login/connection.php';
    $db = $pdo;
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);

    // MySQL table should be created separately. Ensure table exists in migration step.

    $insertStmt = $db->prepare(
        'INSERT INTO meal_logs (user_id, logged_date, meal_type, food_name, quantity, calories, protein, carbs, fat)
         VALUES (:user_id, :logged_date, :meal_type, :food_name, :quantity, :calories, :protein, :carbs, :fat)'
    );

    $insertStmt->execute([
        ':user_id' => $sessionUserId,
        ':logged_date' => $loggedDate,
        ':meal_type' => $mealType,
        ':food_name' => $foodName,
        ':quantity' => $quantity,
        ':calories' => $calories,
        ':protein' => $protein,
        ':carbs' => $carbs,
        ':fat' => $fat,
    ]);

    echo json_encode([
        'success' => true,
        'meal_id' => (int)$db->lastInsertId(),
        'logged_date' => $loggedDate,
    ]);
} catch (Exception $e) {
    http_response_code(http_response_code() >= 400 ? http_response_code() : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
