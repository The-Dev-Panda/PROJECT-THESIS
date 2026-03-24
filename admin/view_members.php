<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
include("../Login/connection.php");

// Get filter and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$time_filter = isset($_GET['time']) ? $_GET['time'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Allowed sort columns
$allowed_sort = ['first_name', 'last_name', 'username', 'email', 'last_logged_in', 'is_verified', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Validate sort order
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Build WHERE clause
$where_conditions = ["user_type = 'user'"];
$params = [];

// Search filter
if ($search !== '') {
    $where_conditions[] = "(first_name LIKE :search OR last_name LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search%";
}

// Status filter
if ($status_filter === 'verified') {
    $where_conditions[] = "is_verified = 1";
} elseif ($status_filter === 'not_verified') {
    $where_conditions[] = "is_verified = 0";
}

// Time filter
if ($time_filter) {
    switch ($time_filter) {
        case 'today':
            $where_conditions[] = "DATE(created_at) = DATE('now')";
            break;
        case 'week':
            $where_conditions[] = "created_at >= DATE('now', '-7 days')";
            break;
        case 'month':
            $where_conditions[] = "created_at >= DATE('now', '-30 days')";
            break;
        case 'year':
            $where_conditions[] = "created_at >= DATE('now', '-365 days')";
            break;
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total records
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM users $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get members
$query = "SELECT id, username, first_name, last_name, email, last_logged_in, is_verified, created_at 
          FROM users 
          $where_clause
          ORDER BY $sort_by $sort_order
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
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

// Helper function to generate sort URL
function getSortUrl($column, $current_sort, $current_order, $search, $status, $time, $page)
{
    $new_order = 'ASC';
    if ($current_sort === $column && $current_order === 'ASC') {
        $new_order = 'DESC';
    }
    $params = "sort=$column&order=$new_order";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($status)
        $params .= "&status=$status";
    if ($time)
        $params .= "&time=$time";
    if ($page > 1)
        $params .= "&page=$page";
    return "?$params";
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order)
{
    if ($current_sort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="opacity: 0.3; font-size: 10px;"></i>';
    }
    return $current_order === 'ASC'
        ? '<i class="bi bi-arrow-up" style="color: var(--hazard); font-size: 10px;"></i>'
        : '<i class="bi bi-arrow-down" style="color: var(--hazard); font-size: 10px;"></i>';
}

// Build pagination URL
function getPaginationUrl($page_num, $search, $status, $time, $sort, $order)
{
    $params = "page=$page_num";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($status)
        $params .= "&status=$status";
    if ($time)
        $params .= "&time=$time";
    if ($sort !== 'created_at')
        $params .= "&sort=$sort";
    if ($order !== 'DESC')
        $params .= "&order=$order";
    return "?$params";
}

//stats bar
//new members today
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM users WHERE user_type = 'user'AND DATE(created_at) = DATE('now')");
$new_today = $stmt->fetch()['total'];
//new members this month
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM users WHERE user_type = 'user'AND strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')
");
$new_month = $stmt->fetch()['total'];
//active members (logged in last 7 days)
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM users WHERE user_type = 'user'AND last_logged_in >= DATE('now', '-7 days')
");
$active_members = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Members | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <style>
        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }

        .sortable-header:hover {
            color: var(--hazard);
        }
    </style>
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
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="stat-box">
                <div class="stat-icon members">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?php echo number_format(isset($total_records) ? $total_records : 0, 0); ?>
                    </div>
                    <div class="stat-label">Total Members</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon registrations">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format(isset($new_today) ? $new_today : 0, 0); ?>
                    </div>
                    <div class="stat-label">New Members Today</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon registrations">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format(isset($new_month) ? $new_month : 0, 0); ?>
                    </div>
                    <div class="stat-label">New Members This Month</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon registrations">
                    <i class="bi bi-person" style="color: var(--success);"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format(isset($active_members) ? $active_members : 0, 0); ?>
                    </div>
                    <div class="stat-label">Active Members (logged in last 7 days)</div>
                </div>
            </div>
        </div>


        <!-- Search & Filter Section -->
        <section>
            <div class="inventory-header">
                <form method="GET" class="search-container">
                    <div class="search-wrapper" style="flex: 2;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search members..."
                            value="<?php echo htmlspecialchars($search); ?>" style="min-width: 200px;">
                    </div>

                    <select name="status" class="search-input" style="min-width: 150px;">
                        <option value="">All Status</option>
                        <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified
                        </option>
                        <option value="not_verified" <?php echo $status_filter === 'not_verified' ? 'selected' : ''; ?>>
                            Not Verified</option>
                    </select>

                    <select name="time" class="search-input" style="min-width: 180px;">
                        <option value="">All Time</option>
                        <option value="today" <?php echo $time_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $time_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $time_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days
                        </option>
                        <option value="year" <?php echo $time_filter === 'year' ? 'selected' : ''; ?>>Last Year</option>
                    </select>

                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">

                    <button type="submit" class="search-btn">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </form>

                <?php if ($search || $status_filter || $time_filter): ?>
                    <a href="view_members.php" class="btn-secondary" style="padding: 11px 22px; text-decoration: none;">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Members Table -->
            <div style="margin-bottom: 16px;">
                <h2 style="margin: 0; border: none; padding: 0; font-size: 11px;">
                    <i class="bi bi-list-ul"></i> All Members
                    <span style="color: var(--text-muted); margin-left: 10px;">
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
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('first_name', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Name <?php echo getSortIcon('first_name', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('username', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Username <?php echo getSortIcon('username', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('email', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Email <?php echo getSortIcon('email', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('last_logged_in', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Last Login <?php echo getSortIcon('last_logged_in', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('is_verified', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Status <?php echo getSortIcon('is_verified', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('created_at', $sort_by, $sort_order, $search, $status_filter, $time_filter, $page); ?>'">
                                Joined <?php echo getSortIcon('created_at', $sort_by, $sort_order); ?>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $member): ?>
                                <?php
                                $full_name = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
                                $username = htmlspecialchars($member['username']);
                                $email = htmlspecialchars($member['email']);
                                $last_login = timeAgo($member['last_logged_in']);
                                $joined = date('M d, Y', strtotime($member['created_at']));
                                $status_badge = $member['is_verified']
                                    ? "<span class='status-badge active'>Verified</span>"
                                    : "<span class='status-badge maintenance'>Not Verified</span>";
                                ?>
                                <tr>
                                    <td>
                                        <i class='bi bi-person-circle' style='color: var(--hazard); margin-right: 8px;'></i>
                                        <strong><?php echo $full_name; ?></strong>
                                    </td>
                                    <td>@<?php echo $username; ?></td>
                                    <td><?php echo $email; ?></td>
                                    <td><?php echo $last_login; ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td><?php echo $joined; ?></td>
                                    <td>
                                        <a href='delete_member.php?id=<?php echo $member['id']; ?>' class='btn-icon'
                                            onclick='return confirm("Are you sure you want to remove this member?")'>
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='7' style='text-align: center; padding: 40px; color: var(--text-muted);'>
                                    <?php echo ($search || $status_filter || $time_filter) ? 'No members match your filters' : 'No members found'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo getPaginationUrl($page - 1, $search, $status_filter, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo getPaginationUrl($i, $search, $status_filter, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo getPaginationUrl($page + 1, $search, $status_filter, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($search || $status_filter || $time_filter || $sort_by !== 'created_at' || $sort_order !== 'DESC'): ?>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="view_members.php"
                        style="color: var(--text-muted); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset All Filters
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>

</body>

</html>