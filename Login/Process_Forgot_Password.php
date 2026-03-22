<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../load_env.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
    $email = $_POST["email"];
    include("connection.php");

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && $user['email'] != '') {
        $email_code = random_int(100000, 999999);
        $mail = new PHPMailer(true);
        try {
            $smtpUsername = trim((string)($_ENV['SMTP_USERNAME'] ?? ''));
            $smtpPassword = trim((string)($_ENV['SMTP_PASSWORD'] ?? ''));
            if ($smtpUsername === '' || $smtpPassword === '') {
                throw new Exception('SMTP credentials are not configured.');
            }

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($smtpUsername, 'FITSTOP');
            $mail->addAddress($email);
            $mail->Subject = 'FITSTOP - Reset Password';
            $mail->Body = 'Your verification code is: ' . $email_code . '. This code will expire in 5 minutes.';
            $mail->send();
            $_SESSION['reset_password_email'] = $email;
            $_SESSION['reset_attempt_window_started'] = time();
            $_SESSION['reset_verify_attempts'] = 0;
            date_default_timezone_set('Asia/Manila');
            $date = new DateTime();
            $date->modify('+5 minutes');
            $expiration = $date->format('Y-m-d H:i:s');
            $hashedCode = password_hash((string)$email_code, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET verification_code = :code, verification_code_expiration = :expiration WHERE email = :email');
            $update->execute([
                'code' => $hashedCode,
                'expiration' => $expiration,
                'email' => $email
            ]);
            header('Location: Verify_Password_Change.php');
            exit();

        } catch (Exception $e) {
            echo "Failed to send email: {$mail->ErrorInfo}";
            header('location: Forgot_Password.php?c=500');
        }
    } else {
        header('Location: Verify_Password_Change.php');
        exit();
    }
} else {
    echo "Invalid request method.";
}
?>