<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
include("../Login/connection.php");

// PAGINATION LOGIC
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// GET MEMBER DATA
$total_query = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

$stmt = $pdo->prepare("SELECT id, username, first_name, last_name, email, last_logged_in, is_verified, created_at 
                       FROM users 
                       WHERE user_type = 'user' 
                       ORDER BY created_at DESC 
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll();

// AI - TIME FORMATTING FUNCTION
function timeAgo($datetime)
{
    if (!$datetime)
        return 'Never';

    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60)
        return 'Just now';
    if ($diff < 3600)
        return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400)
        return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800)
        return floor($diff / 86400) . ' days ago';

    return date('M d, Y', $time);
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Members</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../staff/staff.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body>
    <?php include('includes/header_admin.php') ?>
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-person-badge"></i> Members</h1>
                <p>Manage gym member accounts</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-people-fill"></i>
                    <span><?php echo $total_records; ?> Total Members</span>
                </div>
            </div>
        </div>
        <!-- Members Table Section -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin: 0; border: none; padding: 0;">
                    <i class="bi bi-list-ul"></i> All Members
                    <span style="color: var(--text-muted); font-size: 11px; margin-left: 10px;">
                        Showing
                        <?php echo min($offset + 1, $total_records); ?>-<?php echo min($offset + $records_per_page, $total_records); ?>
                        of <?php echo $total_records; ?>
                    </span>
                </h2>
            </div>

            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($members) > 0) {
                            foreach ($members as $member) {
                                $full_name = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
                                $username = htmlspecialchars($member['username']);
                                $email = htmlspecialchars($member['email']);
                                $last_login = timeAgo($member['last_logged_in']);
                                $joined = date('M d, Y', strtotime($member['created_at']));

                                $status_badge = $member['is_verified']
                                    ? "<span class='status-badge active'>Verified</span>"
                                    : "<span class='status-badge maintenance'>Not Verified</span>";

                                echo "
                                <tr>
                                    <td>
                                        <i class='bi bi-person-circle' style='color: var(--hazard); margin-right: 8px;'></i>
                                        <strong>$full_name</strong>
                                    </td>
                                    <td>@$username</td>
                                    <td>$email</td>
                                    <td>$last_login</td>
                                    <td>$status_badge</td>
                                    <td>$joined</td>
                                </tr>
                                ";
                            }
                        } else {
                            echo "
                            <tr>
                                <td colspan='7' style='text-align: center; padding: 40px; color: var(--text-muted);'>No members found</td>
                            </tr>
                            ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php //include('includes/footer_admin.php') ?>
</body>

</html>