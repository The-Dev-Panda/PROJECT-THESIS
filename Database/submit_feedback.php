<?php
session_start();

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

// Check if user is logged in (member vs guest)
$isGuest = empty($_SESSION['username']) || empty($_SESSION['id']);

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    sendResponse(false, 'Invalid request method');
}

// Get and validate inputs
$desc = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
$about = isset($_POST['machine']) ? trim($_POST['machine']) : '';
$guestName = $isGuest && isset($_POST['guest_name']) ? trim($_POST['guest_name']) : null;

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
    
    if ($isGuest) {
        // GUEST SUBMISSION
        // Get IP for basic rate limiting
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Rate limit: 3 submissions per 15 minutes per IP (stricter for guests)
        $rateLimitStmt = $pdo->prepare("
            SELECT COUNT(*) as recent_count 
            FROM feedback 
            WHERE reporterID IS NULL 
            AND created_at > datetime('now', '-15 minutes')
        ");
        $rateLimitStmt->execute();
        $rateLimitResult = $rateLimitStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rateLimitResult['recent_count'] >= 3) {
            sendResponse(false, 'Too many submissions. Please wait a few minutes');
        }
        
        // Insert with NULL reporterID and guest name as last_name
        $stmt = $pdo->prepare("
            INSERT INTO feedback (about, reporterID, last_name, created_at, desc, status) 
            VALUES (:about, NULL, :guest_name, datetime('now'), :desc, 'pending')
        ");
        
        $stmt->execute([
            'about' => $about,
            'guest_name' => $guestName ?: 'Anonymous Guest',
            'desc' => $desc
        ]);
        
    } else {
        // MEMBER SUBMISSION
        $reporterID = $_SESSION['id'];
        
        // RATE LIMITING: Check if user submitted feedback recently
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
        
        // Get last_name from users table based on session ID
        $userStmt = $pdo->prepare("SELECT last_name FROM users WHERE id = :id");
        $userStmt->execute(['id' => $reporterID]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        $lastName = $user ? $user['last_name'] : null;
        
        // Insert feedback with current timestamp and default status
        $stmt = $pdo->prepare("
            INSERT INTO feedback (about, reporterID, last_name, created_at, desc, status) 
            VALUES (:about, :reporterID, :last_name, datetime('now'), :desc, 'pending')
        ");
        
        $stmt->execute([
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
