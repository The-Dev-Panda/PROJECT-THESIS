<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');
$dbPath = __DIR__ . '/DB.sqlite';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id']) || empty($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login as member.']);
    exit();
}

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $sessionUserId = (int)$_SESSION['id'];

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
    $db->exec('PRAGMA busy_timeout = 10000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('PRAGMA foreign_keys = ON');

    $userStmt = $db->prepare('SELECT id, username, first_name, last_name, email, user_type, created_at FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $sessionUserId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('Member not found');
    }

    // Fetch only schema-defined columns from member_profiles (no bmi column in schema)
    $profileStmt = $db->prepare(
        'SELECT age, height_cm, weight_kg, fitness_level, goal, contact, gender, created_at, updated_at
         FROM member_profiles WHERE user_id = :user_id LIMIT 1'
    );
    $profileStmt->execute([':user_id' => (int)$user['id']]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    $profileAge = null;
    $profileHeightCm = null;
    $profileWeightKg = null;
    $profileBmi = null;

    if ($profile) {
        $profileAge = isset($profile['age']) ? (int)$profile['age'] : null;
        $profileHeightCm = isset($profile['height_cm']) ? (float)$profile['height_cm'] : null;
        $profileWeightKg = isset($profile['weight_kg']) ? (float)$profile['weight_kg'] : null;

        // BMI is always computed dynamically from height and weight; never stored in DB
        if ($profileHeightCm !== null && $profileHeightCm > 0 && $profileWeightKg !== null && $profileWeightKg > 0) {
            $heightMeters = $profileHeightCm / 100;
            $profileBmi = round($profileWeightKg / ($heightMeters * $heightMeters), 1);
        }
    }

    $memberIdDisplay = 'FS-' . date('Y') . '-' . str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT);
    $qrPayload = [
        'member_ref' => (string)$user['id'],
        'username'   => (string)$user['username']
    ];

    echo json_encode([
        'success' => true,
        'user' => [
            'id'               => (int)$user['id'],
            'username'         => (string)$user['username'],
            'first_name'       => (string)$user['first_name'],
            'last_name'        => (string)$user['last_name'],
            'full_name'        => trim(((string)$user['first_name']) . ' ' . ((string)$user['last_name'])),
            'email'            => (string)$user['email'],
            'user_type'        => (string)$user['user_type'],
            'member_id_display' => $memberIdDisplay,
            'qr_payload'       => json_encode($qrPayload)
        ],
        'profile' => $profile ? [
            'age'          => $profileAge,
            'height_cm'    => $profileHeightCm,
            'weight_kg'    => $profileWeightKg,
            'bmi'          => $profileBmi,
            'contact'      => $profile['contact'] ?? null,
            'gender'       => $profile['gender'] ?? null,
            'fitness_level' => $profile['fitness_level'] ?? null,
            'goal'         => $profile['goal'] ?? null,
            'created_at'   => $profile['created_at'] ?? null,
            'updated_at'   => $profile['updated_at'] ?? null
        ] : null
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
