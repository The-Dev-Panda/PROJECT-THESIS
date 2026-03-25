<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include('../Login/connection.php');

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
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
$total_records = (int) $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, (int) ceil($total_records / $records_per_page));

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
    <title>Exercises | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <?php include('includes/header_admin.php'); ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-bar-chart-line"></i> Exercise Library</h1>
                <p>Manage exercises for workout logging and member programs</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-2 col-xl-offset-4">
                <div class="topbar-badge">
                    <i class="bi bi-lightning-fill"></i>
                    <span><?php echo $total_records; ?> Exercises</span>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div
                style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <section>
            <div>
                <form method="GET">
                    <div class="row my-2">
                        <div class="col-sm-12 col-xl-3 my-1">
                            <div class="search-wrapper" style="min-width: 200px;">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" name="search" class="search-input" placeholder="Search exercises..."
                                    maxlength="30" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-sm-12 col-xl-2 my-1">
                            <select name="target_muscle" class="search-input" style="min-width: 200px;">
                                <option value="">All Muscles</option>
                                <?php foreach ($target_list as $target): ?>
                                    <option value="<?php echo htmlspecialchars($target); ?>" <?php echo ($target_muscle == $target) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($target); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-12 col-xl-2 my-1">
                            <select name="movement_type" class="search-input" style="min-width: 200px;">
                                <option value="">All Types</option>
                                <?php foreach ($movement_list as $movement): ?>
                                    <option value="<?php echo htmlspecialchars($movement); ?>" <?php echo ($movement_type == $movement) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movement); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-12 col-xl-1 col-xl-offset-1 d-flex justify-content-center p-1">
                            <button type="submit" class="search-btn col-sm-9 col-xl-12 p-3">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                        <div class="col-sm-12 col-xl-2 d-flex justify-content-center p-1">
                            <a href="add_exercise.php" class="add-btn col-9 p-3 ">
                                <i class="bi bi-plus-circle"></i> Add Exercise
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Exercise Table -->
            <div class="inventory-table">
                <table>
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
                                    <td><?php echo (int) $row['exercise_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['target_muscle']); ?></td>
                                    <td>
                                        <span
                                            style="padding: 4px 11px; font-size: 10px; background: rgba(255,255,255,0.1); border: 1px solid var(--border); text-transform: uppercase;">
                                            <?php echo htmlspecialchars($row['movement_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn-icon"
                                            href="edit_exercise.php?id=<?php echo (int) $row['exercise_id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a class="btn-icon"
                                            href="process_delete_exercise.php?id=<?php echo (int) $row['exercise_id']; ?>"
                                            onclick="return confirm('Delete this exercise? This cannot be reversed.');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">No
                                    exercises found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&target_muscle=<?php echo urlencode($target_muscle); ?>&movement_type=<?php echo urlencode($movement_type); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

</body>

</html>