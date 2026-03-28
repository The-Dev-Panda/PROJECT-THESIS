<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
date_default_timezone_set('Asia/Manila');

include("../Login/connection.php");

// Get statistics
$stats = [];

// Total Members
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
$stats['total_members'] = $stmt->fetch()['total'];

// Active Staff
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'staff'");
$stats['total_staff'] = $stmt->fetch()['total'];

// Total Revenue (from transactions)
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions");
$stats['total_revenue'] = $stmt->fetch()['total'];

// today Revenue (from transactions)
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(created_at) = DATE('now')");
$stats['today_revenue'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT('now', '%Y-%m')");
$stats['this_month_revenue'] = $stmt->fetch()['total'];

// Unread Notifications
$stmt = $pdo->query("SELECT COUNT(*) as total FROM notification_history WHERE is_read = 0");
$stats['unread_notifications'] = $stmt->fetch()['total'];

// Member Growth (last 6 months)
$member_growth = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'user' AND DATE_FORMAT(created_at, '%Y-%m') = :date");
    $stmt->execute(['date' => $date]);
    $count = $stmt->fetch()['count'];
    $member_growth[] = [
        'month' => date('M', strtotime("-$i months")),
        'count' => $count
    ];
}

// Revenue by Month (last 6 months)
$revenue_by_month = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = :date");
    $stmt->execute(['date' => $date]);
    $total = $stmt->fetch()['total'];
    $revenue_by_month[] = [
        'month' => date('M', strtotime("-$i months")),
        'total' => $total
    ];
}

// Member check-ins
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(datetime) = :date");
$stmt->execute(['date' => $date]);
$today_member_attendance = $stmt->fetch()['count'];

// Walk-in check-ins
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM walk_attendance WHERE DATE(datetime) = :date");
$stmt->execute(['date' => $date]);
$today_walk_attendance = $stmt->fetch()['count'];


// FINANCIAL ANALYTICS - Add after existing stats

// Total Expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history");
$total_expenses = $stmt->fetch()['total'];

// Today's Expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE DATE(created_at) = DATE('now')");
$today_expenses = $stmt->fetch()['total'];

// This Month's Expenses
$stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT('now', '%Y-%m')");
$month_expenses = $stmt->fetch()['total'];

// Calculate Net Profit
$gross_profit = $stats['total_revenue']; // Total Revenue
$net_profit = $gross_profit - $total_expenses;
$profit_margin = $gross_profit > 0 ? ($net_profit / $gross_profit) * 100 : 0;

// Monthly Net Profit (last 6 months)
$net_profit_by_month = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));

    // Revenue for this month
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = :date");
    $stmt->execute(['date' => $date]);
    $monthly_revenue = $stmt->fetch()['total'];

    // Expenses for this month
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE DATE_FORMAT(created_at, '%Y-%m') = :date");
    $stmt->execute(['date' => $date]);
    $monthly_expenses = $stmt->fetch()['total'];

    $net_profit_by_month[] = [
        'month' => date('M', strtotime("-$i months")),
        'revenue' => $monthly_revenue,
        'expenses' => $monthly_expenses,
        'net' => $monthly_revenue - $monthly_expenses
    ];
}

// Expense Categories (Top 5)
$stmt = $pdo->query("SELECT expense_name, SUM(expense) as total FROM expense_history GROUP BY expense_name ORDER BY total DESC LIMIT 5");
$expense_categories = $stmt->fetchAll();

// Low Stock Items
$stmt = $pdo->query("SELECT item_name, quantity FROM inventory WHERE quantity < 10 ORDER BY quantity ASC LIMIT 5");
$low_stock = $stmt->fetchAll();

// Recent Feedback
$stmt = $pdo->query("SELECT about, status FROM feedback ORDER BY created_at DESC LIMIT 5");
$recent_feedback = $stmt->fetchAll();

