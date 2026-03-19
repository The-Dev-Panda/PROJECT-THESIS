<?php
session_start();

if (empty($_SESSION['username'])) {
    session_destroy();
    header('Location: Login_Page.php');
    exit();
}

$userType = strtolower((string)($_SESSION['user_type'] ?? ''));
$memberId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if ($userType === 'admin') {
    header('Location: ../admin/Admin_Landing_Page.php');
    exit();
}

if ($userType === 'user') {
    $target = '../user/user.html';
    if ($memberId > 0) {
        $target .= '?member_ref=' . urlencode((string)$memberId);
    }
    header('Location: ' . $target);
    exit();
}

if ($userType === 'staff') {
    header('Location: ../staff/staff.php');
    exit();
}

session_destroy();
header('Location: Login_Page.php');
exit();
?>