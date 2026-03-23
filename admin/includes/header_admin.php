<?php
// Detect current page
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../includes/security.php';
?>

<head>

<link rel="stylesheet" href="../staff/staff.css">

<link href="../styles.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <!-- LOGO --><!--  <img src="../images/Fitstop.png" alt="FITSTOP" class="logo-img">  -->
        <span class="logo-text">FITSTOP<span style="color: red !important">-ADMIN</span></span>
    </div>

    <ul class="menu">
        <li class="<?php echo ($current_page == 'Admin_Landing_Page.php') ? 'active' : ''; ?>"
            onclick="window.location.href='Admin_Landing_Page.php'">
            <i class="bi bi-graph-up"></i>
            <span>Analytics</span>
        </li>
        <li class="<?php echo ($current_page == 'create_announcement.php') ? 'active' : ''; ?>"
            onclick="window.location.href='create_announcement.php'">
            <i class="bi bi-megaphone"></i>
            <span>Announcements</span>
        </li>
        <li class="<?php echo ($current_page == 'notification.php') ? 'active' : ''; ?>"
            onclick="window.location.href='notification.php'">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
        </li>
        <li class="<?php echo ($current_page == 'create_staff.php') ? 'active' : ''; ?>"
            onclick="window.location.href='create_staff.php'">
            <i class="bi bi-person-plus"></i>
            <span>Create Staff</span>
        </li>
        <li class="<?php echo ($current_page == 'view_inventory.php') ? 'active' : ''; ?>"
            onclick="window.location.href='view_inventory.php'">
            <i class="bi bi-box-seam"></i>
            <span>Inventory</span>
        </li>
        <li class="<?php echo ($current_page == 'view_staff.php') ? 'active' : ''; ?>"
            onclick="window.location.href='view_staff.php'">
            <i class="bi bi-people"></i>
            <span>Staff</span>
        </li>
        <li class="<?php echo ($current_page == 'transaction.php') ? 'active' : ''; ?>"
            onclick="window.location.href='transaction.php'">
            <i class="bi bi-bar-chart-line"></i>
            <span>Transactions</span>
        </li>
        <li class="<?php echo ($current_page == 'view_members.php') ? 'active' : ''; ?>"
            onclick="window.location.href='view_members.php'">
            <i class="bi bi-person-badge"></i>
            <span>Members</span>
        </li>
        <li class="<?php echo ($current_page == 'exercises.php') ? 'active' : ''; ?>"
            onclick="window.location.href='exercises.php'">
            <i class="bi bi-bar-chart-line"></i>
            <span>Exercises</span>
        </li>
        <li class="<?php echo ($current_page == 'view_feedback.php') ? 'active' : ''; ?>"
            onclick="window.location.href='view_feedback.php'">
            <i class="bi bi-chat-dots"></i>
            <span>Feedbacks</span>
        </li>
        <li onclick="document.getElementById('logoutForm').submit()" style="cursor: pointer;">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </li>
    </ul>
</div>

<!-- Hidden logout form -->
<form id="logoutForm" action="../../login/logout.php" method="POST" style="display: none;"></form>