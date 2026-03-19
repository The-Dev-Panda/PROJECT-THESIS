<?php

// Process login form submission.
// Note: this file must not output debug data to the browser.
if (isset($_POST["username"])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');
    include("connection.php");
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['id'] = (int)$user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        try {
        $update_login = $pdo->prepare('UPDATE users SET last_logged_in = :last_logged_in WHERE username = :username');
        $update_login->execute(['username' => $username, 'last_logged_in' => $now]);
        } catch (PDOException $e) {
            // Avoid leaking DB errors to the user.
            error_log('Login update error: ' . $e->getMessage());
        }
        header('Location: success.php');
        exit();
    } else {
        header('Location: Login_Page.php?c=false');
        exit();
    }
}
