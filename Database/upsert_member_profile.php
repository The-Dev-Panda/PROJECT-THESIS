<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');
$dbPath = __DIR__ . '/DB.sqlite';

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = isset($input['member_ref']) ? trim((string)$input['member_ref']) : '';
    if ($memberRef === '' && isset($_SESSION['id'])) {
        $memberRef = (string)$_SESSION['id'];
    }

    if ($memberRef === '') {
        throw new Exception('Member reference is required');
    }

    $age = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
    $heightCm = isset($input['height_cm']) && $input['height_cm'] !== '' ? (float)$input['height_cm'] : null;
    $weightKg = isset($input['weight_kg']) && $input['weight_kg'] !== '' ? (float)$input['weight_kg'] : null;
    $fitnessLevel = isset($input['fitness_level']) ? trim((string)$input['fitness_level']) : null;
    $goal = isset($input['goal']) ? trim((string)$input['goal']) : null;

    if ($age !== null && $age < 0) {
        throw new Exception('Invalid age value');
    }
    if ($heightCm !== null && $heightCm <= 0) {
        throw new Exception('Invalid height value');
    }
    if ($weightKg !== null && $weightKg <= 0) {
        throw new Exception('Invalid weight value');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (ctype_digit($memberRef)) {
        $userStmt = $db->prepare('SELECT id, user_type FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => (int)$memberRef]);
    } else {
        $userStmt = $db->prepare('SELECT id, user_type FROM users WHERE username = :username LIMIT 1');
        $userStmt->execute([':username' => $memberRef]);
    }

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('Member not found');
    }
    if (strtolower((string)$user['user_type']) !== 'user') {
        throw new Exception('Selected account is not a member user');
    }

    $userId = (int)$user['id'];

    $existsStmt = $db->prepare('SELECT id FROM member_profiles WHERE user_id = :user_id LIMIT 1');
    $existsStmt->execute([':user_id' => $userId]);
    $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $updateStmt = $db->prepare('UPDATE member_profiles SET age = :age, height_cm = :height_cm, weight_kg = :weight_kg, fitness_level = :fitness_level, goal = :goal, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id');
        $updateStmt->execute([
            ':age' => $age,
            ':height_cm' => $heightCm,
            ':weight_kg' => $weightKg,
            ':fitness_level' => $fitnessLevel,
            ':goal' => $goal,
            ':user_id' => $userId
        ]);
    } else {
        $insertStmt = $db->prepare('INSERT INTO member_profiles (user_id, age, height_cm, weight_kg, fitness_level, goal) VALUES (:user_id, :age, :height_cm, :weight_kg, :fitness_level, :goal)');
        $insertStmt->execute([
            ':user_id' => $userId,
            ':age' => $age,
            ':height_cm' => $heightCm,
            ':weight_kg' => $weightKg,
            ':fitness_level' => $fitnessLevel,
            ':goal' => $goal
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Member profile saved',
        'user_id' => $userId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
