<?php
session_start();

header('Content-Type: application/json');

$allowedRoles = ['admin', 'staff'];
if (empty($_SESSION['username']) || empty($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);

// Support both JSON and form-encoded payloads.
if (!is_array($input)) {
    $input = $_POST;
}

// Fallback for urlencoded payloads that might not populate $_POST.
if (!is_array($input) || count($input) === 0) {
    $parsedRaw = [];
    parse_str((string)$inputRaw, $parsedRaw);
    if (is_array($parsedRaw) && count($parsedRaw) > 0) {
        $input = $parsedRaw;
    }
}

// Last fallback to query string for environments with strict body handling.
if (!is_array($input) || count($input) === 0) {
    $input = $_REQUEST;
}

$feedbackId = isset($input['id']) ? (int)$input['id'] : 0;
$newStatus = isset($input['status']) ? trim((string)$input['status']) : '';

if ($feedbackId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit();
}

$validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    include('../Login/connection.php');

    $checkStmt = $pdo->prepare('SELECT id, status, rowid AS feedback_rowid FROM feedback WHERE id = :id OR rowid = :id LIMIT 1');
    $checkStmt->execute(['id' => $feedbackId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Feedback not found']);
        exit();
    }

    if ((string)$existing['status'] === $newStatus) {
        echo json_encode(['success' => true, 'message' => 'Status is already up to date']);
        exit();
    }

    $stmt = $pdo->prepare('UPDATE feedback SET status = :status WHERE id = :id OR rowid = :id');
    $stmt->execute([
        'status' => $newStatus,
        'id' => $feedbackId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Feedback status updated successfully'
    ]);
} catch (PDOException $e) {
    error_log('Feedback status update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred while updating feedback status'
    ]);
}
?>
