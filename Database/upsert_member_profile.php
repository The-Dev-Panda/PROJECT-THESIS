<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

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
    $sessionUserId = requireUserSession();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../Login/connection.php';
    $db = $pdo;
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $memberRef = (string)$sessionUserId;

    // Extract values
    $age = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
    $heightCm = isset($input['height_cm']) && $input['height_cm'] !== '' ? (float)$input['height_cm'] : null;
    $weightKg = isset($input['weight_kg']) && $input['weight_kg'] !== '' ? (float)$input['weight_kg'] : null;
    $bmi = isset($input['bmi']) && $input['bmi'] !== '' ? (float)$input['bmi'] : null;
    $fitnessLevel = isset($input['fitness_level']) ? trim((string)$input['fitness_level']) : null;
    $goal = isset($input['goal']) ? trim((string)$input['goal']) : null;
    $contact = isset($input['contact']) ? trim((string)$input['contact']) : null;
    $address = isset($input['address']) ? trim((string)$input['address']) : null;
    $gender = isset($input['gender']) ? trim((string)$input['gender']) : null;
    $remarks = isset($input['remarks']) ? trim((string)$input['remarks']) : null;
    
    // Extract new emergency contact values
    $eName = isset($input['e_name']) ? trim((string)$input['e_name']) : null;
    $eContact = isset($input['e_contact']) ? trim((string)$input['e_contact']) : null;

    // Nullify empty strings
    if ($contact === '') $contact = null;
    if ($address === '') $address = null;
    if ($gender === '') $gender = null;
    if ($remarks === '') $remarks = null;
    if ($eName === '') $eName = null;
    if ($eContact === '') $eContact = null;

    // Validation
    if ($age !== null && $age < 0) throw new Exception('Invalid age value');
    if ($heightCm !== null && $heightCm <= 0) throw new Exception('Invalid height value');
    if ($weightKg !== null && $weightKg <= 0) throw new Exception('Invalid weight value');
    if ($bmi !== null && $bmi <= 0) throw new Exception('Invalid BMI value');

    // Database Connection
    require_once __DIR__ . '/../Login/connection.php';
    $db = $pdo;
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);

    // Dynamically check columns to ensure backward/forward schema compatibility
    $profileColumns = [];
    $profileColumnStmt = $db->query('SHOW COLUMNS FROM member_profiles');
    if ($profileColumnStmt) {
        $profileColumnRows = $profileColumnStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($profileColumnRows as $profileColumnRow) {
            if (isset($profileColumnRow['Field'])) {
                $profileColumns[] = (string)$profileColumnRow['Field'];
            }
        }
    }
    
    $hasBmiColumn = in_array('bmi', $profileColumns, true);
    $hasContactColumn = in_array('contact', $profileColumns, true);
    $hasGenderColumn = in_array('gender', $profileColumns, true);
    $hasRemarksColumn = in_array('remarks', $profileColumns, true);
    $hasENameColumn = in_array('e_name', $profileColumns, true);
    $hasEContactColumn = in_array('e_contact', $profileColumns, true);

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

    // Update Address in Users Table
    $updateUserSql = 'UPDATE users SET address = :address WHERE id = :id';
    $updateUserStmt = $db->prepare($updateUserSql);
    $updateUserStmt->execute([
        ':address' => $address,
        ':id' => $userId
    ]);

    // Check if profile exists
    $existsStmt = $db->prepare('SELECT id FROM member_profiles WHERE user_id = :user_id LIMIT 1');
    $existsStmt->execute([':user_id' => $userId]);
    $exists = $existsStmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // --- UPDATE EXISTING PROFILE ---
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
        if ($hasRemarksColumn) {
            $updateFields[] = 'remarks = :remarks';
            $updateParams[':remarks'] = $remarks;
        }
        if ($hasENameColumn) {
            $updateFields[] = 'e_name = :e_name';
            $updateParams[':e_name'] = $eName;
        }
        if ($hasEContactColumn) {
            $updateFields[] = 'e_contact = :e_contact';
            $updateParams[':e_contact'] = $eContact;
        }

        $updateSql = 'UPDATE member_profiles SET ' . implode(', ', $updateFields) . ', updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id';
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($updateParams);
    } else {
        // --- INSERT NEW PROFILE ---
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
        if ($hasRemarksColumn) {
            $insertColumns[] = 'remarks';
            $insertValues[] = ':remarks';
            $insertParams[':remarks'] = $remarks;
        }
        if ($hasENameColumn) {
            $insertColumns[] = 'e_name';
            $insertValues[] = ':e_name';
            $insertParams[':e_name'] = $eName;
        }
        if ($hasEContactColumn) {
            $insertColumns[] = 'e_contact';
            $insertValues[] = ':e_contact';
            $insertParams[':e_contact'] = $eContact;
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