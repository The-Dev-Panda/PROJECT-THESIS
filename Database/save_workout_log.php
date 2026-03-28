<?php
header('Content-Type: application/json');

$dbPath = __DIR__ . '/DB.sqlite';

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    if (session_status() === PHP_SESSION_NONE) session_start();

    // Allow both staff and regular users
    if (empty($_SESSION['id']) || empty($_SESSION['user_type'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login.']);
        exit();
    }

    $sessionUserType = strtolower($_SESSION['user_type']);
    $sessionUserId   = (int)$_SESSION['id'];

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) throw new Exception('Invalid request payload');

    $exerciseId = isset($input['exercise_id']) ? (int)$input['exercise_id'] : 0;
    $reps       = isset($input['reps'])        ? (int)$input['reps']        : 0;
    $sets       = isset($input['sets'])        ? (int)$input['sets']        : 1;
    $weight     = isset($input['weight'])      ? (float)$input['weight']    : 0.0;

    // Staff sends user_id directly; members use their own session id
    if ($sessionUserType === 'staff' || $sessionUserType === 'admin') {
        $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($userId <= 0) throw new Exception('Missing user_id for staff log entry');
    } else {
        // Regular member — always use their own session id
        $userId = $sessionUserId;
    }

    if ($exerciseId <= 0 || $reps <= 0 || $sets <= 0) {
        throw new Exception('Missing required fields');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
    $db->exec('PRAGMA busy_timeout = 10000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('PRAGMA foreign_keys = ON');

    // Verify user exists
    $userStmt = $db->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    if (!$userStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Member not found');
    }

    // Verify exercise exists
    $exStmt = $db->prepare('SELECT exercise_id FROM exercises WHERE exercise_id = :eid LIMIT 1');
    $exStmt->execute([':eid' => $exerciseId]);
    if (!$exStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Invalid exercise selection');
    }

    // INSERT with sets
    $insertStmt = $db->prepare('
    INSERT INTO workout_logs (user_id, exercise_id, weight, sets, reps, logged_at)
    VALUES (:user_id, :exercise_id, :weight, :sets, :reps, :logged_at)
');
$insertStmt->execute([
    ':user_id'     => $userId,
    ':exercise_id' => $exerciseId,
    ':weight'      => $weight,
    ':sets'        => $sets,
    ':reps'        => $reps,
    ':logged_at'   => date('Y-m-d H:i:s'),
]);

    echo json_encode([
        'success'     => true,
        'message'     => 'Workout log saved',
        'log_id'      => (int)$db->lastInsertId(),
        'user_id'     => $userId,
        'exercise_id' => $exerciseId,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}