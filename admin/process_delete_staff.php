<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $get_staff = $pdo->prepare("SELECT first_name, last_name, username FROM users WHERE id = :id AND user_type = 'staff'");
    $get_staff->execute(['id' => $id]);
    $staff = $get_staff->fetch();
    $staff_name = $staff['first_name'] . ' ' . $staff['last_name'];
    $staff_username = $staff['username'];
    //STAFF USERNAME PLACEHOLDER


    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND user_type = 'staff'");
    $stmt->execute(['id' => $id]);
    header('Location: view_staff.php?success=deleted');
    exit();
}

header('Location: view_staff.php');
exit();
?>