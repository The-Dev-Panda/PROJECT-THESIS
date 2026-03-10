<?php
session_start();

header('Content-Type: application/json');

function respondWithError(int $statusCode, string $message): void {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

function getFilteredFeedbackId(array $payload): int {
    if (!array_key_exists('id', $payload) || is_array($payload['id'])) {
        return 0;
    }

    $idRaw = trim((string)$payload['id']);
    if ($idRaw === '' || !preg_match('/^\d+$/', $idRaw)) {
        return 0;
    }

    $id = (int)$idRaw;
    return $id > 0 ? $id : 0;
}

function getFilteredStatus(array $payload): string {
    if (!array_key_exists('status', $payload) || is_array($payload['status'])) {
        return '';
    }

    $status = strtolower(trim((string)$payload['status']));
    $status = preg_replace('/[^a-z_]/', '', $status);
    return $status ?? '';
}

$allowedRoles = ['admin', 'staff'];
if (empty($_SESSION['username']) || empty($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowedRoles, true)) {
    respondWithError(403, 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError(405, 'Method not allowed');
}

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true);

if (!is_array($input)) {
    $input = $_POST;
}

if (!is_array($input) || count($input) === 0) {
    $parsedRaw = [];
    parse_str((string)$inputRaw, $parsedRaw);
    if (is_array($parsedRaw) && count($parsedRaw) > 0) {
        $input = $parsedRaw;
    }
}

if (!is_array($input) || count($input) === 0) {
    $input = $_REQUEST;
}

$feedbackId = getFilteredFeedbackId($input);
$newStatus = getFilteredStatus($input);

if ($feedbackId <= 0) {
    respondWithError(400, 'Invalid feedback ID');
}

$validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($newStatus, $validStatuses, true)) {
    respondWithError(400, 'Invalid status value');
}

try {
    include('../Login/connection.php');

    $checkStmt = $pdo->prepare('SELECT id, status, rowid AS feedback_rowid FROM feedback WHERE id = :id OR rowid = :id LIMIT 1');
    $checkStmt->execute(['id' => $feedbackId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        respondWithError(404, 'Feedback not found');
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
    respondWithError(500, 'Database error occurred while updating feedback status');
}
?>
