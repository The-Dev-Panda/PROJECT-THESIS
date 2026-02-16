<?php
session_start();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $new_password = $_POST['password'];
    include("connection.php");
    $stmt = $pdo->prepare('UPDATE users SET password = :new_password WHERE email = :email');
    $stmt->execute(['new_password' => password_hash($new_password, PASSWORD_DEFAULT), 'email' => $_SESSION['reset_password_email']]);
    header('Location: Login_Page.php');
    session_destroy();
    exit();
};

?>