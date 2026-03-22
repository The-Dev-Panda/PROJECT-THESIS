<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
    $session_email = $_SESSION["reset_password_email"];
    $submittedCode = trim((string)($_POST['code'] ?? ''));

    if (!preg_match('/^\d{6}$/', $submittedCode)) {
        header('Location: Verify_Password_Change.php?c=2');
        exit();
    }

    $attemptWindowSeconds = 600;
    $maxAttempts = 5;
    $windowStart = isset($_SESSION['reset_attempt_window_started']) ? (int)$_SESSION['reset_attempt_window_started'] : 0;
    $attempts = isset($_SESSION['reset_verify_attempts']) ? (int)$_SESSION['reset_verify_attempts'] : 0;

    if ($windowStart <= 0 || (time() - $windowStart) > $attemptWindowSeconds) {
        $windowStart = time();
        $attempts = 0;
    }

    if ($attempts >= $maxAttempts) {
        $_SESSION['reset_attempt_window_started'] = $windowStart;
        $_SESSION['reset_verify_attempts'] = $attempts;
        header('Location: Verify_Password_Change.php?c=3');
        exit();
    }

    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');

    include("connection.php");
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $session_email]);
    $user = $stmt->fetch();

    if (!empty($user['verification_code_expiration']) && $user['verification_code_expiration'] > $now) {
        if (!empty($user['verification_code']) && !password_verify($submittedCode, (string)$user['verification_code'])) {
            $_SESSION['reset_attempt_window_started'] = $windowStart;
            $_SESSION['reset_verify_attempts'] = $attempts + 1;
            header('Location: Verify_Password_Change.php?c=2'); #invalid code
            exit();
        } else {
            $_SESSION['reset_verify_attempts'] = 0;
            header('Location: Change_Password.php'); #valid code & valid expiration
            exit();
        }
    } else {
        $clear_verification_code = $pdo->prepare('UPDATE users SET verification_code = NULL, verification_code_expiration = NULL WHERE email = :email');
        $clear_verification_code->execute(['email' => $session_email]);
        header('Location: Forgot_Password.php?c=1'); #expired code
        exit();
    }
}
;
?>