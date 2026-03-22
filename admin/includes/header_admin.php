<?php
// Detect current page
$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../includes/security.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title></title>
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
                        <a class="nav-link <?php echo ($current_page == 'Admin_Landing_Page.php') ? 'active' : ''; ?>"
                            href="Admin_Landing_Page.php">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'create_announcement.php') ? 'active' : ''; ?>"
                            href="create_announcement.php">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'create_staff.php') ? 'active' : ''; ?>"
                            href="create_staff.php">
                            <i class="bi bi-person-plus"></i> Create Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'view_inventory.php') ? 'active' : ''; ?>"
                            href="view_inventory.php">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'view_staff.php') ? 'active' : ''; ?>"
                            href="view_staff.php">
                            <i class="bi bi-people"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'view_members.php') ? 'active' : ''; ?>"
                            href="view_members.php">
                            <i class="bi bi-person-badge"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'exercises.php') ? 'active' : ''; ?>"
                            href="exercises.php">
                            <i class="bi bi-bar-chart-line"></i> Exercises
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'view_feedback.php') ? 'active' : ''; ?>"
                            href="view_feedback.php">
                            <i class="bi bi-person-badge"></i> Feedbacks
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="../../login/logout.php" method="POST" class="d-inline">
                            <?php echo fitstop_csrf_input(); ?>
                            <button type="submit" class="nav-link border-0 bg-transparent" style="cursor: pointer;">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>

</html>