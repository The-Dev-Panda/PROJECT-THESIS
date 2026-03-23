<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id']) || (int)$_GET['id'] <= 0) {
    header('Location: exercises.php?error=' . urlencode('Invalid exercise ID'));
    exit();
}

$exercise_id = (int)$_GET['id'];
include('../Login/connection.php');

$stmt = $pdo->prepare('SELECT * FROM exercises WHERE exercise_id = :id LIMIT 1');
$stmt->execute(['id' => $exercise_id]);
$exercise = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$exercise) {
    header('Location: exercises.php?error=' . urlencode('Exercise not found'));
    exit();
}

$movement_types = ['push', 'pull', 'legs', 'cardio', 'other', 'arms', 'back', 'chest', 'core', 'shoulders'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Exercise - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<?php include('includes/header_admin.php'); ?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1><i class="bi bi-pencil-square"></i> Edit Exercise</h1>
            <p>Update exercise information</p>
        </div>
    </div>

    <!-- Error Message -->
    <?php if (!empty($_GET['error'])): ?>
        <div style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Form Section -->
    <section>
        <h2><i class="bi bi-clipboard-data"></i> Exercise Details</h2>

        <div class="registration-card">
            <form action="process_edit_exercise.php" method="POST">
                <input type="hidden" name="exercise_id" value="<?php echo (int)$exercise['exercise_id']; ?>">

                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 3;">
                        <label>Exercise Name</label>
                        <input type="text" class="form-input" name="name" required maxlength="255" value="<?php echo htmlspecialchars($exercise['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Target Muscle</label>
                        <input type="text" class="form-input" name="target_muscle" maxlength="100" value="<?php echo htmlspecialchars($exercise['target_muscle']); ?>">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label>Movement Type</label>
                        <select name="movement_type" class="form-input" required>
                            <option value="">Choose movement type</option>
                            <?php foreach ($movement_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo ($exercise['movement_type'] === $type) ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="exercises.php" class="btn-secondary" style="text-decoration: none; display: inline-block;">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

</body>
</html>