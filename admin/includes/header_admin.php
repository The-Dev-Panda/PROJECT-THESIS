<?php
// Detect current page
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../includes/security.php';
?>

<head>

    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="icon" href="../images/Fitstop.png" type="image/x-icon"/>
    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<!-- Sidebar -->
<!-- Notification Bell -->
<div style="position: fixed; bottom: 20px; left: calc(var(--sidebar-w) - 70px); z-index: 1001;">
    <div class="notif-wrapper">
        <button class="notif-bell-btn" id="notifBellBtn">
            <i class="bi bi-bell-fill"></i>
            <?php
            // Get unread count
            include('../login/connection.php');
            $unread_stmt = $pdo->query("SELECT COUNT(*) as count FROM notification_history WHERE is_read = 0");
            $unread_count = $unread_stmt->fetch()['count'];
            if ($unread_count > 0):
            ?>
                <span class="notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </button>

        <!-- Notification Panel -->
        <div class="notif-panel" id="notifPanel" style="position: fixed; left: calc(var(--sidebar-w) + 10px); bottom: 20px; right: auto; top: auto; z-index: 99999;">
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
                            if ($diff < 60) $time_ago = 'Just now';
                            elseif ($diff < 3600) $time_ago = floor($diff / 60) . 'm ago';
                            elseif ($diff < 86400) $time_ago = floor($diff / 3600) . 'h ago';
                            else $time_ago = floor($diff / 86400) . 'd ago';
                        }

                        $icon_class = 'bi-exclamation-circle';
                        if ($notif['category'] == 'Accounts') $icon_class = 'bi-person-fill';
                        if ($notif['category'] == 'Inventory') $icon_class = 'bi-box-seam';
                        if ($notif['category'] == 'Staff') $icon_class = 'bi-people-fill';
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
                    <a href="notification.php" style="color: var(--hazard); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif; font-weight: 700;">
                        View All Notifications →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Headers -->
<div class="sidebar">
    <div class="sidebar-header">
        <!-- LOGO --><!--  <img src="../images/Fitstop.png" alt="FITSTOP" class="logo-img">  -->
        <span class="logo-text">FITSTOP<span style="color: red !important">-ADMIN</span></span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bellBtn = document.getElementById('notifBellBtn');
            const panel = document.getElementById('notifPanel');

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
        });
    </script>


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
        <li class="<?php echo ($current_page == 'membership.php') ? 'active' : ''; ?>"
            onclick="window.location.href='membership.php'">
            <i class="bi bi-clipboard-check"></i>
            <span>Membership</span>
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
<form id="logoutForm" action="../../login/logout.php" method="POST" style="display: none;">
    <?php echo fitstop_csrf_input(); ?>
    <button type="submit" class="nav-link border-0 bg-transparent" style="cursor: pointer;">
        <i class="bi bi-box-arrow-right"></i> Logout
    </button>
</form>