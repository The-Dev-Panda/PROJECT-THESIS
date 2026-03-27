<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = isset($input['member_ref']) ? trim((string)$input['member_ref']) : '';
    $source    = isset($input['source'])     ? trim((string)$input['source'])     : '';

    if ($memberRef === '') throw new Exception('Member reference is required');
    if ($source !== 'qr' && $source !== 'manual') throw new Exception('Invalid attendance source');

    include('../Login/connection.php');
    $db = $pdo;

    // ✅ PHP date — respects Asia/Manila timezone
    $now   = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $minus59min   = date('Y-m-d H:i:s', strtotime('-59 minutes'));
    $minus6days   = date('Y-m-d', strtotime('-6 days'));
    $minus29days  = date('Y-m-d', strtotime('-29 days'));

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
    if (!$user) throw new Exception('Member not found');
    if (strtolower((string)$user['user_type']) !== 'user') throw new Exception('Selected account is not a member user');

    $userId      = (int)$user['id'];
    $displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    if ($displayName === '') $displayName = (string)$user['username'];

    $db->beginTransaction();

    // ✅ Check last hour using PHP datetime strings
    $hourlyCheckStmt = $db->prepare("
        SELECT datetime FROM attendance
        WHERE user_id = :user_id AND datetime >= :minus59
        ORDER BY datetime DESC LIMIT 1
    ");
    $hourlyCheckStmt->execute([':user_id' => $userId, ':minus59' => $minus59min]);
    $recentAttendance = $hourlyCheckStmt->fetchColumn();
    if ($recentAttendance !== false) {
        throw new Exception('Attendance already recorded within the last hour');
    }

    // ✅ Check if point already credited today using PHP $today
    $pointCheckStmt = $db->prepare("
        SELECT 1 FROM attendance
        WHERE user_id = :user_id AND DATE(datetime) = :today LIMIT 1
    ");
    $pointCheckStmt->execute([':user_id' => $userId, ':today' => $today]);
    $alreadyCreditedToday = (bool)$pointCheckStmt->fetchColumn();

    try {
        // ✅ Insert Manila time from PHP, not CURRENT_TIMESTAMP
        $insertStmt = $db->prepare('INSERT INTO attendance (user_id, datetime) VALUES (:user_id, :now)');
        $insertStmt->execute([':user_id' => $userId, ':now' => $now]);
        $attendanceId = (int)$db->lastInsertId();
        $pointAwarded = !$alreadyCreditedToday;
    } catch (PDOException $insertEx) {
        if (strpos($insertEx->getMessage(), 'UNIQUE constraint failed') !== false) {
            $db->rollBack();
            throw new Exception('Attendance already recorded for today');
        }
        throw $insertEx;
    }

    // ✅ All stats use PHP date strings instead of SQLite 'localtime'
    $dailyStmt = $db->prepare("
        SELECT COUNT(*) AS total FROM attendance
        WHERE user_id = :user_id AND DATE(datetime) = :today
    ");
    $dailyStmt->execute([':user_id' => $userId, ':today' => $today]);
    $dailyCount = (int)$dailyStmt->fetchColumn();

    $weeklyStmt = $db->prepare("
        SELECT COUNT(DISTINCT DATE(datetime)) AS total FROM attendance
        WHERE user_id = :user_id AND DATE(datetime) >= :minus6
    ");
    $weeklyStmt->execute([':user_id' => $userId, ':minus6' => $minus6days]);
    $weeklyCount = (int)$weeklyStmt->fetchColumn();

    $monthlyStmt = $db->prepare("
        SELECT COUNT(DISTINCT DATE(datetime)) AS total FROM attendance
        WHERE user_id = :user_id AND DATE(datetime) >= :minus29
    ");
    $monthlyStmt->execute([':user_id' => $userId, ':minus29' => $minus29days]);
    $monthlyCount = (int)$monthlyStmt->fetchColumn();

    $allTimePointsStmt = $db->prepare("
        SELECT COUNT(DISTINCT DATE(datetime)) AS total FROM attendance
        WHERE user_id = :user_id
    ");
    $allTimePointsStmt->execute([':user_id' => $userId]);
    $allTimePoints = (int)$allTimePointsStmt->fetchColumn();

    $syncPointsStmt = $db->prepare('UPDATE users SET points = :points WHERE id = :user_id');
    $syncPointsStmt->execute([':points' => $allTimePoints, ':user_id' => $userId]);

    $db->commit();

    echo json_encode([
        'success'             => true,
        'attendance_id'       => $attendanceId,
        'member_id'           => $userId,
        'member_ref'          => $memberRef,
        'member_display_name' => $displayName,
        'source'              => $source,
        'point_awarded'       => $pointAwarded,
        'points'              => $allTimePoints,
        'counts'              => [
            'daily_events'   => $dailyCount,
            'weekly_points'  => $weeklyCount,
            'monthly_points' => $monthlyCount,
        ],
    ]);

} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}