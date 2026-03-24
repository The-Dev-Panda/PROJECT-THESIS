<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
include("../Login/connection.php");

// Get filter and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Allowed sort columns
$allowed_sort = ['first_name', 'last_name', 'username', 'email', 'last_logged_in', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Validate sort order
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Build query
$where_clause = "WHERE user_type = 'staff'";
$params = [];

if ($search !== '') {
    $where_clause .= " AND (first_name LIKE :search OR last_name LIKE :search OR username LIKE :search OR email LIKE :search)";
    $params['search'] = "%$search%";
}

$query = "SELECT id, username, first_name, last_name, email, last_logged_in, created_at 
          FROM users 
          $where_clause
          ORDER BY $sort_by $sort_order";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$staff_members = $stmt->fetchAll();

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
function getSortUrl($column, $current_sort, $current_order, $search) {
    $new_order = 'ASC';
    if ($current_sort === $column && $current_order === 'ASC') {
        $new_order = 'DESC';
    }
    return "?sort=$column&order=$new_order" . ($search ? "&search=" . urlencode($search) : "");
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="opacity: 0.3; font-size: 10px;"></i>';
    }
    return $current_order === 'ASC' 
        ? '<i class="bi bi-arrow-up" style="color: var(--hazard); font-size: 10px;"></i>' 
        : '<i class="bi bi-arrow-down" style="color: var(--hazard); font-size: 10px;"></i>';
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Staff - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
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
                <h1><i class="bi bi-people"></i> Staff Members</h1>
                <p>Manage gym staff accounts</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-person-check"></i>
                    <span><?php echo count($staff_members); ?> Active</span>
                </div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <section>
            <div class="inventory-header">
                <form method="GET" class="search-container">
                    <div class="search-wrapper" style="flex: 1;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search staff by name, username, or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i> Search
                    </button>
                </form>
                
                <?php if ($search): ?>
                    <a href="view_staff.php" class="btn-secondary" style="padding: 11px 22px; text-decoration: none;">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                <?php endif; ?>
                
                <a href="create_staff.php" class="add-btn">
                    <i class="bi bi-person-plus"></i> Add New Staff
                </a>
            </div>

            <!-- Staff Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th class="sortable-header" onclick="window.location.href='<?php echo getSortUrl('first_name', $sort_by, $sort_order, $search); ?>'">
                                Name <?php echo getSortIcon('first_name', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header" onclick="window.location.href='<?php echo getSortUrl('username', $sort_by, $sort_order, $search); ?>'">
                                Username <?php echo getSortIcon('username', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header" onclick="window.location.href='<?php echo getSortUrl('email', $sort_by, $sort_order, $search); ?>'">
                                Email <?php echo getSortIcon('email', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header" onclick="window.location.href='<?php echo getSortUrl('last_logged_in', $sort_by, $sort_order, $search); ?>'">
                                Last Login <?php echo getSortIcon('last_logged_in', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header" onclick="window.location.href='<?php echo getSortUrl('created_at', $sort_by, $sort_order, $search); ?>'">
                                Joined <?php echo getSortIcon('created_at', $sort_by, $sort_order); ?>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($staff_members) > 0): ?>
                            <?php foreach ($staff_members as $staff): ?>
                                <?php
                                $full_name = htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']);
                                $username = htmlspecialchars($staff['username']);
                                $email = htmlspecialchars($staff['email']);
                                $last_login = timeAgo($staff['last_logged_in']);
                                $joined = date('M d, Y', strtotime($staff['created_at']));
                                ?>
                                <tr>
                                    <td>
                                        <i class='bi bi-person-circle' style='color: var(--hazard); margin-right: 8px;'></i>
                                        <strong><?php echo $full_name; ?></strong>
                                    </td>
                                    <td><?php echo $username; ?></td>
                                    <td><?php echo $email; ?></td>
                                    <td><?php echo $last_login; ?></td>
                                    <td><?php echo $joined; ?></td>
                                    <td>
                                        <a href='process_delete_staff.php?id=<?php echo $staff['id']; ?>' class='btn-icon' onclick='return confirm("Are you sure you want to remove this staff member?")'>
                                            <i class='bi bi-trash'></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='6' style='text-align: center; padding: 40px; color: var(--text-muted);'>
                                    <?php echo $search ? 'No staff members match your search' : 'No staff members found'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($search || $sort_by !== 'created_at' || $sort_order !== 'DESC'): ?>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="view_staff.php" style="color: var(--text-muted); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </div>
    
</body>

</html>