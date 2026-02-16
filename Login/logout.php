<?php
session_start();
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $_SESSION = array();
    session_destroy();
    header("Location: Login_Page.php");
    exit();
}
?>