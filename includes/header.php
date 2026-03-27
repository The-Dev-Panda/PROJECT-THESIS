<?php
/**
 * HEADER INCLUDE - Reusable Navigation Component
 * 
 * This file handles:
 * - Session initialization (if not already started)
 * - User authentication state detection
 * - Dynamic navigation based on login status
 * - Active page highlighting
 * 
 * Usage: include('includes/header.php');
 */

// Start session only if it hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security.php';

// Detect current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is logged in and get their info
$is_logged_in = !empty($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : null;
$user_type = $is_logged_in ? ($_SESSION['user_type'] ?? 'user') : null;

// Set base path if not already defined.
// Root pages use '', while known one-level subfolder pages use '../'.
if (!isset($base_path)) {
    $scriptDirName = basename(str_replace('\\', '/', dirname((string)($_SERVER['PHP_SELF'] ?? ''))));
    $oneLevelFolders = ['user', 'admin', 'staff', 'login', 'machines', 'database'];
    $base_path = in_array(strtolower($scriptDirName), $oneLevelFolders, true) ? '../' : '';
}

// Determine dashboard link based on user type
$dashboard_link = $base_path . 'Login/Login_Page.php'; // Default to login
if ($is_logged_in) {
    switch ($user_type) {
        case 'admin':
            $dashboard_link = $base_path . 'admin/Admin_Landing_Page.php';
            break;
        case 'staff':
            $dashboard_link = $base_path . 'staff/staff.php';
            break;
        case 'user':
            $dashboard_link = $base_path . 'user/user.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'FIT-STOP | Bakal Meets Tech'; ?></title>
    
    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Styles -->
    <link href="<?php echo $base_path; ?>styles.css" rel="stylesheet">
    
    <?php 
    // Allow pages to inject custom CSS
    if (isset($custom_css)) {
        echo $custom_css;
    }
    ?>
</head>
<body>

    <!-- NAVIGATION BAR -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand brand-font" href="<?php echo $base_path; ?>index.php">
                FIT-STOP
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown me-4">
                        <a class="nav-link dropdown-toggle text-white <?php echo (in_array($current_page, ['equipment.php', 'machine.php'])) ? 'text-hazard fw-bold' : ''; ?>" 
                           href="#" 
                           id="equipmentDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            EQUIPMENT
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="equipmentDropdown">
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>equipment.php">Zones</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_path; ?>machine.php">Machines</a></li>
                        </ul>
                    </li>
                    <li class="nav-item me-4">
                        <a class="nav-link text-white" href="<?php echo $base_path; ?>index.php#location">LOCATION</a>
                    </li>
                    <li class="nav-item me-4">
                        <a class="nav-link text-white <?php echo ($current_page === 'aboutus.php') ? 'text-hazard fw-bold' : ''; ?>" 
                           href="<?php echo $base_path; ?>aboutus.php">About Us</a>
                    </li>
                    
                    <?php if ($is_logged_in): ?>
                        <!-- LOGGED IN: Show welcome message and dashboard link -->
                        <li class="nav-item me-3">
                            <span class="nav-link text-light">
                                <i class="fa-solid fa-user-circle me-2"></i>
                                Hello, <strong class="text-hazard"><?php echo htmlspecialchars($username); ?></strong>!
                            </span>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="btn btn-hazard">
                                <i class="fa-solid fa-gauge me-2"></i> DASHBOARD
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- NOT LOGGED IN: Show login button -->
                        <li class="nav-item">
                            <a href="<?php echo $base_path; ?>Login/Login_Page.php" class="btn btn-hazard">
                                <i class="fa-solid fa-lock me-2"></i> LOGIN
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
