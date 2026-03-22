<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');
$dbPath = __DIR__ . '/DB.sqlite';

function requireUserSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['id']) || empty($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'user') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please login as member.']);
        exit();
    }

    return (int)$_SESSION['id'];
}

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $sessionUserId = requireUserSession();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = (string)$sessionUserId;

    $age = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
    $heightCm = isset($input['height_cm']) && $input['height_cm'] !== '' ? (float)$input['height_cm'] : null;
    $weightKg = isset($input['weight_kg']) && $input['weight_kg'] !== '' ? (float)$input['weight_kg'] : null;
    $bmi = isset($input['bmi']) && $input['bmi'] !== '' ? (float)$input['bmi'] : null;
    $fitnessLevel = isset($input['fitness_level']) ? trim((string)$input['fitness_level']) : null;
    $goal = isset($input['goal']) ? trim((string)$input['goal']) : null;
    $contact = isset($input['contact']) ? trim((string)$input['contact']) : null;
    $gender = isset($input['gender']) ? trim((string)$input['gender']) : null;

    if ($contact === '') {
        $contact = null;
    }
    if ($gender === '') {
        $gender = null;
    }

    if ($age !== null && $age < 0) {
        throw new Exception('Invalid age value');
    }
    if ($heightCm !== null && $heightCm <= 0) {
        throw new Exception('Invalid height value');
    }
    if ($weightKg !== null && $weightKg <= 0) {
        throw new Exception('Invalid weight value');
    }
    if ($bmi !== null && $bmi <= 0) {
        throw new Exception('Invalid BMI value');
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
    $hasContactColumn = in_array('contact', $profileColumns, true);
    $hasGenderColumn = in_array('gender', $profileColumns, true);

    $userStmt = $db->prepare('SELECT id, user_type FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => (int)$memberRef]);

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new Exception('Member not found');
    }
    if (strtolower((string)$user['user_type']) !== 'user') {
        throw new Exception('Selected account is not a member user');
    }

    $userId = (int)$user['id'];

    $existsStmt = $db->prepare('SELECT id FROM member_profiles WHERE user_id = :user_id LIMIT 1');
    $existsStmt->execute([':user_id' => $userId]);
    $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $updateFields = [
            'age = :age',
            'height_cm = :height_cm',
            'weight_kg = :weight_kg',
            'fitness_level = :fitness_level',
            'goal = :goal'
        ];

        $updateParams = [
            ':age' => $age,
            ':height_cm' => $heightCm,
            ':weight_kg' => $weightKg,
            ':fitness_level' => $fitnessLevel,
            ':goal' => $goal,
            ':user_id' => $userId
        ];

        if ($hasBmiColumn) {
            $updateFields[] = 'bmi = :bmi';
            $updateParams[':bmi'] = $bmi;
        }
        if ($hasContactColumn) {
            $updateFields[] = 'contact = :contact';
            $updateParams[':contact'] = $contact;
        }
        if ($hasGenderColumn) {
            $updateFields[] = 'gender = :gender';
            $updateParams[':gender'] = $gender;
        }

        $updateSql = 'UPDATE member_profiles SET ' . implode(', ', $updateFields) . ', updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id';
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($updateParams);
    } else {
        $insertColumns = ['user_id', 'age', 'height_cm', 'weight_kg', 'fitness_level', 'goal'];
        $insertValues = [':user_id', ':age', ':height_cm', ':weight_kg', ':fitness_level', ':goal'];
        $insertParams = [
            ':user_id' => $userId,
            ':age' => $age,
            ':height_cm' => $heightCm,
            ':weight_kg' => $weightKg,
            ':fitness_level' => $fitnessLevel,
            ':goal' => $goal
        ];

        if ($hasBmiColumn) {
            $insertColumns[] = 'bmi';
            $insertValues[] = ':bmi';
            $insertParams[':bmi'] = $bmi;
        }
        if ($hasContactColumn) {
            $insertColumns[] = 'contact';
            $insertValues[] = ':contact';
            $insertParams[':contact'] = $contact;
        }
        if ($hasGenderColumn) {
            $insertColumns[] = 'gender';
            $insertValues[] = ':gender';
            $insertParams[':gender'] = $gender;
        }

        $insertSql = 'INSERT INTO member_profiles (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute($insertParams);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Member profile saved',
        'user_id' => $userId
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
