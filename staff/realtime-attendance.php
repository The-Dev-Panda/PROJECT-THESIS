<?php
session_start();
require_once '../login/connection.php';
header('Content-Type: application/json');

try {

    $records = $pdo->query("
        SELECT
            a.id,
            a.user_id,
            a.datetime,
            COALESCE(
                NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''),
                u.username,
                CONCAT('Member #', a.user_id)
            ) AS display_name
        FROM attendance a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE DATE(a.datetime) = CURDATE()
        ORDER BY a.datetime DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weekRows = $pdo->query("
        SELECT (DAYOFWEEK(datetime) + 5) % 7 AS dow, COUNT(*) AS cnt
        FROM attendance
        WHERE DATE(datetime) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY dow
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weekly = array_fill(0, 7, 0);
    foreach ($weekRows as $row) {
        $dow = (int)$row['dow'];
        $idx = $dow === 0 ? 6 : $dow - 1;
        $weekly[$idx] = (int)$row['cnt'];
    }

    $membersCheckedIn = (int)$pdo->query("
        SELECT COUNT(DISTINCT user_id)
        FROM attendance
        WHERE DATE(datetime) = CURDATE()
    ")->fetchColumn();

    $newRegistrations = (int)$pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE user_type = 'user'
        AND DATE(created_at) = CURDATE()
    ")->fetchColumn();

    $pendingNotifications = (int)$pdo->query("
        SELECT COUNT(*)
        FROM transactions
        WHERE status != 'completed'
    ")->fetchColumn();

    echo json_encode([
        'success' => true,
        'records' => $records,
        'weekly'  => $weekly,
        'stats'   => [
            'members_checked_in'    => $membersCheckedIn,
            'new_registrations'     => $newRegistrations,
            'equipment_issues'      => 0,
            'pending_notifications' => $pendingNotifications,
        ],
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}