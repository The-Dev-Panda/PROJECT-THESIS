<?php
header('Content-Type: application/json');

$dbPath = __DIR__ . '/DB.sqlite';

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = isset($input['member_ref']) ? trim((string)$input['member_ref']) : '';
    $exerciseId = isset($input['exercise_id']) ? (int)$input['exercise_id'] : 0;
    $reps = isset($input['reps']) ? (int)$input['reps'] : 0;
    $weight = isset($input['weight']) ? (float)$input['weight'] : 0.0;

    if ($memberRef === '' || $exerciseId <= 0 || $reps <= 0) {
        throw new Exception('Missing required fields');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Resolve user_id from numeric id or username.
    if (ctype_digit($memberRef)) {
        $userStmt = $db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => (int)$memberRef]);
    } else {
        $userStmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $userStmt->execute([':username' => $memberRef]);
    }
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('Member not found in users table');
    }
    $userId = (int)$user['id'];

    $exerciseStmt = $db->prepare('SELECT exercise_id FROM exercises WHERE exercise_id = :exercise_id LIMIT 1');
    $exerciseStmt->execute([':exercise_id' => $exerciseId]);
    if (!$exerciseStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Invalid exercise selection');
    }

    $insertStmt = $db->prepare('INSERT INTO workout_logs (user_id, exercise_id, weight, reps) VALUES (:user_id, :exercise_id, :weight, :reps)');
    $insertStmt->execute([
        ':user_id' => $userId,
        ':exercise_id' => $exerciseId,
        ':weight' => $weight,
        ':reps' => $reps
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Workout log saved',
        'log_id' => (int)$db->lastInsertId(),
        'user_id' => $userId,
        'exercise_id' => $exerciseId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
