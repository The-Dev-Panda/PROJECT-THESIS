<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Pagination
$records_per_page = 15;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_clause = "";
$params = [];

if ($search !== '') {
    $where_clause = "WHERE customer_name LIKE :search OR receipt_number LIKE :search";
    $params['search'] = "%$search%";
}

// Get total records
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions $where_clause");
$total_stmt->execute($params);
$total_records = $total_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get transactions
$query = "SELECT t.*, u.username as staff_username 
          FROM transactions t 
          LEFT JOIN users u ON t.staff_id = u.id 
          $where_clause 
          ORDER BY t.transaction_date DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Calculate stats

//growth
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

//total revenue
$total_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions");
$total_revenue = $total_revenue_stmt->fetch()['total'];
//revenue today
$today_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')");
$today_revenue = $today_revenue_stmt->fetch()['total'];
//revenue this month
$month_revenue_stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = strftime('%Y-%m', 'now')");
$month_revenue = $month_revenue_stmt->fetch()['total'];
//transactions all time
$total_count_stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions");
$total_count = $total_count_stmt->fetch()['total'];
//Transaction today only
$total_count_stmt_td = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')");
$total_count_td = $total_count_stmt_td->fetch()['total'];

?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Transactions | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../staff/staff.css">

    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-receipt"></i> Transactions</h1>
                <p>Monitor all payment transactions</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-cash-stack"></i>
                    <span>₱<?php echo number_format($total_revenue, 2); ?> Total</span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="stat-box">
                <div class="stat-icon members">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($today_revenue, 0); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon registrations">
                    <i class="bi bi-calendar-month"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($month_revenue, 0); ?></div>
                    <div class="stat-label">Revenue This Month</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon equipment">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="stat-value">₱<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Revenue All Time</div>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-icon notifications">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_count; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon notifications">
                    <i class="bi bi-receipt-cutoff bi-success"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo $total_count_td; ?></div>
                    <div class="stat-label">Transactions (Today)</div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon registrations" style="color: var(--success);">
                    <i class="bi bi-currency-exchange"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo ($growth >= 0 ? "<small class='text-success'>▲ " : "<small class='text-danger'>▼") . number_format(abs($growth), 2) . "%</small>";; ?></div>
                    <div class="stat-label">Growth</div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <section>
            <div class="inventory-header">
                <div class="search-container">
                    <div class="search-wrapper" style="flex: 1;">
                        <i class="bi bi-search search-icon"></i>
                        <form method="GET" style="width: 100%;">
                            <input type="text" name="search" class="search-input"
                                placeholder="Search customer or receipt number..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>
                <?php if ($search): ?>
                    <a href="transaction.php" class="btn-secondary" style="padding: 11px 22px; text-decoration: none;">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </div>

            <!-- Transactions Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Receipt #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                            <th>Staff</th>
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
                                    <td><?php echo htmlspecialchars($txn['staff_id'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($txn['desc']); ?></td>
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
                                <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">No
                                    transactions found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; 
                           color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
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
                        <strong style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Staff ID:</strong>
                        <span style="color: var(--text-primary);">${txn.staff_id || 'N/A'}</span>
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