<?php
session_start();

// Validate user is logged in
if (empty($_SESSION['username']) || empty($_SESSION['id'])) {
    header('Location: ../Login/Login_Page.php');
    exit();
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../index.html');
    exit();
}

// Get and validate inputs
$desc = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
$about = isset($_POST['machine']) ? trim($_POST['machine']) : '';

// Validate feedback description
if (empty($desc)) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=empty_feedback');
    exit();
}

if (strlen($desc) > 1000) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=feedback_too_long');
    exit();
}

// Validate about field
if (empty($about)) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=invalid_machine');
    exit();
}

if (strlen($about) > 255) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=machine_name_too_long');
    exit();
}

try {
    include('../Login/connection.php');
    
    // Get user information from session (secure - cannot be manipulated)
    $reporterID = $_SESSION['id'];
    
    // Get last_name from users table based on session ID
    $userStmt = $pdo->prepare("SELECT last_name FROM users WHERE id = :id");
    $userStmt->execute(['id' => $reporterID]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    $lastName = $user ? $user['last_name'] : null;
    
    // Insert feedback with current timestamp
    $stmt = $pdo->prepare("INSERT INTO feedback (about, reporterID, last_name, created_at, desc) VALUES (:about, :reporterID, :last_name, datetime('now'), :desc)");
    
    $stmt->execute([
        'about' => $about,
        'reporterID' => $reporterID,
        'last_name' => $lastName,
        'desc' => $desc
    ]);
    
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?success=feedback_submitted');
    exit();
    
} catch (PDOException $e) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?error=database');
    exit();
}
?>
