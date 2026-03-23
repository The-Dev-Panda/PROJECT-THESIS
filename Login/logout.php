<?php
session_start();
//require_once __DIR__ . '/../includes/security.php';
if($_SERVER["REQUEST_METHOD"] == "POST"){
    //fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
    $_SESSION = array();
    session_destroy();
    header("Location: Login_Page.php");
    exit();
}
?>