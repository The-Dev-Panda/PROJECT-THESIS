<?php
session_start();

if(empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin'){
    header('Location: Login_Page.php');
    exit();
}
?>

<?php
// CREATING STAFF PART
include("../login/connection.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = $_POST['username'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password !== $confirm_password){
        header('Location: create_staff.php?error=password_mismatch');
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $check_username = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $check_username->execute(['username' => $username]);
        if($check_username->fetchColumn() > 0){
            header('Location: create_staff.php?error=username_exists');
            exit();
        }
        
        $check_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check_email->execute(['email' => $email]);
        if($check_email->fetchColumn() > 0){
            header('Location: create_staff.php?error=email_exists');
            exit();
        }
        
        $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password, user_type, is_verified) 
                               VALUES (:username, :first_name, :last_name, :email, :password, 'staff', 1)");
        $stmt->execute([
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $hashed_password
        ]);
        
        header('Location: create_staff.php?success=created');
        exit();
        
    } catch(PDOException $e) {
        header('Location: create_staff.php?error=database');
        exit();
    }
    
} else {
    header('Location: create_staff.php');
    exit();
}
?>