// Daily Check-ins (last 7 days) - from attendance table
$checkin_activity = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));

    // Member check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $member_count = $stmt->fetch()['count'];

    // Walk-in check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM walk_attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $walkin_count = $stmt->fetch()['count'];

    $checkin_activity[] = [
        'day' => date('D', strtotime("-$i days")),
        'member_count' => $member_count,
        'walkin_count' => $walkin_count,
        'total' => $member_count + $walkin_count
    ];
}

// Top Exercises
$stmt = $pdo->query("SELECT e.name, COUNT(w.log_id) as usage_count 
                     FROM workout_logs w 
                     JOIN exercises e ON w.exercise_id = e.exercise_id 
                     GROUP BY e.exercise_id 
                     ORDER BY usage_count DESC 
                     LIMIT 5");
$top_exercises = $stmt->fetchAll();

// Payment Methods Distribution (for chart)
$stmt = $pdo->query("SELECT payment_method, COUNT(*) as count FROM transactions GROUP BY payment_method");
$payment_methods_data = $stmt->fetchAll();

// Recent Transactions
$stmt = $pdo->query("SELECT customer_name, amount, payment_method, transaction_date FROM transactions ORDER BY transaction_date DESC LIMIT 5");
$recent_transactions = $stmt->fetchAll();

// Revenue by Payment Method (Business insight)
$stmt = $pdo->query("SELECT payment_method, COALESCE(SUM(amount), 0) as total FROM transactions GROUP BY payment_method");
$revenue_by_payment = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Analytics | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <!-- WEB APP PROMPT -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#080808">

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js');
        }
    </script>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6 reveal-left">
                <h1><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
                <p>Real-time insights and performance metrics</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-3 reveal-right">
                <div class="topbar-badge">
                    <span class="live-indicator"></span>
                    <span>Live Data • <span id="lastUpdate">Just now</span></span>
                </div>
            </div>
            <div class="topbar-right col-sm-12 col-xl-3 reveal-right">
                <button id="installBtn" class="topbar-badge my-3 p-3" style="display: none;">Install App</button>
            </div>
        </div>

        <!-- WEB APP PART -->
        <script>
            let deferredPrompt;

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                document.getElementById('installBtn').style.display = 'block';
            });

            document.getElementById('installBtn').addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User response: ${outcome}`);
                    deferredPrompt = null;
                }
            });
        </script>

        <!-- Stats Grid -->
        <div class="row g-3 mb-3 " id="statsGrid">
            <div class="row my-2 d-flex justify-content-center">
                <div class="col-12 col-sm-12 col-lg-3 reveal-left">
                    <a href="view_members.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon members"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="stat-value" id="stat-members"><?php echo $stats['total_members']; ?></div>
                                <div class="stat-label">Total Members</div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-sm-12 col-lg-3 reveal-left">
                    <a href="view_staff.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon registrations"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <div class="stat-value" id="stat-staff"><?php echo $stats['total_staff']; ?></div>
                                <div class="stat-label">Active Staff</div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-sm-12 col-lg-3 reveal-left">
                    <a href="notification.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon notifications"><i class="bi bi-bell-fill"></i></div>
                            <div>
                                <div class="stat-value" id="stat-notifications">
                                    <?php echo $stats['unread_notifications']; ?>
                                </div>
                                <div class="stat-label">Unread Notifications</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row my-2 d-flex justify-content-center">
                <!-- Enhanced Stats Grid with Financial Metrics -->
                <div class="col-12 col-sm-12 col-xl-3 reveal-right">
                    <a href="transaction.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon equipment"><i class="bi bi-cash-coin"></i></div>
                            <div>
                                <div class="stat-value" id="stat-revenue">
                                    ₱<?php echo number_format($stats['total_revenue'], 2); ?></div>
                                <div class="stat-label">Total Gross Revenue</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-12 col-xl-3  reveal-right">
                    <a href="transaction.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon equipment"><i class="bi bi-cash-coin"></i></div>
                            <div>
                                <div class="stat-value" id="stat-today-revenue">
                                    ₱<?php echo number_format($stats['today_revenue'], 2); ?>
                                </div>
                                <div class="stat-label">Today's Gross Revenue</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-12 col-xl-3  reveal-right">
                    <a href="transaction.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon equipment"><i class="bi bi-cash-coin"></i></div>
                            <div>
                                <div class="stat-value" id="stat-month-revenue">
                                    ₱<?php echo number_format($monthly_revenue, 2); ?>
                                </div>
                                <div class="stat-label">This Month's Gross Revenue</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row my-2 d-flex justify-content-center">
                <div class="col-12 col-sm-12 col-xl-3  reveal-left">
                    <a href="expenses.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon" style="background: rgba(255, 71, 87, 0.1); color: var(--danger);"><i
                                    class="bi bi-wallet2"></i></div>
                            <div>
                                <div class="stat-value" style="color: var(--danger);" id="stat-total-expenses">
                                    ₱<?php echo number_format($total_expenses, 2); ?></div>
                                <div class="stat-label">Total Expenses</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-12 col-xl-3 reveal-left">
                    <a href="expenses.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon" style="background: rgba(255, 71, 87, 0.1); color: var(--danger);"><i
                                    class="bi bi-wallet2"></i></div>
                            <div>
                                <div class="stat-value" style="color: var(--danger);" id="stat-today-expenses">
                                    ₱<?php echo number_format($today_expenses, 2); ?></div>
                                <div class="stat-label">Today's Expenses</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-sm-12 col-xl-3 reveal-left">
                    <a href="expenses.php" class="text-decoration-none text-dark">
                        <div class="stat-box h-100">
                            <div class="stat-icon" style="background: rgba(255, 71, 87, 0.1); color: var(--danger);"><i
                                    class="bi bi-wallet2"></i></div>
                            <div>
                                <div class="stat-value" style="color: var(--danger);" id="stat-month-expenses">
                                    ₱<?php echo number_format($month_expenses, 2); ?></div>
                                <div class="stat-label">This Month's Expenses</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row my-2 d-flex justify-content-center">
                <div class="col-12 col-sm-12 col-xl-3 reveal-right">
                    <div class="stat-box h-100"
                        style="border-left: 3px solid <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <div class="stat-icon"
                            style="background: <?php echo $net_profit >= 0 ? 'rgba(34, 208, 122, 0.1)' : 'rgba(255, 71, 87, 0.1)'; ?>; color: <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <div class="stat-value"
                                style="color: <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;"
                                id="stat-net-profit">
                                ₱<?php echo number_format($net_profit, 2); ?></div>
                            <div class="stat-label">Net Profit</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-12 col-xl-3 reveal-right">
                    <div class="stat-box h-100">
                        <div class="stat-icon registrations"><i class="bi bi-percent"></i></div>
                        <div>
                            <div class="stat-value" id="stat-profit-margin">
                                <?php echo number_format($profit_margin, 1); ?>%
                            </div>
                            <div class="stat-label">Profit Margin</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Performance Chart -->
        <section>
            <h2><i class="bi bi-bank"></i> Financial Performance</h2>
            <div class="row g-3 reveal">
                <div class="col-12 col-lg-8">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-graph-up"></i> Net Profit Analysis (6 Months)
                        </h3>
                        <div style="position: relative; height: 300px;">
                            <canvas id="netProfitChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-pie-chart"></i> Top Expenses
                        </h3>
                        <canvas id="expenseCategoriesChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </section>
        <!-- Charts Row 1 -->
        <section class="container-fluid">
            <h2><i class="bi bi-bar-chart-line"></i> Growth & Revenue Trends</h2>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-graph-up-arrow"></i> Member Growth (6 Months)
                        </h3>
                        <div style="position: relative; height: 250px;">
                            <canvas id="memberGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-cash-stack"></i> Revenue by Month
                        </h3>
                        <div style="position: relative; height: 250px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Charts Row 2 -->
        <section>
            <h2><i class="bi bi-activity"></i> Activity & Revenue Insights</h2>
            <div class="row reveal-left my-2">
                <div class="col-12 col-sm-12 col-xl-3">
                    <div class="stat-box h-100">
                        <div class="stat-icon" style="background: rgba(255, 71, 71, 0.1); color: var(--danger);"><i
                                class="bi bi-person"></i></div>
                        <div>
                            <div class="stat-value" style="color: var(--danger);" id="today_walk_attendace">
                                <?php echo $today_walk_attendance; ?>
                            </div>
                            <div class="stat-label">Today's Walk-In Check-Ins</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-xl-3">
                    <div class="stat-box h-100">
                        <div class="stat-icon" style="background: rgba(252, 255, 71, 0.1); color: var(--hazard);"><i
                                class="bi bi-person"></i></div>
                        <div>
                            <div class="stat-value" style="color: var(--hazard);" id="stat-today-member-attendance">
                                <?php echo $today_member_attendance; ?>
                            </div>
                            <div class="stat-label">Today's Mmeber Check-Ins</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-calendar-check"></i> Check-ins | Members & Walk-ins | (Last 7 Days)
                        </h3>
                        <canvas id="checkinActivityChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <!-- Revenue by Payment Method -->
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-credit-card"></i> Revenue by Payment
                        </h3>
                        <canvas id="paymentMethodChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- Data Tables Row -->
        <section>
            <h2><i class="bi bi-table"></i> Recent Activity & Insights</h2>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-exclamation-triangle"></i> Low Stock Alert
                        </h3>
                        <div id="lowStockContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($low_stock as $item): ?>
                                <div
                                    style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border);">
                                    <span
                                        style="color: var(--text-primary); font-size: 13px;"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                    <span class="status-badge low-stock"><?php echo $item['quantity']; ?> left</span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($low_stock)): ?>
                                <div style="text-align: center; padding: 20px; color: var(--text-muted);">All items well
                                    stocked</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-trophy"></i> Most Popular Exercises
                        </h3>
                        <div id="topExercisesContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($top_exercises as $exercise): ?>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border);">
                                    <span
                                        style="color: var(--text-primary); font-size: 13px;"><?php echo htmlspecialchars($exercise['name']); ?></span>
                                    <span
                                        style="color: var(--hazard); font-family: 'Chakra Petch', sans-serif; font-weight: 700;"><?php echo $exercise['usage_count']; ?>
                                        logs</span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($top_exercises)): ?>
                                <div style="text-align: center; padding: 20px; color: var(--text-muted);">No workout logs
                                    yet</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Transactions & Feedback -->
        <section>
            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-receipt"></i> Recent Transactions
                        </h3>
                        <div class="table-responsive inventory-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="recentTransactionsTable">
                                    <?php foreach ($recent_transactions as $txn): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($txn['customer_name']); ?></td>
                                            <td>₱<?php echo number_format($txn['amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($txn['payment_method']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($txn['transaction_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_transactions)): ?>
                                        <tr>
                                            <td colspan="4" style="text-align: center; color: var(--text-muted);">No
                                                transactions</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="registration-card">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                            <i class="bi bi-chat-dots"></i> Recent Feedback
                        </h3>
                        <div id="recentFeedbackContainer" style="display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($recent_feedback as $fb): ?>
                                <?php
                                $statusClass = 'maintenance';
                                if ($fb['status'] === 'resolved')
                                    $statusClass = 'active';
                                if ($fb['status'] === 'closed')
                                    $statusClass = 'inactive';
                                ?>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border);">
                                    <span
                                        style="color: var(--text-primary); font-size: 13px;"><?php echo htmlspecialchars($fb['about']); ?></span>
                                    <span
                                        class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($fb['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($recent_feedback)): ?>
                                <div style="text-align: center; padding: 20px; color: var(--text-muted);">No feedback yet
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Chart.js Default Config
        Chart.defaults.color = '#999';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.07)';
        Chart.defaults.font.family = "'DM Sans', sans-serif";

        // Initialize Charts
        let netProfitChart, expenseCategoriesChart;

        // Net Profit Chart
        netProfitChart = new Chart(document.getElementById('netProfitChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($net_profit_by_month, 'month')); ?>,
                datasets: [
                    {
                        label: 'Revenue',
                        data: <?php echo json_encode(array_column($net_profit_by_month, 'revenue')); ?>,
                        backgroundColor: 'rgba(34, 208, 122, 0.7)',
                        borderColor: '#22d07a',
                        borderWidth: 1
                    },
                    {
                        label: 'Expenses',
                        data: <?php echo json_encode(array_column($net_profit_by_month, 'expenses')); ?>,
                        backgroundColor: 'rgba(255, 71, 87, 0.7)',
                        borderColor: '#ff4757',
                        borderWidth: 1
                    },
                    {
                        label: 'Net Profit',
                        data: <?php echo json_encode(array_column($net_profit_by_month, 'net')); ?>,
                        backgroundColor: 'rgba(255, 204, 0, 0.7)',
                        borderColor: '#FFCC00',
                        borderWidth: 2,
                        type: 'line'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Expense Categories Chart
        expenseCategoriesChart = new Chart(document.getElementById('expenseCategoriesChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($expense_categories, 'expense_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($expense_categories, 'total')); ?>,
                    backgroundColor: ['#ff4757', '#ff9f43', '#FFCC00', '#22d07a', '#17a2b8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, font: { size: 10 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        // Member Growth Chart
        memberGrowthChart = new Chart(document.getElementById('memberGrowthChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($member_growth, 'month')); ?>,
                datasets: [{
                    label: 'New Members',
                    data: <?php echo json_encode(array_column($member_growth, 'count')); ?>,
                    borderColor: '#FFCC00',
                    backgroundColor: 'rgba(255, 204, 0, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        // Revenue Chart
        revenueChart = new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($revenue_by_month, 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($revenue_by_month, 'total')); ?>,
                    backgroundColor: 'rgba(255, 204, 0, 0.7)',
                    borderColor: '#FFCC00',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Revenue: ₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Daily Check-ins Chart with Walk-ins
        checkinChart = new Chart(document.getElementById('checkinActivityChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($checkin_activity, 'day')); ?>,
                datasets: [
                    {
                        label: 'Member Check-ins',
                        data: <?php echo json_encode(array_column($checkin_activity, 'member_count')); ?>,
                        borderColor: '#22d07a',
                        backgroundColor: 'rgba(34, 208, 122, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2
                    },
                    {
                        label: 'Walk-in Check-ins',
                        data: <?php echo json_encode(array_column($checkin_activity, 'walkin_count')); ?>,
                        borderColor: '#ff4757',
                        backgroundColor: 'rgba(255, 71, 87, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11,
                                family: "'DM Sans', sans-serif"
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' check-ins';
                            },
                            footer: function (tooltipItems) {
                                let total = 0;
                                tooltipItems.forEach(function (tooltipItem) {
                                    total += tooltipItem.parsed.y;
                                });
                                return 'Total: ' + total + ' check-ins';
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                }
            }
        });

        // Payment Method Revenue Chart
        paymentMethodChart = new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($revenue_by_payment, 'payment_method')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($revenue_by_payment, 'total')); ?>,
                    backgroundColor: ['#FFCC00', '#22d07a', '#ff9f43', '#17a2b8', '#6c757d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 10, font: { size: 10 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ₱' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Real-time update function
        function updateDashboard() {
            fetch('get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    // Update basic stats
                    document.getElementById('stat-members').textContent = data.stats.total_members;
                    document.getElementById('stat-revenue').textContent = '₱' + parseFloat(data.stats.total_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-today-revenue').textContent = '₱' + parseFloat(data.stats.today_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-month-revenue').textContent = '₱' + parseFloat(data.stats.this_month_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-total-expenses').textContent = '₱' + parseFloat(data.stats.total_expenses).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-today-expenses').textContent = '₱' + parseFloat(data.stats.today_expenses).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-month-expenses').textContent = '₱' + parseFloat(data.stats.month_expenses).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    document.getElementById('stat-notifications').textContent = data.stats.unread_notifications;
                    //ATTENDANCE TRACK
                    document.getElementById('stat-today-member-attendance').textContent = data.stats.today_member_attendance;
                    document.getElementById('stat-members').textContent = data.stats.total_members;

                    // Update financial stats if elements exist
                    if (document.getElementById('stat-expenses')) {
                        document.getElementById('stat-expenses').textContent = '₱' + parseFloat(data.stats.total_expenses).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    }
                    if (document.getElementById('stat-net-profit')) {
                        document.getElementById('stat-net-profit').textContent = '₱' + parseFloat(data.stats.net_profit).toLocaleString('en-US', { minimumFractionDigits: 2 });
                    }
                    if (document.getElementById('stat-profit-margin')) {
                        document.getElementById('stat-profit-margin').textContent = parseFloat(data.stats.profit_margin).toFixed(1) + '%';
                    }

                    // Update charts
                    revenueChart.data.datasets[0].data = data.revenue_by_month.map(item => item.total);
                    revenueChart.update('none');

                    // Update check-in chart in the updateDashboard function
                    checkinChart.data.datasets[0].data = data.checkin_activity.map(item => item.member_count);
                    checkinChart.data.datasets[1].data = data.checkin_activity.map(item => item.walkin_count);
                    checkinChart.update('none');

                    paymentMethodChart.data.labels = data.revenue_by_payment.map(item => item.payment_method);
                    paymentMethodChart.data.datasets[0].data = data.revenue_by_payment.map(item => item.total);
                    paymentMethodChart.update('none');

                    // Update net profit chart if exists
                    if (typeof netProfitChart !== 'undefined') {
                        netProfitChart.data.datasets[0].data = data.net_profit_by_month.map(item => item.revenue);
                        netProfitChart.data.datasets[1].data = data.net_profit_by_month.map(item => item.expenses);
                        netProfitChart.data.datasets[2].data = data.net_profit_by_month.map(item => item.net);
                        netProfitChart.update('none');
                    }

                    // Update expense categories chart if exists
                    if (typeof expenseCategoriesChart !== 'undefined' && data.expense_categories.length > 0) {
                        expenseCategoriesChart.data.labels = data.expense_categories.map(item => item.expense_name);
                        expenseCategoriesChart.data.datasets[0].data = data.expense_categories.map(item => item.total);
                        expenseCategoriesChart.update('none');
                    }

                    // Update recent transactions
                    updateRecentTransactions(data.recent_transactions);

                    // Update last update time
                    document.getElementById('lastUpdate').textContent = 'Just now';
                })
                .catch(error => console.error('Update error:', error));
        }

        function updateRecentTransactions(transactions) {
            const tbody = document.getElementById('recentTransactionsTable');
            if (transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No transactions</td></tr>';
                return;
            }

            tbody.innerHTML = transactions.map(txn => `
                <tr>
                    <td>${txn.customer_name}</td>
                    <td>₱${parseFloat(txn.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td>${txn.payment_method}</td>
                    <td>${new Date(txn.transaction_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                </tr>
            `).join('');
        }

        // Update every 5 seconds
        setInterval(updateDashboard, 5000);

        // Update time ago every minute
        setInterval(() => {
            const lastUpdate = document.getElementById('lastUpdate');
            const currentText = lastUpdate.textContent;
            if (currentText === 'Just now') {
                lastUpdate.textContent = '1 min ago';
            } else if (currentText.includes('min ago')) {
                const mins = parseInt(currentText) + 1;
                lastUpdate.textContent = mins + ' min ago';
            }
        }, 60000);
    </script>
</body>

</html>