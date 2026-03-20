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

$movement_types = ['strength', 'cardio', 'hypertrophy', 'flexibility', 'mobility', 'other'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Exercise</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-dark">
<?php include('includes/header_admin.php'); ?>
<div class="container py-5">
    <div class="page-header mb-4 text-white">
        <h1>Edit Exercise</h1>
        <p class="text-muted">Update fields for the selected exercise.</p>
    </div>

    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <form action="process_edit_exercise.php" method="POST" class="exercise-card p-4">
        <input type="hidden" name="exercise_id" value="<?php echo (int)$exercise['exercise_id']; ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Exercise Name *</label>
            <input type="text" class="form-control" id="name" name="name" required maxlength="255" value="<?php echo htmlspecialchars($exercise['name']); ?>">
        </div>
        <div class="mb-3">
            <label for="target_muscle" class="form-label">Target Muscle</label>
            <input type="text" class="form-control" id="target_muscle" name="target_muscle" maxlength="100" value="<?php echo htmlspecialchars($exercise['target_muscle']); ?>">
        </div>
        <div class="mb-3">
            <label for="movement_type" class="form-label">Movement Type *</label>
            <select id="movement_type" name="movement_type" class="form-select" required>
                <option value="">Choose movement type</option>
                <?php foreach ($movement_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($exercise['movement_type'] === $type) ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex justify-content-end">
            <a href="exercises.php" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
</body>
</html>