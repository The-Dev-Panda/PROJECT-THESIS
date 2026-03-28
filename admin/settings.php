<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Get admin info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'admin'");
$stmt->execute(['id' => $_SESSION['id']]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Settings - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-gear-fill"></i> Settings</h1>
                <p>Manage system settings and preferences</p>
            </div>
        </div>

        <!-- Settings Grid -->
        <section>
            <div class="row">
                <!-- Account Settings -->
                <a href="admin_profile.php" style="text-decoration: none;" class="col-sm-12 col-xl-6">
                    <div class="registration-card"
                        style="cursor: pointer; transition: all 0.2s; border-left: 3px solid var(--hazard);">
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                            <div
                                style="width: 60px; height: 60px; background: rgba(255, 204, 0, 0.1); border: 2px solid var(--hazard); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 30px; color: var(--hazard);"></i>
                            </div>
                            <div>
                                <h3
                                    style="font-family: 'Chakra Petch', sans-serif; font-size: 16px; margin: 0; color: var(--text-primary); text-transform: uppercase;">
                                    Account Settings</h3>
                                <p style="color: var(--text-muted); font-size: 11px; margin: 5px 0 0 0;">Manage your
                                    profile and account</p>
                            </div>
                        </div>
                        <p style="color: var(--text-sub); font-size: 12px; line-height: 1.6; margin-bottom: 12px;">
                            Update your personal information, change password, and manage your admin account settings.
                        </p>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border);">
                            <span
                                style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                                <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($admin['username']); ?>
                            </span>
                            <i class="bi bi-arrow-right" style="color: var(--hazard);"></i>
                        </div>
                    </div>
                </a>

                <!-- backup part (DEPRECATED) -->
                <!-- 
                <a href="backup.php" style="text-decoration: none;" class="col-sm-12 col-xl-3">
                    <div class="registration-card"
                        style="cursor: pointer; transition: all 0.2s; border-left: 3px solid var(--success);">
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                            <div
                                style="width: 60px; height: 60px; background: rgba(0, 255, 76, 0.1); border: 2px solid var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 30px; color: var(--success);"></i>
                            </div>
                            <div>
                                <h3
                                    style="font-family: 'Chakra Petch', sans-serif; font-size: 16px; margin: 0; color: var(--text-primary); text-transform: uppercase;">
                                    Backup Settings</h3>
                                <p style="color: var(--text-muted); font-size: 11px; margin: 5px 0 0 0;">Manually backup
                                    your database</p>
                            </div>
                        </div>
                        <p style="color: var(--text-sub); font-size: 12px; line-height: 1.6; margin-bottom: 12px;">
                            Create a manual backup of your database and send it to your email.
                        </p>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border);">
                            <span
                                style="color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                                <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($admin['username']); ?>
                            </span>
                            <i class="bi bi-arrow-right" style="color: var(--hazard);"></i>
                        </div>
                    </div>
                </a> 
                -->
            </div>
        </section>
    </div>
</body>

</html>