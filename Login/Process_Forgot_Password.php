<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    include("connection.php");

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && $user['email'] != '') {
        $email_code = random_int(100000, 999999);
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreplayfitstop@gmail.com';
            $mail->Password = 'qtuw htmw qvpy pmmt';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('noreplyfitstop@gmail.com', 'FITSTOP');
            $mail->addAddress($email);
            $mail->Subject = 'FITSTOP - Reset Password';
            $mail->Body = 'Your verification code is: '. $email_code . ' . This code will expire in 5 minutes.';
            $mail->send();
            session_start();
            $_SESSION['reset_password_email'] = $email;
            date_default_timezone_set('Asia/Manila');
            $date = new DateTime();
            $date->modify('+5 minutes');
            $expiration = $date->format('Y-m-d H:i:s');
            $update = $pdo->prepare('UPDATE users SET verification_code = :code, verification_code_expiration = :expiration WHERE email = :email');
            $update->execute([
                'code' => $email_code,
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