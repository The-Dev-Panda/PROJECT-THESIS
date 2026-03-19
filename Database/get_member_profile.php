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

    $memberRef = '';
    if (isset($_GET['member_ref'])) {
        $memberRef = trim((string)$_GET['member_ref']);
    }

    if ($memberRef === '' && isset($_SESSION['id'])) {
        $memberRef = (string)$_SESSION['id'];
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
    $db->exec('PRAGMA busy_timeout = 10000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('PRAGMA foreign_keys = ON');

    $profileColumns = [];
    $profileColumnStmt = $db->query('PRAGMA table_info(member_profiles)');
    if ($profileColumnStmt) {
        $profileColumnRows = $profileColumnStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($profileColumnRows as $profileColumnRow) {
            if (isset($profileColumnRow['name'])) {
                $profileColumns[] = (string)$profileColumnRow['name'];
            }
        }
    }
    $hasBmiColumn = in_array('bmi', $profileColumns, true);

    if ($memberRef === '') {
        $fallbackStmt = $db->prepare("SELECT id FROM users WHERE lower(coalesce(user_type, '')) = 'user' ORDER BY id ASC LIMIT 1");
        $fallbackStmt->execute();
        $fallback = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
        if (!$fallback) {
            throw new Exception('No member user found');
        }
        $memberRef = (string)$fallback['id'];
    }

    if (ctype_digit($memberRef)) {
        $userStmt = $db->prepare('SELECT id, username, first_name, last_name, email, user_type, created_at FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => (int)$memberRef]);
    } else {
        $userStmt = $db->prepare('SELECT id, username, first_name, last_name, email, user_type, created_at FROM users WHERE username = :username LIMIT 1');
        $userStmt->execute([':username' => $memberRef]);
    }

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('Member not found');
    }

    $profileSelectSql = $hasBmiColumn
        ? 'SELECT age, height_cm, weight_kg, fitness_level, goal, bmi, created_at, updated_at FROM member_profiles WHERE user_id = :user_id LIMIT 1'
        : 'SELECT age, height_cm, weight_kg, fitness_level, goal, created_at, updated_at FROM member_profiles WHERE user_id = :user_id LIMIT 1';
    $profileStmt = $db->prepare($profileSelectSql);
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

        if ($hasBmiColumn && isset($profile['bmi']) && $profile['bmi'] !== null && $profile['bmi'] !== '') {
            $profileBmi = (float)$profile['bmi'];
        } elseif ($profileHeightCm !== null && $profileHeightCm > 0 && $profileWeightKg !== null && $profileWeightKg > 0) {
            $heightMeters = $profileHeightCm / 100;
            $profileBmi = round($profileWeightKg / ($heightMeters * $heightMeters), 1);
        }
    }

    $memberIdDisplay = 'FS-' . date('Y') . '-' . str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT);
    $qrPayload = [
        'member_ref' => (string)$user['id'],
        'username' => (string)$user['username']
    ];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'first_name' => (string)$user['first_name'],
            'last_name' => (string)$user['last_name'],
            'full_name' => trim(((string)$user['first_name']) . ' ' . ((string)$user['last_name'])),
            'email' => (string)$user['email'],
            'user_type' => (string)$user['user_type'],
            'member_id_display' => $memberIdDisplay,
            'qr_payload' => json_encode($qrPayload)
        ],
        'profile' => $profile ? [
            'age' => $profileAge,
            'height_cm' => $profileHeightCm,
            'weight_kg' => $profileWeightKg,
            'bmi' => $profileBmi,
            'fitness_level' => $profile['fitness_level'],
            'goal' => $profile['goal'],
            'created_at' => $profile['created_at'],
            'updated_at' => $profile['updated_at']
        ] : null
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
