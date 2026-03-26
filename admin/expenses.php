<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Get expense details before deleting
    $stmt = $pdo->prepare("SELECT expense_name, expense, author FROM expense_history WHERE expense_id = :id");
    $stmt->execute(['id' => $id]);
    $expense = $stmt->fetch();

    if ($expense) {
        // Delete expense
        $stmt = $pdo->prepare("DELETE FROM expense_history WHERE expense_id = :id");
        $stmt->execute(['id' => $id]);

        // Log to notification history
        $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
        $notif->execute([
            'EXPENSE DELETED',
            "Expense '{$expense['expense_name']}' (₱" . number_format($expense['expense'], 2) . ") has been removed",
            "Deleted by " . $_SESSION['username'] . " | Originally added by: " . $expense['author'],
            'Finance'
        ]);
    }

    header('Location: expenses.php?success=deleted');
    exit();
}

// Get filter and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$time_filter = isset($_GET['time']) ? $_GET['time'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Pagination
$records_per_page = 15;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Allowed sort columns
$allowed_sort = ['expense_id', 'expense_name', 'expense', 'author', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Validate sort order
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Build WHERE clause
$where_conditions = [];
$params = [];

// Search filter
if ($search !== '') {
    $where_conditions[] = "(expense_name LIKE :search OR description LIKE :search OR author LIKE :search)";
    $params['search'] = "%$search%";
}

// Time filter
if ($time_filter) {
    switch ($time_filter) {
        case 'today':
            $where_conditions[] = "DATE(created_at) = DATE('now')";
            break;
        case 'week':
            $where_conditions[] = "created_at >= DATE('now', '-7 days')";
            break;
        case 'month':
            $where_conditions[] = "created_at >= DATE('now', '-30 days')";
            break;
        case 'year':
            $where_conditions[] = "created_at >= DATE('now', '-365 days')";
            break;
    }
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM expense_history $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get expenses
$query = "SELECT * FROM expense_history $where_clause ORDER BY $sort_by $sort_order LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$expenses = $stmt->fetchAll();

// Calculate total expenses
$total_stmt = $pdo->prepare("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history $where_clause");
$total_stmt->execute($params);
$total_expenses = $total_stmt->fetch()['total'];

// Helper functions
function getSortUrl($column, $current_sort, $current_order, $search, $time, $page)
{
    $new_order = 'ASC';
    if ($current_sort === $column && $current_order === 'ASC') {
        $new_order = 'DESC';
    }
    $params = "sort=$column&order=$new_order";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($time)
        $params .= "&time=$time";
    if ($page > 1)
        $params .= "&page=$page";
    return "?$params";
}

function getSortIcon($column, $current_sort, $current_order)
{
    if ($current_sort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="opacity: 0.3; font-size: 10px;"></i>';
    }
    return $current_order === 'ASC'
        ? '<i class="bi bi-arrow-up" style="color: var(--hazard); font-size: 10px;"></i>'
        : '<i class="bi bi-arrow-down" style="color: var(--hazard); font-size: 10px;"></i>';
}

function getPaginationUrl($page_num, $search, $time, $sort, $order)
{
    $params = "page=$page_num";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($time)
        $params .= "&time=$time";
    if ($sort !== 'created_at')
        $params .= "&sort=$sort";
    if ($order !== 'DESC')
        $params .= "&order=$order";
    return "?$params";
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Expense Management - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../staff/staff.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <style>
        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }

        .sortable-header:hover {
            color: var(--hazard);
        }
    </style>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-wallet2"></i> Expense Management</h1>
                <p>Track and manage business expenses</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-cash-stack"></i>
                    <span>₱<?php echo number_format($total_expenses, 2); ?> Total</span>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i>
                <?php
                if ($_GET['success'] === 'added')
                    echo 'Expense added successfully';
                elseif ($_GET['success'] === 'updated')
                    echo 'Expense updated successfully';
                elseif ($_GET['success'] === 'deleted')
                    echo 'Expense deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <!-- Add New Expense -->
        <section>
            <h2><i class="bi bi-plus-circle"></i> Add New Expense</h2>
            <div class="registration-card">
                <form method="POST" action="process_expenses.php">
                    <?php echo fitstop_csrf_input(); ?>
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="form-group col-sm-12 col-xl-3">
                            <label class="form-label">Expense Name</label>
                            <input type="text" name="expense_name" class="form-input"
                                placeholder="e.g., Equipment Purchase" maxlength="100" required>
                        </div>
                        <div class="form-group col-sm-12 col-xl-3">
                            <label class="form-label">Amount (₱)</label>
                            <input type="text" name="expense" class="form-input number-only" placeholder="0.00"
                                maxlength="12" required>
                        </div>
                        <div class="form-group form-group col-sm-12 col-xl-5">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-input"
                                placeholder="Details about the expense" maxlength="200">
                        </div>
                        <div class="form-group col-sm-12 col-xl-1">
                            <button type="submit" class="btn-primary" style="width: 100%;">
                                <i class="bi bi-plus-circle"></i> Add
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Search & Filter Section -->
        <section>
            <form method="GET">
                <div class="row my-2">
                    <div class="col-sm-12 col-xl-2">
                        <div class="search-wrapper" style="flex: 2;">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search expenses..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-sm-12 col-xl-2">
                        <select name="time" class="search-input">
                            <option value="">All Time</option>
                            <option value="today" <?php echo $time_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $time_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days
                            </option>
                            <option value="month" <?php echo $time_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days
                            </option>
                            <option value="year" <?php echo $time_filter === 'year' ? 'selected' : ''; ?>>Last Year
                            </option>
                        </select>
                    </div>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                    <div class="col-sm-12 col-xl-2 my-1">
                        <button type="submit" class="search-btn">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-sm-12 col-xl-2 d-flex justify-content-center my-1">
                        <?php if ($search || $time_filter): ?>
                            <a href="expenses.php" class="btn-secondary" style="padding: 11px 22px; text-decoration: none;">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-12 col-xl-2 d-flex justify-content-center my-1">
                        <a href="export_expenses.php?<?php echo http_build_query(['search' => $search, 'time' => $time_filter]); ?>"
                            class="add-btn" style="background: var(--success); text-decoration: none;">
                            <i class="bi bi-file-earmark-excel"></i> Export to Excel
                        </a>
                    </div>
                </div>
            </form>


            <!-- Expense Statistics -->
            <div style="margin-bottom: 20px;">
                <h2 style="margin: 0; border: none; padding: 0; font-size: 11px;">
                    <i class="bi bi-list-ul"></i> Expense History
                    <span style="color: var(--text-muted); margin-left: 10px;">
                        Showing
                        <?php echo min($offset + 1, $total_records); ?>-<?php echo min($offset + $records_per_page, $total_records); ?>
                        of <?php echo $total_records; ?> records
                    </span>
                </h2>
            </div>

            <!-- Expenses Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('expense_id', $sort_by, $sort_order, $search, $time_filter, $page); ?>'">
                                ID <?php echo getSortIcon('expense_id', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('expense_name', $sort_by, $sort_order, $search, $time_filter, $page); ?>'">
                                Expense Name <?php echo getSortIcon('expense_name', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('expense', $sort_by, $sort_order, $search, $time_filter, $page); ?>'">
                                Amount <?php echo getSortIcon('expense', $sort_by, $sort_order); ?>
                            </th>
                            <th>Description</th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('author', $sort_by, $sort_order, $search, $time_filter, $page); ?>'">
                                Added By <?php echo getSortIcon('author', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('created_at', $sort_by, $sort_order, $search, $time_filter, $page); ?>'">
                                Date <?php echo getSortIcon('created_at', $sort_by, $sort_order); ?>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($expenses) > 0): ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo $expense['expense_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($expense['expense_name']); ?></strong></td>
                                    <td><strong
                                            style="color: var(--danger);">₱<?php echo number_format($expense['expense'], 2); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($expense['author']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($expense['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-icon"
                                            onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?php echo $expense['expense_id']; ?>" class="btn-icon"
                                            onclick="return confirm('Delete this expense?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo ($search || $time_filter) ? 'No expenses match your filters' : 'No expenses found'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (count($expenses) > 0): ?>
                        <tfoot>
                            <tr style="background: var(--bg-card); font-weight: 700;">
                                <td colspan="2" style="text-align: right; padding: 12px;">TOTAL:</td>
                                <td style="color: var(--danger); font-size: 16px;">
                                    ₱<?php echo number_format($total_expenses, 2); ?></td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo getPaginationUrl($page - 1, $search, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo getPaginationUrl($i, $search, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo getPaginationUrl($page + 1, $search, $time_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Edit Modal -->
    <div id="editModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--bg-surface); border: 1px solid var(--border); max-width: 600px; width: 90%;">
            <div
                style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3
                    style="font-family: 'Chakra Petch', sans-serif; color: var(--hazard); text-transform: uppercase; margin: 0;">
                    Edit Expense</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" action="process_expenses.php">
                <?php echo fitstop_csrf_input(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="expense_id" id="edit_id">
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label class="form-label">Expense Name</label>
                        <input type="text" name="expense_name" id="edit_name" class="form-input" maxlength="100"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (₱)</label>
                        <input type="text" name="expense" id="edit_expense" class="form-input number-only"
                            maxlength="12" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-input" rows="3"
                            maxlength="200"></textarea>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" onclick="closeModal()" class="btn-secondary"
                            style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            <i class="bi bi-check-circle"></i> Update Expense
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="validation.js"></script>
    <script>
        function editExpense(expense) {
            document.getElementById('edit_id').value = expense.expense_id;
            document.getElementById('edit_name').value = expense.expense_name;
            document.getElementById('edit_expense').value = expense.expense;
            document.getElementById('edit_description').value = expense.description || '';
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>