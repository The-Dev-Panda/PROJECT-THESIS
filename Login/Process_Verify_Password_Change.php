<?php
session_start();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $session_email = $_SESSION["reset_password_email"];
    include("connection.php");

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $session_email]);
    $user = $stmt->fetch();
    if($user && password_verify($_POST['code'], $user['verification_code'])){
        header('Location: Change_Password.php');
        exit();
    } else {
        header('Location: Verify_Password_Change.php?c=false');
        exit();

    }
};
?>