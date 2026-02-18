<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_email = $_SESSION["reset_password_email"];
    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');

    include("connection.php");
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $session_email]);
    $user = $stmt->fetch();

    if (!empty($user['verification_code_expiration']) && $user['verification_code_expiration'] > $now) {
        if ($user['verification_code'] && $user['verification_code'] != $_POST['code']) {
            header('Location: Verify_Password_Change.php?c=2'); #invalid code
            exit();
        } else {
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