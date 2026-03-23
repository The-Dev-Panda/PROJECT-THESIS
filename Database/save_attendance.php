<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = isset($input['member_ref']) ? trim((string)$input['member_ref']) : '';
    $source = isset($input['source']) ? trim((string)$input['source']) : '';

    if ($memberRef === '') {
        throw new Exception('Member reference is required');
    }

    if ($source !== 'qr' && $source !== 'manual') {
        throw new Exception('Invalid attendance source');
    }

    include('../Login/connection.php');
    $db = $pdo;

    $memberRefUpper = strtoupper($memberRef);
    if (preg_match('/^FS-\d{4}-(\d+)$/', $memberRefUpper, $matches)) {
        $memberRef = (string)((int)$matches[1]);
    }

    if (ctype_digit($memberRef)) {
        $userStmt = $db->prepare('SELECT id, username, first_name, last_name, user_type FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => (int)$memberRef]);
    } else {
        $userStmt = $db->prepare('SELECT id, username, first_name, last_name, user_type FROM users WHERE username = :username LIMIT 1');
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
    $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($displayName === '') {
        $displayName = (string)$user['username'];
    }

    $db->beginTransaction();

    // Enforce at most one attendance event per user per hour.
    $hourlyCheckStmt = $db->prepare("SELECT datetime FROM attendance WHERE user_id = :user_id AND datetime(datetime, 'localtime') >= datetime('now', 'localtime', '-59 minutes') ORDER BY datetime DESC LIMIT 1");
    $hourlyCheckStmt->execute([':user_id' => $userId]);
    $recentAttendance = $hourlyCheckStmt->fetchColumn();
    if ($recentAttendance !== false) {
        throw new Exception('Attendance already recorded within the last hour');
    }

    // Attendance is event-based. Point is credited once per member per calendar day.
    $pointCheckStmt = $db->prepare("SELECT 1 FROM attendance WHERE user_id = :user_id AND date(datetime, 'localtime') = date('now', 'localtime') LIMIT 1");
    $pointCheckStmt->execute([':user_id' => $userId]);
    $alreadyCreditedToday = (bool)$pointCheckStmt->fetchColumn();

    try {
        $insertStmt = $db->prepare('INSERT INTO attendance (user_id, datetime) VALUES (:user_id, CURRENT_TIMESTAMP)');
        $insertStmt->execute([':user_id' => $userId]);
        $attendanceId = (int)$db->lastInsertId();
        $pointAwarded = !$alreadyCreditedToday;
    } catch (PDOException $insertEx) {
        // Handle UNIQUE constraint on daily attendance (idx_attendance_user_day)
        if (strpos($insertEx->getMessage(), 'UNIQUE constraint failed') !== false) {
            $db->rollBack();
            throw new Exception('Attendance already recorded for today');
        }
        throw $insertEx;
    }

    $dailyStmt = $db->prepare("SELECT COUNT(*) AS total FROM attendance WHERE user_id = :user_id AND date(datetime, 'localtime') = date('now', 'localtime')");
    $dailyStmt->execute([':user_id' => $userId]);
    $dailyCount = (int)$dailyStmt->fetchColumn();

    $weeklyStmt = $db->prepare("SELECT COUNT(DISTINCT date(datetime, 'localtime')) AS total FROM attendance WHERE user_id = :user_id AND datetime(datetime, 'localtime') >= datetime('now', 'localtime', '-6 days')");
    $weeklyStmt->execute([':user_id' => $userId]);
    $weeklyCount = (int)$weeklyStmt->fetchColumn();

    $monthlyStmt = $db->prepare("SELECT COUNT(DISTINCT date(datetime, 'localtime')) AS total FROM attendance WHERE user_id = :user_id AND datetime(datetime, 'localtime') >= datetime('now', 'localtime', '-29 days')");
    $monthlyStmt->execute([':user_id' => $userId]);
    $monthlyCount = (int)$monthlyStmt->fetchColumn();

    $allTimePointsStmt = $db->prepare("SELECT COUNT(DISTINCT date(datetime, 'localtime')) AS total FROM attendance WHERE user_id = :user_id");
    $allTimePointsStmt->execute([':user_id' => $userId]);
    $allTimePoints = (int)$allTimePointsStmt->fetchColumn();

    // Keep users.points in sync with credited attendance days.
    $syncPointsStmt = $db->prepare('UPDATE users SET points = :points WHERE id = :user_id');
    $syncPointsStmt->execute([
        ':points' => $allTimePoints,
        ':user_id' => $userId
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'attendance_id' => $attendanceId,
        'member_id' => $userId,
        'member_ref' => $memberRef,
        'member_display_name' => $displayName,
        'source' => $source,
        'point_awarded' => $pointAwarded,
        'points' => $allTimePoints,
        'counts' => [
            'daily_events' => $dailyCount,
            'weekly_points' => $weeklyCount,
            'monthly_points' => $monthlyCount
        ]
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
