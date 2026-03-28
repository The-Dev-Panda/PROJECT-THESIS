<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
    $new_password = $_POST['password'];
    include("connection.php");

    // Fetch current password hash
    $stmt = $pdo->prepare('SELECT password FROM users WHERE email = :email');
    $stmt->execute(['email' => $_SESSION['reset_password_email']]);
    $user = $stmt->fetch();

    // Check if new password is the same as current
    if ($user && password_verify($new_password, $user['password'])) {
        header('Location: Change_Password.php?c=same');
        exit();
    }

    $stmt = $pdo->prepare('UPDATE users SET password = :new_password, verification_code = NULL, verification_code_expiration = NULL WHERE email = :email');
    $stmt->execute(['new_password' => password_hash($new_password, PASSWORD_DEFAULT), 'email' => $_SESSION['reset_password_email']]);

    unset($_SESSION['reset_password_email'], $_SESSION['reset_attempt_window_started'], $_SESSION['reset_verify_attempts']);
    $_SESSION = [];
    session_destroy();
    header('Location: Login_Page.php');
    exit();
}
?>