<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

function jsonError(string $message, int $httpStatus = 400): void {
    http_response_code($httpStatus);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function jsonSuccess(array $payload): void {
    echo json_encode(array_merge(['success' => true], $payload));
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    $fullName = isset($input['full_name']) ? trim((string)$input['full_name']) : '';
    $email = isset($input['email']) ? trim((string)$input['email']) : '';
    $password = isset($input['password']) ? (string)$input['password'] : '';
    $confirmPassword = isset($input['confirm_password']) ? (string)$input['confirm_password'] : '';

    $age = isset($input['age']) && $input['age'] !== '' ? (int)$input['age'] : null;
    $heightCm = isset($input['height_cm']) && $input['height_cm'] !== '' ? (float)$input['height_cm'] : null;
    $weightKg = isset($input['weight_kg']) && $input['weight_kg'] !== '' ? (float)$input['weight_kg'] : null;
    $fitnessLevel = isset($input['fitness_level']) ? trim((string)$input['fitness_level']) : null;
    $goal = isset($input['goal']) ? trim((string)$input['goal']) : null;
    $address = isset($input['address']) ? trim((string)$input['address']) : null;
    $contact = $address !== '' ? $address : (isset($input['contact']) ? trim((string)$input['contact']) : null);
    $gender = isset($input['gender']) ? trim((string)$input['gender']) : null;

    $dpaConsent = isset($input['dpa_consent']) ? (bool)$input['dpa_consent'] : false;

    if ($fullName === '') {
        throw new Exception('full_name is required');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('A valid email is required');
    }

    if ($password === '' || $confirmPassword === '') {
        throw new Exception('password and confirm_password are required');
    }

    if ($password !== $confirmPassword) {
        throw new Exception('Password and confirm password do not match');
    }

    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        throw new Exception('Password must include at least one letter and one number');
    }

    $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
    $firstName = $nameParts[0] ?? '';
    $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Member';

    if ($firstName === '' || $lastName === '') {
        throw new Exception('Both first and last name are required in full_name');
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
    if ($contact === '') {
        $contact = null;
    }
    if ($gender === '') {
        $gender = null;
    }

    $dbPath = __DIR__ . '/DB.sqlite';
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
    $db->exec('PRAGMA busy_timeout = 10000');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA synchronous = NORMAL');
    $db->exec('PRAGMA foreign_keys = ON');

    // duplicate check
    $emailCheckStmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $emailCheckStmt->execute([':email' => $email]);
    if ($emailCheckStmt->fetchColumn()) {
        throw new Exception('Email already exists');
    }

    // username generation
    $baseUsername = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $firstName . $lastName));
    if ($baseUsername === '') {
        $baseUsername = 'member';
    }

    $username = $baseUsername;
    $usernameCheckStmt = $db->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
    $suffix = 1;
    while (true) {
        $usernameCheckStmt->execute([':username' => $username]);
        if (!$usernameCheckStmt->fetchColumn()) {
            break;
        }
        $suffix++;
        $username = $baseUsername . $suffix;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $db->beginTransaction();

    $now = date('Y-m-d H:i:s');

    $insertUserStmt = $db->prepare('INSERT INTO users (username, first_name, last_name, email, password, user_type, is_verified, dpa_consent, dpa_consent_at, points, address, created_at, updated_at) VALUES (:username, :first_name, :last_name, :email, :password, :user_type, :is_verified, :dpa_consent, :dpa_consent_at, :points, :address, :created_at, :updated_at)');

    $dpaConsentInt = $dpaConsent ? 1 : 0;
    $dpaConsentAt = $dpaConsent ? $now : null;

    $insertUserStmt->execute([
        ':username' => $username,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':password' => $passwordHash,
        ':user_type' => 'user',
        ':is_verified' => 0,
        ':dpa_consent' => $dpaConsentInt,
        ':dpa_consent_at' => $dpaConsentAt,
        ':points' => 0,
        ':address' => $address,
        ':created_at' => $now,
        ':updated_at' => $now
    ]);

    $userId = (int) $db->lastInsertId();
    if ($userId <= 0) {
        throw new Exception('Could not create user');
    }

    $insertProfileStmt = $db->prepare('INSERT INTO member_profiles (user_id, age, height_cm, weight_kg, fitness_level, goal, contact, gender, created_at, updated_at) VALUES (:user_id, :age, :height_cm, :weight_kg, :fitness_level, :goal, :contact, :gender, :created_at, :updated_at)');
    $insertProfileStmt->execute([
        ':user_id' => $userId,
        ':age' => $age,
        ':height_cm' => $heightCm,
        ':weight_kg' => $weightKg,
        ':fitness_level' => $fitnessLevel,
        ':goal' => $goal,
        ':contact' => $contact,
        ':gender' => $gender,
        ':created_at' => $now,
        ':updated_at' => $now
    ]);

    // optional admin notification (safe db connection)
    $notificationStmt = $db->prepare('INSERT INTO notification_history (name, description, datetime, remarks, is_read, category) VALUES (:name, :description, :datetime, :remarks, :is_read, :category)');
    $notificationStmt->execute([
        ':name' => 'New Member',
        ':description' => "username: {$username}",
        ':datetime' => date('Y-m-d H:i:s'),
        ':remarks' => 'Created via API',
        ':is_read' => 0,
        ':category' => 'membership'
    ]);

    $db->commit();

    $memberIdDisplay = 'FS-' . date('Y') . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
    jsonSuccess([
        'user_id' => $userId,
        'username' => $username,
        'member_id_display' => $memberIdDisplay,
        'qr_payload' => json_encode(['member_ref' => (string)$userId, 'username' => $username])
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    jsonError($e->getMessage());
}

