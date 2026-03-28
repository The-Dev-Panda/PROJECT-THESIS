<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Get filter and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$payment_method = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : '';
$time_filter = isset($_GET['time']) ? $_GET['time'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'transaction_date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Allowed sort columns
$allowed_sort = ['id', 'receipt_number', 'customer_name', 'customer_type', 'amount', 'payment_method', 'transaction_date'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'transaction_date';
}

// Validate sort order
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Build WHERE clause
$where_conditions = [];
$params = [];

// Search filter
if ($search !== '') {
    $where_conditions[] = "(t.customer_name LIKE :search OR t.receipt_number LIKE :search)";
    $params['search'] = "%$search%";
}

// Payment method filter
if ($payment_method !== '') {
    $where_conditions[] = "t.payment_method = :payment_method";
    $params['payment_method'] = $payment_method;
}

// Time filter
if ($time_filter) {
    switch ($time_filter) {
        case 'today':
            $where_conditions[] = "DATE(t.transaction_date) = DATE('now')";
            break;
        case 'week':
            $where_conditions[] = "t.transaction_date >= DATE('now', '-7 days')";
            break;
        case 'month':
            $where_conditions[] = "t.transaction_date >= DATE('now', '-30 days')";
            break;
        case 'year':
            $where_conditions[] = "t.transaction_date >= DATE('now', '-365 days')";
            break;
    }
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM transactions t $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get transactions with sorting
$query = "SELECT 
            t.*, 
            u.first_name, 
            u.last_name
          FROM transactions t
          LEFT JOIN users u ON t.staff_id = u.id
          $where_clause
          ORDER BY t.$sort_by $sort_order
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Get payment methods for filter dropdown
$payment_methods_stmt = $pdo->query("SELECT DISTINCT payment_method FROM transactions WHERE payment_method IS NOT NULL ORDER BY payment_method");
$payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate stats
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN DATE(transaction_date) = DATE('now') THEN amount ELSE 0 END) AS today,
        SUM(CASE WHEN DATE(transaction_date) = DATE('now','-1 day') THEN amount ELSE 0 END) AS yesterday
    FROM transactions
    WHERE status = 'completed'
");
$data = $stmt->fetch();

$growth = ($data['yesterday'] > 0)
    ? (($data['today'] - $data['yesterday']) / $data['yesterday']) * 100 : 0;

$total_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions");
$total_revenue = $total_revenue_stmt->fetch()['total'];

$today_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')");
$today_revenue = $today_revenue_stmt->fetch()['total'];

$month_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')");
$month_revenue = $month_revenue_stmt->fetch()['total'];

$total_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions");
$total_count = $total_count_stmt->fetch()['total'];

$total_count_stmt_td = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')");
$total_count_td = $total_count_stmt_td->fetch()['total'];

// Helper function to generate sort URL
function getSortUrl($column, $current_sort, $current_order, $search, $payment_method, $time, $page)
{
    $new_order = 'ASC';
    if ($current_sort === $column && $current_order === 'ASC') {
        $new_order = 'DESC';
    }
    $params = "sort=$column&order=$new_order";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($payment_method)
        $params .= "&payment_method=" . urlencode($payment_method);
    if ($time)
        $params .= "&time=$time";
    if ($page > 1)
        $params .= "&page=$page";
    return "?$params";
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order)
{
    if ($current_sort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="opacity: 0.3; font-size: 10px;"></i>';
    }
    return $current_order === 'ASC'
        ? '<i class="bi bi-arrow-up" style="color: var(--hazard); font-size: 10px;"></i>'
        : '<i class="bi bi-arrow-down" style="color: var(--hazard); font-size: 10px;"></i>';
}

// Build pagination URL
function getPaginationUrl($page_num, $search, $payment_method, $time, $sort, $order)
{
    $params = "page=$page_num";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($payment_method)
        $params .= "&payment_method=" . urlencode($payment_method);
    if ($time)
        $params .= "&time=$time";
    if ($sort !== 'transaction_date')
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
    <title>Transactions | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-receipt"></i> Transactions</h1>
                <p>Monitor all payment transactions</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stat-grid row p-0 mb-1 g-2">
            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon members">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($today_revenue, 0); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>

            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon registrations">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($month_revenue, 0); ?></div>
                    <div class="stat-label">Revenue This Month</div>
                </div>
            </div>

            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon equipment">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Revenue All Time</div>
                </div>
            </div>

            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon notifications">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_count; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
            </div>

            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon notifications">
                    <i class="bi bi-receipt-cutoff bi-success"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_count_td; ?></div>
                    <div class="stat-label">Transactions (Today)</div>
                </div>
            </div>

            <div class="stat-box col-sm-12 col-xl-3">
                <div class="stat-icon registrations"
                    style="color: <?php echo $growth >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div>
                    <div class="stat-value">
                        <?php echo ($growth >= 0 ? "<small style='color: var(--success);'>▲ " : "<small style='color: var(--danger);'>▼ ") . number_format(abs($growth), 2) . "%</small>"; ?>
                    </div>
                    <div class="stat-label">Growth</div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <section>
            <div>
                <div class="col-12 p-1 d-flex justify-content-end">
                    <button class="add-btn" data-bs-toggle="modal" data-bs-target="#exportModal"
                        style="background: var(--success); text-decoration: none; border: none; cursor: pointer;">
                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                    </button>
                </div>
                <form method="GET">
                    <div class="row">
                        <div class="search-wrapper col-sm-12 col-xl-2 p-1">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" name="search" class="search-input" maxlength="30"
                                placeholder="Search customer or receipt..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-sm-12 col-xl-2 p-1">
                            <select name="payment_method" class="search-input" style="min-width: 250px;">
                                <option value="">All Payment Methods</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $payment_method === $method ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($method); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-12 col-xl-2 p-1">
                            <select name="time" class="search-input" style="min-width: 180px;">
                                <option value="">All Time</option>
                                <option value="today" <?php echo $time_filter === 'today' ? 'selected' : ''; ?>>Today
                                </option>
                                <option value="week" <?php echo $time_filter === 'week' ? 'selected' : ''; ?>>Last 7
                                    Days
                                </option>
                                <option value="month" <?php echo $time_filter === 'month' ? 'selected' : ''; ?>>Last
                                    30
                                    Days
                                </option>
                                <option value="year" <?php echo $time_filter === 'year' ? 'selected' : ''; ?>>Last
                                    Year
                                </option>
                            </select>
                        </div>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                        <div class="col-sm-12 col-xl-1 p-1 d-flex justify-content-center">
                            <button type="submit" class="search-btn">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                        <div class="col-sm-12 col-xl-2 p-1 d-flex justify-content-center">
                            <?php if ($search || $payment_method || $time_filter): ?>
                                <a href="transaction.php" class="btn-secondary" style="text-decoration: none;">
                                    <i class="bi bi-x-circle"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Transactions Table -->
        <div class="inventory-table">
            <table>
                <thead>
                    <tr>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('id', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            ID <?php echo getSortIcon('id', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('receipt_number', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Receipt # <?php echo getSortIcon('receipt_number', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('customer_name', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Customer <?php echo getSortIcon('customer_name', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('customer_type', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Type <?php echo getSortIcon('customer_type', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('amount', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Amount <?php echo getSortIcon('amount', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('payment_method', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Payment Method <?php echo getSortIcon('payment_method', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('transaction_date', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Date <?php echo getSortIcon('transaction_date', $sort_by, $sort_order); ?>
                        </th>
                        <th class="sortable-header"
                            onclick="window.location.href='<?php echo getSortUrl('staff_id', $sort_by, $sort_order, $search, $payment_method, $time_filter, $page); ?>'">
                            Staff <?php echo getSortIcon('staff_id', $sort_by, $sort_order); ?>
                        </th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><?php echo $txn['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($txn['receipt_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($txn['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($txn['customer_type']); ?></td>
                                <td><strong
                                        style="color: var(--hazard);">₱<?php echo number_format($txn['amount'], 2); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($txn['payment_method']); ?></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($txn['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars(($txn['first_name'] ?? '') . ' ' . ($txn['last_name'] ?? '')); ?>
                                </td>
                                <td><?php echo htmlspecialchars($txn['desc'] ?? ''); ?></td>
                                <td>
                                    <button class="btn-icon"
                                        onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($txn)); ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <?php echo ($search || $payment_method || $time_filter) ? 'No transactions match your filters' : 'No transactions found'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px; text-align: center;">
                <?php if ($page > 1): ?>
                    <a href="<?php echo getPaginationUrl($page - 1, $search, $payment_method, $time_filter, $sort_by, $sort_order); ?>"
                        style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?php echo getPaginationUrl($i, $search, $payment_method, $time_filter, $sort_by, $sort_order); ?>"
                        style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo getPaginationUrl($page + 1, $search, $payment_method, $time_filter, $sort_by, $sort_order); ?>"
                        style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($search || $payment_method || $time_filter || $sort_by !== 'transaction_date' || $sort_order !== 'DESC'): ?>
            <div style="margin-top: 16px; text-align: center;">
                <a href="transaction.php"
                    style="color: var(--text-muted); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset All Filters
                </a>
            </div>
        <?php endif; ?>
        </section>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                    <h5 class="modal-title" id="exportModalLabel"
                        style="font-family: 'Chakra Petch', sans-serif; color: var(--hazard); text-transform: uppercase;">
                        <i class="bi bi-file-earmark-excel"></i> Export Transactions to Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="filter: invert(1);"></button>
                </div>
                <form method="GET" action="export_transaction.php">
                    <div class="modal-body">
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 20px;">
                            Select the date range for the transactions you want to export. Leave blank for all
                            transactions.
                        </p>

                        <div class="row g-3">
                            <div class="col-sm-12 col-xl-6">
                                <div class="form-group">
                                    <label class="form-label">From Date</label>
                                    <input type="date" name="from_date" class="form-input" value="">
                                </div>
                            </div>
                            <div class="col-sm-12 col-xl-6">
                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input type="date" name="to_date" class="form-input"
                                        value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 15px;">
                            <label class="form-label">Payment Method (Optional)</label>
                            <select name="payment_method" class="form-input">
                                <option value="">All Payment Methods</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?php echo htmlspecialchars($method); ?>">
                                        <?php echo htmlspecialchars($method); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div
                            style="background: rgba(255, 204, 0, 0.1); border: 1px solid var(--hazard); padding: 12px; margin-top: 15px; border-radius: 2px;">
                            <p style="color: var(--text-muted); font-size: 11px; margin: 0;">
                                <i class="bi bi-info-circle" style="color: var(--hazard);"></i>
                                The export will include: ID, Receipt #, Customer, Type, Amount, Payment Method, Date,
                                Staff, and Description.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--border);">
                        <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-download"></i> Export to Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Transaction Details Modal -->
    <div id="transactionModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div
            style="background: var(--bg-surface); border: 1px solid var(--border); max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div
                style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3
                    style="font-family: 'Chakra Petch', sans-serif; color: var(--hazard); text-transform: uppercase; margin: 0;">
                    Transaction Details</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div id="modalContent" style="padding: 20px;"></div>
        </div>
    </div>

    <script>
        function viewTransaction(txn) {
            const modal = document.getElementById('transactionModal');
            const content = document.getElementById('modalContent');

            content.innerHTML = `
                <div style="display: grid; gap: 16px;">
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Receipt Number:</strong>
                        <span style="color: var(--hazard); font-family: 'Courier New', monospace; font-weight: 700;">${txn.receipt_number}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Customer Name:</strong>
                        <span style="color: var(--text-primary);">${txn.customer_name}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Customer Type:</strong>
                        <span style="color: var(--text-primary);">${txn.customer_type}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Amount:</strong>
                        <span style="color: var(--hazard); font-size: 24px; font-family: 'Chakra Petch', sans-serif; font-weight: 700;">₱${parseFloat(txn.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Payment Method:</strong>
                        <span style="color: var(--text-primary);">${txn.payment_method}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Transaction Date:</strong>
                        <span style="color: var(--text-primary);">${new Date(txn.transaction_date).toLocaleString()}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Staff Name:</strong>
                        <span style="color: var(--text-primary);">${(txn.first_name || '') + ' ' + (txn.last_name || '') || 'N/A'}</span>
                    </div>
                    ${txn.desc ? `
                    <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; padding: 12px; background: var(--bg-card); border: 1px solid var(--border);">
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Description:</strong>
                        <span style="color: var(--text-sub);">${txn.desc}</span>
                    </div>
                    ` : ''}
                </div>
            `;

            modal.style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('transactionModal').style.display = 'none';
        }

        document.getElementById('transactionModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>

</html>