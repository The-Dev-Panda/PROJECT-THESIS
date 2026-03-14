<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Create New Staff</title>
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
                        <a class="nav-link active" href="create_staff.php">
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
                        <a class="nav-link" href="view_members.php">
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
    <?php if (isset($_GET['success'])) {
        //DISPLAY SUCCESS MESSAGE
        echo '
        <div class="container">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                Staff member created successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        ';
    }
    if (isset($_GET['error'])) {
        //DISPLAY ERROR MESSAGE
        echo '
        <div class="container">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>';
        if ($_GET['error'] == 'username_exists') {
            echo 'Username already exists!';
        }
        ;
        if ($_GET['error'] == 'email_exists') {
            echo 'Email already exists!';
        }
        ;
        if ($_GET['error'] == 'database') {
            echo 'Database error. Please try again.';
        }
        ;
        if ($_GET['error'] == 'password_mismatch') {
            echo 'Passwords do not match!';
        }
        ;
        echo '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        ';
    } ?>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                <h1 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Create New Staff</h1>
                    <form action="process_create_staff.php" method="POST">
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label fw-bold">
                                    <i class="bi bi-person me-2"></i>Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-bold">
                                    <i class="bi bi-envelope me-2"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label fw-bold">
                                    <i class="bi bi-person-badge me-2"></i>First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-bold">
                                    <i class="bi bi-person-badge me-2"></i>Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-bold">
                                    <i class="bi bi-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required
                                    minlength="6">
                                <small class="form-label">Minimum 6 characters</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label fw-bold">
                                    <i class="bi bi-lock-fill me-2"></i>Confirm Password
                                </label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required minlength="6">
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="admin_dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-plus-fill me-2"></i>Create Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>