<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if($_SERVER["REQUEST_METHOD"]=="POST"){
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
    $new_password = $_POST['password'];
    include("connection.php");
    $stmt = $pdo->prepare('UPDATE users SET password = :new_password, verification_code = NULL, verification_code_expiration = NULL WHERE email = :email');
    $stmt->execute(['new_password' => password_hash($new_password, PASSWORD_DEFAULT), 'email' => $_SESSION['reset_password_email']]);
    unset($_SESSION['reset_password_email'], $_SESSION['reset_attempt_window_started'], $_SESSION['reset_verify_attempts']);
    $_SESSION = [];
    session_destroy();
    header('Location: Login_Page.php');
    exit();
};

?>