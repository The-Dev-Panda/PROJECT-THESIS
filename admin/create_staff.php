<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Staff | FITSTOP</title>
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
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-person-plus-fill"></i> Create Staff</h1>
                <p>Add new staff member to the system</p>
            </div>
        </div>
    <?php if (isset($_GET['success'])) {
        //DISPLAY SUCCESS MESSAGE
        echo '
         <div style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i> Staff member created successfully!
            </div>
        ';
    }
    if (isset($_GET['error'])) {
        //DISPLAY ERROR MESSAGE
        echo '
        <div style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i> ';
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
            </div></div>
        ';
    } ?>

    <section>
        <h2><i class="bi bi-person-plus"></i> Staff Information</h2>

        <div class="registration-card">
            <form action="process_create_staff.php" method="POST">
                <?php echo fitstop_csrf_input(); ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-input" maxlength="50" name="username" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-input" maxlength="50" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" class="form-input" maxlength="50" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" class="form-input" maxlength="50" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-input" maxlength="50" name="password" required minlength="8">
                        <small
                            style="color: var(--text-muted); font-size: 10.5px; margin-top: 5px; display: block;">Minimum
                            8 characters</small>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" class="form-input" maxlength="50" name="confirm_password" required minlength="8">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='Admin_Landing_Page.php'">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-person-plus-fill"></i> Create Staff
                    </button>
                </div>
            </form>
        </div>
    </section>
    </div>
    <?php //include('includes/footer_admin.php') ?>
</body>

</html>