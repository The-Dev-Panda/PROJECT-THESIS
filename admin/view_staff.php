<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
include("../Login/connection.php");
//GET STAFF DATA
$stmt = $pdo->query("SELECT id, username, first_name, last_name, email, last_logged_in, created_at 
                     FROM users 
                     WHERE user_type = 'staff' 
                     ORDER BY created_at DESC");
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
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Staff</title>
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

        <!-- Staff Table Section -->
        <section>
            <div class="inventory-header">
                <a href="create_staff.php" class="add-btn">
                    <i class="bi bi-person-plus"></i> Add New Staff
                </a>
            </div>

            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($staff_members) > 0) {
                            foreach ($staff_members as $staff) {
                                $full_name = htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']);
                                $username = htmlspecialchars($staff['username']);
                                $email = htmlspecialchars($staff['email']);
                                $last_login = timeAgo($staff['last_logged_in']);
                                $joined = date('M d, Y', strtotime($staff['created_at']));

                                echo "
                            <tr>
                                <td>
                                    <i class='bi bi-person-circle' style='color: var(--hazard); margin-right: 8px;'></i>
                                    <strong>$full_name</strong>
                                </td>
                                <td>$username</td>
                                <td>$email</td>
                                <td>$last_login</td>
                                <td>$joined</td>
                                <td>
                                <a href='process_delete_staff.php?id={$staff['id']}' class='btn-icon' onclick='return confirm(\"Are you sure you want to remove this staff member?\")'>
                                    <i class='bi bi-trash'></i>
                                </a>
                            </td>
                            </tr>
                            ";
                            }
                        } else {
                            echo "
                        <tr>
                            <td colspan='5' style='text-align: center; padding: 40px; color: var(--text-muted);'>No staff members found</td>
                        </tr>
                        ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <?php include('includes/footer_admin.php') ?>
</body>

</html>