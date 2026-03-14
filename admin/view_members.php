<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
include("../login/connection.php");

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

    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body class="bg-dark">
    <img src="../images/Fitstop.png" alt="FITSTOP LOGIN" class="img-fluid w-100 h-100"
        style="object-fit: cover; position: fixed; opacity: 10%; z-index: -1;">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand brand-front" href="../index.php">
                <i class="bi bi-lightning-fill"></i> FITSTOP - <span class="text-danger">
                    Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Admin_Landing_Page.php">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_announcement.php">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_staff.php">
                            <i class="bi bi-person-plus"></i> Create Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_inventory.php">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_staff.php">
                            <i class="bi bi-people"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_members.php">
                            <i class="bi bi-person-badge"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_feedback.php">
                            <i class="bi bi-chat-square-text"></i> Feedback
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="../Login/logout.php" method="POST" class="d-inline">
                            <button type="submit" class="nav-link border-0 bg-transparent" style="cursor: pointer;">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="members-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    Showing
                    <?php echo min($offset + 1, $total_records); ?>-
                    <?php echo min($offset + $records_per_page, $total_records); ?> of
                    <?php echo $total_records; ?> members
                </h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
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
                                    ? "<span class='badge bg-success'>Active</span>"
                                    : "<span class='badge bg-warning'>Not Verified</span>";

                                echo "
                                <tr>
                                    <td>
                                        <i class='bi bi-person-circle me-2'></i>
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
                                <td colspan='6' class='text-center text-muted py-4'>No members found</td>
                            </tr>
                            ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php
            if ($total_pages > 1) {
                echo "<nav aria-label='Member pagination'>";
                echo "<ul class='pagination justify-content-center mb-0'>";

                // Previous Button
                $prev_disabled = ($page <= 1) ? 'disabled' : '';
                $prev_page = $page - 1;
                echo "<li class='page-item $prev_disabled'>";
                echo "<a class='page-link' href='?page=$prev_page' tabindex='-1'>";
                echo "<i class='bi bi-chevron-left'></i> Previous";
                echo "</a></li>";

                // Page Numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                // First page
                if ($start_page > 1) {
                    echo "<li class='page-item'><a class='page-link' href='?page=1'>1</a></li>";
                    if ($start_page > 2) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                }

                // Page number buttons
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                }

                // Last page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                    }
                    echo "<li class='page-item'><a class='page-link' href='?page=$total_pages'>$total_pages</a></li>";
                }

                // Next Button
                $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
                $next_page = $page + 1;
                echo "<li class='page-item $next_disabled'>";
                echo "<a class='page-link' href='?page=$next_page'>";
                echo "Next <i class='bi bi-chevron-right'></i>";
                echo "</a></li>";

                echo "</ul></nav>";
            }
            ?>
        </div>
    </div>

</body>

</html>