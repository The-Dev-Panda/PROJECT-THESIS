<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Handle mark as read/unread
if (isset($_POST['toggle_read'])) {
    $notif_id = $_POST['notif_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status == 1) ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE notification_history SET is_read = :status WHERE notif_id = :id");
    $stmt->execute(['status' => $new_status, 'id' => $notif_id]);
    
    header('Location: notification.php');
    exit();
}

// Get unread notifications
$stmt_unread = $pdo->query("SELECT * FROM notification_history WHERE is_read = 0 ORDER BY datetime DESC");
$unread_notifications = $stmt_unread->fetchAll();

// Get read notifications
$stmt_read = $pdo->query("SELECT * FROM notification_history WHERE is_read = 1 ORDER BY datetime DESC");
$read_notifications = $stmt_read->fetchAll();

function timeAgo($datetime) {
    if (!$datetime) return 'Unknown';
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y g:i A', $time);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Notifications | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        .notification-row {
            cursor: pointer;
            transition: all 0.2s;
        }
        .notification-row:hover {
            background: var(--bg-card-hover) !important;
        }
        .notification-details {
            display: none;
            background: var(--bg-surface);
            border-top: 1px solid var(--border);
        }
        .notification-details.expanded {
            display: table-row;
        }
        .category-badge {
            padding: 4px 11px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-family: 'Chakra Petch', sans-serif;
            letter-spacing: 0.5px;
            background: rgba(255, 204, 0, 0.1);
            color: var(--hazard);
            border: 1px solid rgba(255, 204, 0, 0.3);
        }
    </style>
</head>
<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-bell-fill"></i> Notifications</h1>
                <p>Manage system notifications and alerts</p>
            </div>
        </div>

        <!-- Unread Notifications -->
        <section>
            <h2><i class="bi bi-envelope-exclamation"></i> Unread Notifications (<?php echo count($unread_notifications); ?>)</h2>

            <?php if (count($unread_notifications) > 0): ?>
                <div class="inventory-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <tr class="notification-row" onclick="toggleRow(<?php echo $notif['notif_id']; ?>)" style="background: rgba(255, 204, 0, 0.05);">
                                    <td>
                                        <i class="bi bi-chevron-right" id="icon-<?php echo $notif['notif_id']; ?>" style="color: var(--hazard); transition: transform 0.2s;"></i>
                                    </td>
                                    <td>
                                        <strong style="color: var(--hazard);"><?php echo htmlspecialchars($notif['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo htmlspecialchars($notif['category'] ?? 'General'); ?></span>
                                    </td>
                                    <td><?php echo timeAgo($notif['datetime']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onclick="event.stopPropagation();">
                                            <input type="hidden" name="notif_id" value="<?php echo $notif['notif_id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $notif['is_read']; ?>">
                                            <button type="submit" name="toggle_read" class="btn-primary" style="padding: 7px 14px; font-size: 11px;">
                                                <i class="bi bi-check-circle"></i> Mark as Read
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="notification-details" id="details-<?php echo $notif['notif_id']; ?>">
                                    <td colspan="5" style="padding: 20px;">
                                        <div style="background: var(--bg-card); padding: 16px; border: 1px solid var(--border); border-radius: 2px;">
                                            <h4 style="font-family: 'Chakra Petch', sans-serif; font-size: 14px; color: var(--hazard); margin-bottom: 10px; text-transform: uppercase;">
                                                <i class="bi bi-info-circle"></i> Description
                                            </h4>
                                            <p style="color: var(--text-sub); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                                                <?php echo nl2br(htmlspecialchars($notif['description'])); ?>
                                            </p>
                                            <?php if (!empty($notif['remarks'])): ?>
                                                <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px;">
                                                    <strong style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px;">Remarks:</strong>
                                                    <p style="color: var(--text-sub); font-size: 12px; margin-top: 5px;">
                                                        <?php echo nl2br(htmlspecialchars($notif['remarks'])); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="registration-card" style="text-align: center; padding: 60px 20px;">
                    <i class="bi bi-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 16px; display: block;"></i>
                    <h3 style="font-family: 'Chakra Petch', sans-serif; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">All Caught Up!</h3>
                    <p style="color: var(--text-muted); font-size: 12px;">No unread notifications</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Read Notifications -->
        <section>
            <h2><i class="bi bi-envelope-check"></i> Read Notifications (<?php echo count($read_notifications); ?>)</h2>

            <?php if (count($read_notifications) > 0): ?>
                <div class="inventory-table">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($read_notifications as $notif): ?>
                                <tr class="notification-row" onclick="toggleRow(<?php echo $notif['notif_id']; ?>)" style="opacity: 0.7;">
                                    <td>
                                        <i class="bi bi-chevron-right" id="icon-<?php echo $notif['notif_id']; ?>" style="color: var(--text-muted); transition: transform 0.2s;"></i>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($notif['name']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="category-badge" style="opacity: 0.6;"><?php echo htmlspecialchars($notif['category'] ?? 'General'); ?></span>
                                    </td>
                                    <td><?php echo timeAgo($notif['datetime']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onclick="event.stopPropagation();">
                                            <input type="hidden" name="notif_id" value="<?php echo $notif['notif_id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $notif['is_read']; ?>">
                                            <button type="submit" name="toggle_read" class="btn-secondary" style="padding: 7px 14px; font-size: 11px;">
                                                <i class="bi bi-arrow-counterclockwise"></i> Mark Unread
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="notification-details" id="details-<?php echo $notif['notif_id']; ?>">
                                    <td colspan="5" style="padding: 20px;">
                                        <div style="background: var(--bg-card); padding: 16px; border: 1px solid var(--border); border-radius: 2px;">
                                            <h4 style="font-family: 'Chakra Petch', sans-serif; font-size: 14px; color: var(--text-muted); margin-bottom: 10px; text-transform: uppercase;">
                                                <i class="bi bi-info-circle"></i> Description
                                            </h4>
                                            <p style="color: var(--text-sub); font-size: 13px; line-height: 1.6; margin-bottom: 12px;">
                                                <?php echo nl2br(htmlspecialchars($notif['description'])); ?>
                                            </p>
                                            <?php if (!empty($notif['remarks'])): ?>
                                                <div style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 12px;">
                                                    <strong style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px;">Remarks:</strong>
                                                    <p style="color: var(--text-sub); font-size: 12px; margin-top: 5px;">
                                                        <?php echo nl2br(htmlspecialchars($notif['remarks'])); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="registration-card" style="text-align: center; padding: 60px 20px;">
                    <i class="bi bi-inbox" style="font-size: 48px; color: var(--text-muted); margin-bottom: 16px; display: block;"></i>
                    <h3 style="font-family: 'Chakra Petch', sans-serif; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">No Read Notifications</h3>
                    <p style="color: var(--text-muted); font-size: 12px;">Archive is empty</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function toggleRow(id) {
            const details = document.getElementById('details-' + id);
            const icon = document.getElementById('icon-' + id);
            
            if (details.classList.contains('expanded')) {
                details.classList.remove('expanded');
                icon.style.transform = 'rotate(0deg)';
            } else {
                details.classList.add('expanded');
                icon.style.transform = 'rotate(90deg)';
            }
        }
    </script>
</body>
</html>