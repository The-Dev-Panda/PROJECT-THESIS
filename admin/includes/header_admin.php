<?php
$current_page = basename($_SERVER['PHP_SELF']);
date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/../../includes/security.php';
include('../login/connection.php');

$unread_stmt = $pdo->query("SELECT COUNT(*) as count FROM notification_history WHERE is_read = 0");
$unread_count = $unread_stmt->fetch()['count'];
?>

<head>
    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="icon" href="../images/Fitstop.png" type="image/x-icon" />
    <link href="../styles.css" rel="stylesheet">

    <!-- ADMIN CUSTOM CSS -->
    <link rel="stylesheet" href="admin_custom_styles.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body>

    <!-- CUSOTM CURSOR -->
    <div class="cursor" id="cursor"></div>
    <script>
        const cursor = document.getElementById('cursor');

        document.addEventListener('mousemove', e => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });

        const clickables = 'a, button, [onclick], input, select, textarea, label';

        document.addEventListener('mouseover', (e) => {
            if (e.target.closest(clickables)) {
                cursor.classList.add('hovered');
            }
        });

        document.addEventListener('mouseout', (e) => {
            if (e.target.closest(clickables)) {
                cursor.classList.remove('hovered');
            }
        });
    </script>

    <!-- ================= MOBILE TOPBAR ================= -->
    <div class="mobile-topbar">
        <button id="hamburgerBtn" class="hamburger-btn">
            <i class="bi bi-list"></i>
        </button>

        <div class="topbar-title">
            FITSTOP<span style="color:red;">-ADMIN</span>
        </div>

        <a href="notification.php" class="notif-bell-btn" id="notifBellBtnMobile">
            <i class="bi bi-bell-fill"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ================= DESKTOP NOTIFICATION (UNCHANGED) ================= -->
    <div style="position: fixed; bottom: 20px; left: calc(var(--sidebar-w) - 70px); z-index: 1001;">
        <div class="notif-wrapper">
            <button class="notif-bell-btn" id="notifBellBtn">
                <i class="bi bi-bell-fill"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notif-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>

            <!-- PANEL -->
            <div class="notif-panel" id="notifPanel"
                style="position: fixed; left: calc(var(--sidebar-w) + 10px); bottom: 20px; right: auto; top: auto; z-index: 99999;">
                <div class="notif-panel-header">
                    <h4>Notifications</h4>
                    <span><?php echo $unread_count; ?> unread</span>
                </div>
                <div class="notif-list" id="notifList">
                    <?php
                    // Get unread notifications
                    $notif_stmt = $pdo->query("SELECT * FROM notification_history WHERE is_read = 0 ORDER BY datetime DESC LIMIT 10");
                    $notifications = $notif_stmt->fetchAll();

                    if (count($notifications) > 0):
                        foreach ($notifications as $notif):
                            $time_ago = '';
                            if ($notif['datetime']) {
                                $time = strtotime($notif['datetime']);
                                $diff = time() - $time;
                                if ($diff < 60)
                                    $time_ago = 'Just now';
                                elseif ($diff < 3600)
                                    $time_ago = floor($diff / 60) . 'm ago';
                                elseif ($diff < 86400)
                                    $time_ago = floor($diff / 3600) . 'h ago';
                                else
                                    $time_ago = floor($diff / 86400) . 'd ago';
                            }

                            $icon_class = 'bi-exclamation-circle';
                            if ($notif['category'] == 'Accounts')
                                $icon_class = 'bi-person-fill';
                            if ($notif['category'] == 'Inventory')
                                $icon_class = 'bi-box-seam';
                            if ($notif['category'] == 'Staff')
                                $icon_class = 'bi-people-fill';
                            ?>
                            <a href="notification.php?id=<?php echo $notif['notif_id']; ?>" class="notif-item">
                                <div class="notif-icon">
                                    <i class="bi <?php echo $icon_class; ?>"></i>
                                </div>
                                <div class="notif-body">
                                    <p class="notif-msg">
                                        <strong><?php echo htmlspecialchars($notif['name']); ?></strong><br>
                                        <?php echo htmlspecialchars(substr($notif['description'], 0, 60)) . (strlen($notif['description']) > 60 ? '...' : ''); ?>
                                    </p>
                                    <div class="notif-time">
                                        <i class="bi bi-clock"></i> <?php echo $time_ago; ?>
                                    </div>
                                </div>
                            </a>
                            <?php
                        endforeach;
                    else:
                        ?>
                        <div class="notif-empty">
                            <i class="bi bi-check-circle"></i>
                            No new notifications
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (count($notifications) > 0): ?>
                    <div style="padding: 12px; border-top: 1px solid var(--border); text-align: center;">
                        <a href="notification.php"
                            style="color: var(--hazard); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif; font-weight: 700;">
                            View All Notifications →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================= SIDEBAR (UNCHANGED) ================= -->
    <div class="sidebar">
        <div class="sidebar-header">
            <span class="logo-text">FITSTOP<span style="color:red;">-ADMIN</span></span>
        </div>

        <ul class="menu">
            <li class="<?= $current_page === 'Admin_Landing_Page.php' ? 'active' : '' ?>"
                onclick="location.href='Admin_Landing_Page.php'"><i class="bi bi-graph-up"></i><span>Analytics</span>
            </li>
            <li class="<?= $current_page === 'create_announcement.php' ? 'active' : '' ?>"
                onclick="location.href='create_announcement.php'"><i
                    class="bi bi-megaphone"></i><span>Announcements</span></li>
            <li class="<?= $current_page === 'notification.php' ? 'active' : '' ?>"
                onclick="location.href='notification.php'"><i class="bi bi-bell"></i><span>Notifications</span></li>
            <li class="<?= $current_page === 'create_staff.php' ? 'active' : '' ?>"
                onclick="location.href='create_staff.php'"><i class="bi bi-person-plus"></i><span>Create Staff</span>
            </li>
            <li class="<?= $current_page === 'view_inventory.php' ? 'active' : '' ?>"
                onclick="location.href='view_inventory.php'"><i class="bi bi-box-seam"></i><span>Inventory</span></li>
            <li class="<?= $current_page === 'view_staff.php' ? 'active' : '' ?>"
                onclick="location.href='view_staff.php'"><i class="bi bi-people"></i><span>Staff</span></li>
            <li class="<?= $current_page === 'transaction.php' ? 'active' : '' ?>"
                onclick="location.href='transaction.php'"><i class="bi bi-bar-chart-line"></i><span>Transactions</span>
            </li>
            <li class="<?= $current_page === 'expenses.php' ? 'active' : '' ?>" onclick="location.href='expenses.php'">
                <i class="bi bi-currency-exchange"></i><span>Expenses</span>
            </li>
            <li class="<?= $current_page === 'view_members.php' ? 'active' : '' ?>"
                onclick="location.href='view_members.php'"><i class="bi bi-person-badge"></i><span>Members</span></li>
            <li class="<?= $current_page === 'membership_pricing.php' ? 'active' : '' ?>"
                onclick="location.href='membership_pricing.php'"><i class="bi bi-clipboard-check"></i><span>Membership
                    Pricing</span></li>
            <li class="<?= $current_page === 'exercises.php' ? 'active' : '' ?>"
                onclick="location.href='exercises.php'"><i class="bi bi-person-walking"></i><span>Exercises</span></li>
            <li class="<?= $current_page === 'view_feedback.php' ? 'active' : '' ?>"
                onclick="location.href='view_feedback.php'"><i class="bi bi-chat-dots"></i><span>Feedbacks</span></li>
            <li class="<?= $current_page === 'settings.php' ? 'active' : '' ?>" onclick="location.href='settings.php'">
                <i class="bi bi-gear"></i><span>Settings</span>
            </li>
            <li onclick="document.getElementById('logoutForm').submit()"><i
                    class="bi bi-box-arrow-right"></i><span>Logout</span></li>
        </ul>
    </div>

    <form id="logoutForm" action="../../login/logout.php" method="POST" style="display:none;">
        <?php echo fitstop_csrf_input(); ?>
    </form>

    <!-- ================= SCRIPT ================= -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bellBtn = document.getElementById('notifBellBtn');
            const panel = document.getElementById('notifPanel');
            const hamburger = document.getElementById('hamburgerBtn');
            const sidebar = document.querySelector('.sidebar');

            if (bellBtn && panel) {
                bellBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    panel.classList.toggle('open');
                });

                // Close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!panel.contains(e.target) && e.target !== bellBtn) {
                        panel.classList.remove('open');
                    }
                });

                // Prevent closing when clicking inside panel
                panel.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
            // 🍔 MOBILE SIDEBAR
            if (hamburger && sidebar) {
                hamburger.addEventListener('click', function () {
                    sidebar.classList.toggle('mobile-active');
                });
            }
        });
    </script>

    <!-- ================= MOBILE CSS ONLY ================= -->
    <style>
        /* hide mobile bar on desktop */
        .mobile-topbar {
            display: none;
        }

        /* ================= MOBILE ================= */
        @media (max-width: 768px) {

            .mobile-topbar {
                display: flex;
                position: fixed;
                top: 0;
                width: 100%;
                height: 50px;
                background: #111;
                align-items: center;
                justify-content: space-between;
                padding: 0 15px;
                z-index: 2000;
            }

            body {
                padding-top: 50px;
            }

            :root {
                --sidebar-w: 64px;
            }

            .logo-text,
            .menu li span {
                display: flex;
            }

            .hamburger-btn {
                background: none;
                border: none;
                color: white;
                font-size: 22px;
            }

            .topbar-title {
                font-family: 'Chakra Petch';
                font-weight: 700;
                color: var(--hazard);
                letter-spacing: 2px;
            }

            /* 🔥 FIXED SIDEBAR */
            .sidebar {
                position: fixed !important;
                top: 50px !important;
                left: -100% !important;
                width: 260px !important;
                height: calc(100% - 50px);
                transition: 0.3s ease;
                z-index: 1500;
            }

            .sidebar.mobile-active {
                left: 0 !important;
            }

            /* hide desktop notif on mobile */
            .notif-panel {
                display: none !important;
            }

            #notifBellBtn {
                display: none !important;
            }

        }
    </style>