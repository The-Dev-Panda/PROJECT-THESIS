<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

try {
    $period = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'weekly';
    if ($period !== 'weekly' && $period !== 'monthly' && $period !== 'all_time') {
        $period = 'weekly';
    }

    include('../Login/connection.php');
    $db = $pdo;

    $limit = 10;

    if ($period === 'all_time') {
        $sql = "
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.username,
                COALESCE(u.points, 0) AS score
            FROM users u
            WHERE lower(coalesce(u.user_type, '')) = 'user'
            GROUP BY u.id, u.first_name, u.last_name, u.username, u.points
            ORDER BY score DESC, u.first_name ASC, u.last_name ASC
            LIMIT :limit
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $windowModifier = $period === 'monthly' ? '-29 days' : '-6 days';
        $sql = "
            SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.username,
                COUNT(DISTINCT date(a.datetime, 'localtime')) AS score
            FROM users u
            LEFT JOIN attendance a
              ON a.user_id = u.id
             AND datetime(a.datetime, 'localtime') >= datetime('now', 'localtime', :window)
            WHERE lower(coalesce(u.user_type, '')) = 'user'
            GROUP BY u.id, u.first_name, u.last_name, u.username
            ORDER BY score DESC, u.first_name ASC, u.last_name ASC
            LIMIT :limit
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':window', $windowModifier, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $rankings = [];
    $rank = 1;
    foreach ($rows as $row) {
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string)$row['username'];
        }

        $rankings[] = [
            'rank' => $rank,
            'user_id' => (int)$row['id'],
            'name' => $fullName,
            'username' => (string)$row['username'],
            'score' => (int)$row['score']
        ];
        $rank++;
    }

    echo json_encode([
        'success' => true,
        'period' => $period,
        'rankings' => $rankings,
        'top_three' => array_slice($rankings, 0, 3)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
