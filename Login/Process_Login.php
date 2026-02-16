<?php

echo "REQUEST METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "POST DATA: ";
print_r($_POST);
echo "<br><br>";

if (isset($_POST["username"])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    include("connection.php");
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['user_type'] = $user['user_type'];
        header('Location: success.php');
        exit();
    } else {
        header('Location: Login_Page.php?c=false');
        exit();
    }
}
