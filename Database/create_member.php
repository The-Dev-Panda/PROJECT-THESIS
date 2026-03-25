<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/security.php';

try {
    // Require a logged-in staff or admin to create members
    $callerType = strtolower((string)($_SESSION['user_type'] ?? ''));
    if (empty($_SESSION['id']) || !in_array($callerType, ['staff', 'admin'], true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden. Staff or admin login required.']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new Exception('Invalid request payload');
    }

    fitstop_validate_csrf_or_exit($input['csrf_token'] ?? null);

    $fullName = isset($input['full_name']) ? trim((string) $input['full_name']) : '';
    $email = isset($input['email']) ? trim((string) $input['email']) : '';
    $age = isset($input['age']) && $input['age'] !== '' ? (int) $input['age'] : null;
    $heightCm = isset($input['height_cm']) && $input['height_cm'] !== '' ? (float) $input['height_cm'] : null;
    $weightKg = isset($input['weight_kg']) && $input['weight_kg'] !== '' ? (float) $input['weight_kg'] : null;
    $fitnessLevel = isset($input['fitness_level']) ? trim((string) $input['fitness_level']) : null;
    $goal = isset($input['goal']) ? trim((string) $input['goal']) : null;
    $contact = isset($input['contact']) ? trim((string) $input['contact']) : null;
    $gender = isset($input['gender']) ? trim((string) $input['gender']) : null;
    $password = isset($input['password']) ? (string) $input['password'] : '';
    $confirmPassword = isset($input['confirm_password']) ? (string) $input['confirm_password'] : '';

    if ($contact === '') {
        $contact = null;
    }
    if ($gender === '') {
        $gender = null;
    }

    if ($fullName === '' || $email === '') {
        throw new Exception('Full name and email are required');
    }

    if ($password === '' || $confirmPassword === '') {
        throw new Exception('Password and confirm password are required');
    }

    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        throw new Exception('Password must include at least one letter and one number');
    }

    if ($password !== $confirmPassword) {
        throw new Exception('Password and confirm password do not match');
    }

    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? '';
    $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Member';

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

    // Check for duplicate email
    $emailCheckStmt = $db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
    $emailCheckStmt->execute([':email' => $email]);
    if ($emailCheckStmt->fetchColumn()) {
        throw new Exception('A user with this email already exists');
    }

    $baseUsername = strtolower(preg_replace('/[^A-Za-z0-9]+/', '', $firstName . $lastName));
    if ($baseUsername === '') {
        $baseUsername = 'member';
    }
    $username = $baseUsername;
    $suffix = 1;
    $checkStmt = $db->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
    while (true) {
        $checkStmt->execute([':username' => $username]);
        if (!$checkStmt->fetchColumn()) {
            break;
        }
        $suffix++;
        $username = $baseUsername . $suffix;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $db->beginTransaction();

    $insertUserStmt = $db->prepare(
        'INSERT INTO users (username, first_name, last_name, email, password, user_type, is_verified, dpa_consent, dpa_consent_at, points)
         VALUES (:username, :first_name, :last_name, :email, :password, :user_type, :is_verified, :dpa_consent, :dpa_consent_at, :points)'
    );
    $insertUserStmt->execute([
        ':username'      => $username,
        ':first_name'    => $firstName,
        ':last_name'     => $lastName,
        ':email'         => $email,
        ':password'      => $passwordHash,
        ':user_type'     => 'user',
        ':is_verified'   => 1,
        ':dpa_consent'   => 0,
        ':dpa_consent_at' => null,
        ':points'        => 0
    ]);

    $userId = (int) $db->lastInsertId();

    $insertProfileStmt = $db->prepare(
        'INSERT INTO member_profiles (user_id, age, height_cm, weight_kg, fitness_level, goal, contact, gender)
         VALUES (:user_id, :age, :height_cm, :weight_kg, :fitness_level, :goal, :contact, :gender)'
    );
    $insertProfileStmt->execute([
        ':user_id'       => $userId,
        ':age'           => $age,
        ':height_cm'     => $heightCm,
        ':weight_kg'     => $weightKg,
        ':fitness_level' => $fitnessLevel,
        ':goal'          => $goal,
        ':contact'       => $contact,
        ':gender'        => $gender
    ]);

    $db->commit();

    $memberIdDisplay = 'FS-' . date('Y') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

    echo json_encode([
        'success'          => true,
        'user_id'          => $userId,
        'username'         => $username,
        'member_id_display' => $memberIdDisplay,
        'qr_payload'       => json_encode([
            'member_ref' => (string) $userId,
            'username'   => $username
        ])
    ]);
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
