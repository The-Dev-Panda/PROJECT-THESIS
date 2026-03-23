<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

// Helper function to send JSON response
function sendResponse($success, $message, $redirect = null) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit();
    } else {
        // Traditional redirect for non-AJAX requests
        $param = $success ? 'success' : 'error';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
        header("Location: $referer?$param=" . urlencode($message));
        exit();
    }
}

function getNextFeedbackId(PDO $pdo) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM feedback');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    $nextId = $row && isset($row['next_id']) ? (int)$row['next_id'] : 1;
    return $nextId > 0 ? $nextId : 1;
}

function isSameOriginRequest(): bool {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $currentScheme = $https ? 'https' : 'http';
    $currentHostHeader = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string)$_SERVER['HTTP_HOST'])) : '';

    if ($currentHostHeader === '') {
        return false;
    }

    $parsedCurrentHost = parse_url($currentScheme . '://' . $currentHostHeader, PHP_URL_HOST);
    $currentHost = $parsedCurrentHost !== false ? strtolower($parsedCurrentHost) : $currentHostHeader;
    $currentPort = isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : ($https ? 443 : 80);

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string)$_SERVER['HTTP_ORIGIN']) : '';
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $originScheme = parse_url($origin, PHP_URL_SCHEME);
        $originPort = parse_url($origin, PHP_URL_PORT);

        if ($originHost === false || $originScheme === false) {
            return false;
        }

        $originHost = strtolower($originHost);
        $originScheme = strtolower($originScheme);
        $originPort = $originPort !== null ? (int)$originPort : ($originScheme === 'https' ? 443 : 80);

        return $originScheme === $currentScheme && $originHost === $currentHost && $originPort === $currentPort;
    }

    $referer = isset($_SERVER['HTTP_REFERER']) ? trim((string)$_SERVER['HTTP_REFERER']) : '';
    if ($referer === '') {
        return true;
    }

    $refererHost = parse_url($referer, PHP_URL_HOST);
    $refererScheme = parse_url($referer, PHP_URL_SCHEME);
    $refererPort = parse_url($referer, PHP_URL_PORT);

    if ($refererHost === false || $refererScheme === false) {
        return false;
    }

    $refererHost = strtolower($refererHost);
    $refererScheme = strtolower($refererScheme);
    $refererPort = $refererPort !== null ? (int)$refererPort : ($refererScheme === 'https' ? 443 : 80);

    return $refererScheme === $currentScheme && $refererHost === $currentHost && $refererPort === $currentPort;
}

// Check if user is logged in (member vs guest)
$sessionUsername = isset($_SESSION['username']) ? trim((string)$_SESSION['username']) : '';
$sessionUserId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    sendResponse(false, 'Invalid request method');
}

fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);

if (!isSameOriginRequest()) {
    sendResponse(false, 'Invalid request origin');
}

// Get and validate inputs
$desc = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
$about = isset($_POST['machine']) ? trim($_POST['machine']) : '';
$guestName = isset($_POST['guest_name']) ? trim($_POST['guest_name']) : '';

// Validate feedback description
if (empty($desc)) {
    sendResponse(false, 'Feedback cannot be empty');
}

if (strlen($desc) < 10) {
    sendResponse(false, 'Feedback must be at least 10 characters long');
}

if (strlen($desc) > 1000) {
    sendResponse(false, 'Feedback cannot exceed 1000 characters');
}

// Validate about field
if (empty($about)) {
    sendResponse(false, 'Machine name is required');
}

if (strlen($about) > 255) {
    sendResponse(false, 'Machine name is too long');
}

try {
    include('../Login/connection.php');

    $reporterID = $sessionUserId;

    // Backward-compatibility: resolve ID for older sessions that only stored username.
    if ($reporterID <= 0 && !empty($sessionUsername)) {
        $idLookupStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $idLookupStmt->execute(['username' => $sessionUsername]);
        $resolvedUser = $idLookupStmt->fetch(PDO::FETCH_ASSOC);
        if ($resolvedUser && !empty($resolvedUser['id'])) {
            $reporterID = (int)$resolvedUser['id'];
            $_SESSION['id'] = $reporterID;
        }
    }

    if ($reporterID <= 0) {
        // GUEST SUBMISSION (reporterID can be NULL)
        $windowSeconds = 15 * 60;
        $maxGuestSubmissions = 3;
        $nowTs = time();
        $guestHistory = isset($_SESSION['guest_feedback_times']) && is_array($_SESSION['guest_feedback_times'])
            ? $_SESSION['guest_feedback_times']
            : [];

        $guestHistory = array_values(array_filter($guestHistory, function ($ts) use ($nowTs, $windowSeconds) {
            return is_numeric($ts) && ((int)$ts >= ($nowTs - $windowSeconds));
        }));

        if (count($guestHistory) >= $maxGuestSubmissions) {
            sendResponse(false, 'Too many guest submissions. Please wait a few minutes');
        }

        $feedbackId = getNextFeedbackId($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO feedback (id, about, reporterID, last_name, created_at, desc, status)
            VALUES (:id, :about, NULL, :last_name, datetime('now'), :desc, 'pending')
        ");

        $stmt->execute([
            'id' => $feedbackId,
            'about' => $about,
            'last_name' => $guestName !== '' ? $guestName : 'Anonymous Guest',
            'desc' => $desc
        ]);

        $guestHistory[] = $nowTs;
        $_SESSION['guest_feedback_times'] = $guestHistory;
    } else {
        // MEMBER SUBMISSION
        $rateLimitStmt = $pdo->prepare("
            SELECT COUNT(*) as recent_count
            FROM feedback
            WHERE reporterID = :reporterID
            AND created_at > datetime('now', '-5 minutes')
        ");
        $rateLimitStmt->execute(['reporterID' => $reporterID]);
        $rateLimitResult = $rateLimitStmt->fetch(PDO::FETCH_ASSOC);

        if ($rateLimitResult['recent_count'] >= 3) {
            sendResponse(false, 'Please wait a few minutes before submitting more feedback');
        }

        $userStmt = $pdo->prepare("SELECT last_name FROM users WHERE id = :id");
        $userStmt->execute(['id' => $reporterID]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        $lastName = $user ? $user['last_name'] : null;

        $feedbackId = getNextFeedbackId($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO feedback (id, about, reporterID, last_name, created_at, desc, status)
            VALUES (:id, :about, :reporterID, :last_name, datetime('now'), :desc, 'pending')
        ");

        $stmt->execute([
            'id' => $feedbackId,
            'about' => $about,
            'reporterID' => $reporterID,
            'last_name' => $lastName,
            'desc' => $desc
        ]);
    }
    sendResponse(true, 'Thank you! Your feedback has been submitted successfully');
    
} catch (PDOException $e) {
    // Log error for debugging (don't expose to user)
    error_log("Feedback submission error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while submitting your feedback. Please try again');
}
?>
