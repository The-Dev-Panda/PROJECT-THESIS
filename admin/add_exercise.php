<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

$movement_types = ['push', 'pull', 'legs', 'cardio', 'other', 'arms', 'back', 'chest', 'core', 'shoulders'];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Add Exercise - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <?php include('includes/header_admin.php'); ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-plus-circle-fill"></i> Add Exercise</h1>
                <p>Create new exercise for workout logging</p>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (!empty($_GET['error'])): ?>
            <div
                style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Form Section -->
        <section>
            <h2><i class="bi bi-clipboard-plus"></i> Exercise Information</h2>

            <div class="registration-card">
                <form action="process_add_exercise.php" method="POST">
                    <?php echo fitstop_csrf_input(); ?>
                    <div class="row">
                        <div class="form-group" style="grid-column: span 3;">
                            <label>Exercise Name</label>
                            <input type="text" class="form-input" name="name" placeholder="Enter exercise name" required
                                maxlength="255">
                        </div>

                        <div class="form-group">
                            <label>Target Muscle</label>
                            <input type="text" class="form-input" name="target_muscle" placeholder="e.g., Chest, Biceps"
                                maxlength="100">
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Movement Type</label>
                            <select name="movement_type" class="form-input" required>
                                <option value="">Choose movement type</option>
                                <?php foreach ($movement_types as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <a href="exercises.php" class="btn-secondary"
                                style="text-decoration: none; display: inline-block;">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-plus-circle"></i> Create Exercise
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

</body>

</html>