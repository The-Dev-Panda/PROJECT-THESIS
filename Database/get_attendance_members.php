<?php
header('Content-Type: application/json');

try {
    include('../Login/connection.php');
    $db = $pdo;

    $stmt = $db->query("SELECT id, username, first_name, last_name FROM users WHERE lower(coalesce(user_type, '')) = 'user' ORDER BY first_name ASC, last_name ASC, username ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $members = [];
    foreach ($rows as $row) {
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string)$row['username'];
        }

        $members[] = [
            'member_ref' => (string)$row['id'],
            'username' => (string)$row['username'],
            'display_name' => $fullName . ' (#' . $row['id'] . ')'
        ];
    }

    echo json_encode([
        'success' => true,
        'members' => $members
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
