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
                NULLIF(TRIM(u.first_name || ' ' || u.last_name), ''),
                u.username,
                'Member #' || a.user_id
            ) AS display_name
        FROM attendance a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE DATE(a.datetime) = DATE('now', 'localtime')
        ORDER BY a.datetime DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weekRows = $pdo->query("
        SELECT strftime('%w', datetime) AS dow, COUNT(*) AS cnt
        FROM attendance
        WHERE DATE(datetime) >= DATE('now', 'localtime', '-6 days')
        GROUP BY strftime('%w', datetime)
    ")->fetchAll(PDO::FETCH_ASSOC);

    $weekly = array_fill(0, 7, 0);
    foreach ($weekRows as $row) {
        $dow = (int)$row['dow'];
        $idx = $dow === 0 ? 6 : $dow - 1;
        $weekly[$idx] = (int)$row['cnt'];
    }

    echo json_encode([
        'success' => true,
        'records' => $records,
        'weekly'  => $weekly,
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}