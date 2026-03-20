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

function isSameOriginRequest(): bool {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $currentScheme = $https ? 'https' : 'http';
    $currentHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';

    if ($currentHost === '') {
        return false;
    }

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';
    if ($origin !== '') {
        $originParts = parse_url($origin);
        if (!is_array($originParts) || empty($originParts['host'])) {
            return false;
        }

        $originScheme = isset($originParts['scheme']) ? strtolower((string)$originParts['scheme']) : '';
        $originHost = strtolower((string)$originParts['host']);
        $originPort = isset($originParts['port']) ? (int)$originParts['port'] : null;
        $currentPort = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : null;
        $samePort = $originPort === null || $currentPort === null || $originPort === $currentPort;

        return $originScheme === $currentScheme && $originHost === strtolower($currentHost) && $samePort;
    }

    $referer = isset($_SERVER['HTTP_REFERER']) ? trim((string)$_SERVER['HTTP_REFERER']) : '';
    if ($referer === '') {
        // Allow non-browser clients and strict privacy modes to proceed if the session is valid.
        return true;
    }

    $refererParts = parse_url($referer);
    if (!is_array($refererParts) || empty($refererParts['host'])) {
        return false;
    }

    $refererScheme = isset($refererParts['scheme']) ? strtolower((string)$refererParts['scheme']) : '';
    $refererHost = strtolower((string)$refererParts['host']);
    $refererPort = isset($refererParts['port']) ? (int)$refererParts['port'] : null;
    $currentPort = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : null;
    $samePort = $refererPort === null || $currentPort === null || $refererPort === $currentPort;

    return $refererScheme === $currentScheme && $refererHost === strtolower($currentHost) && $samePort;
}

$allowedRoles = ['admin', 'staff'];
if (empty($_SESSION['username']) || empty($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowedRoles, true)) {
    respondWithError(403, 'Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondWithError(405, 'Method not allowed');
}

if (!isSameOriginRequest()) {
    respondWithError(403, 'Invalid request origin');
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
