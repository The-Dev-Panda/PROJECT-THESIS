<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include('../Login/connection.php');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$target_muscle = isset($_GET['target_muscle']) ? trim($_GET['target_muscle']) : '';
$movement_type = isset($_GET['movement_type']) ? trim($_GET['movement_type']) : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = 'name LIKE :search';
    $params['search'] = '%' . $search . '%';
}
if ($target_muscle !== '') {
    $where[] = 'target_muscle = :target_muscle';
    $params['target_muscle'] = $target_muscle;
}
if ($movement_type !== '') {
    $where[] = 'movement_type = :movement_type';
    $params['movement_type'] = $movement_type;
}

$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM exercises $where_clause");
$total_stmt->execute($params);
$total_records = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, (int)ceil($total_records / $records_per_page));

$query = "SELECT exercise_id, name, target_muscle, movement_type FROM exercises $where_clause ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);

$target_list = $pdo->query("SELECT DISTINCT target_muscle FROM exercises WHERE target_muscle IS NOT NULL AND target_muscle != '' ORDER BY target_muscle")->fetchAll(PDO::FETCH_COLUMN);
$movement_list = $pdo->query("SELECT DISTINCT movement_type FROM exercises WHERE movement_type IS NOT NULL AND movement_type != '' ORDER BY movement_type")->fetchAll(PDO::FETCH_COLUMN);

$allowedMovementTypes = ['strength', 'cardio', 'hypertrophy', 'flexibility', 'mobility', 'other'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exercise Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-dark">
<?php include('includes/header_admin.php'); ?>
<div class="container pb-5">
    <div class="page-header my-4">
        <h1 class="text-white">Exercise Library</h1>
        <p class="text-muted">Manage exercises used in workout logging and member programs.</p>
    </div>

    <?php if (!empty($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form method="GET" class="row g-2 align-items-end" style="width:100%;">
            <div class="col-sm-4">
                <label class="form-label text-light">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Exercise name" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label text-light">Target Muscle</label>
                <select name="target_muscle" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($target_list as $target): ?>
                        <option value="<?php echo htmlspecialchars($target); ?>" <?php echo ($target_muscle == $target) ? 'selected' : ''; ?>><?php echo htmlspecialchars($target); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label text-light">Movement Type</label>
                <select name="movement_type" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($movement_list as $movement): ?>
                        <option value="<?php echo htmlspecialchars($movement); ?>" <?php echo ($movement_type == $movement) ? 'selected' : ''; ?>><?php echo htmlspecialchars($movement); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-warning w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="mb-3 text-end">
        <a href="add_exercise.php" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i>Add Exercise</a>
    </div>

    <div class="table-responsive exercise-card">
        <table class="table table-hover table-dark align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Target Muscle</th>
                    <th>Movement Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($exercises) > 0): ?>
                    <?php foreach ($exercises as $row): ?>
                        <tr>
                            <td><?php echo (int)$row['exercise_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['target_muscle']); ?></td>
                            <td><?php echo htmlspecialchars($row['movement_type']); ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary me-1" href="edit_exercise.php?id=<?php echo (int)$row['exercise_id']; ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="process_delete_exercise.php?id=<?php echo (int)$row['exercise_id']; ?>" onclick="return confirm('Delete this exercise? This cannot be reversed.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No exercises found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav aria-label="page navigation">
            <ul class="pagination justify-content-center mt-3">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
</body>
</html>