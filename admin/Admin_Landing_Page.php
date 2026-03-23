<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
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

// Unread Notifications
$stmt = $pdo->query("SELECT COUNT(*) as total FROM notification_history WHERE is_read = 0");
$stats['unread_notifications'] = $stmt->fetch()['total'];

// Member Growth (last 6 months)
$member_growth = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'user' AND strftime('%Y-%m', created_at) = :date");
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
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = :date");
    $stmt->execute(['date' => $date]);
    $total = $stmt->fetch()['total'];
    $revenue_by_month[] = [
        'month' => date('M', strtotime("-$i months")),
        'total' => $total
    ];
}

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
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $count = $stmt->fetch()['count'];
    $checkin_activity[] = [
        'day' => date('D', strtotime("-$i days")),
        'count' => $count
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

// Payment Methods Distribution
$stmt = $pdo->query("SELECT payment_method, COUNT(*) as count FROM transactions GROUP BY payment_method");
$payment_methods = $stmt->fetchAll();

// Recent Transactions
$stmt = $pdo->query("SELECT customer_name, amount, payment_method, transaction_date FROM transactions ORDER BY transaction_date DESC LIMIT 5");
$recent_transactions = $stmt->fetchAll();

// User Verification Status
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as not_verified
    FROM users WHERE user_type = 'user'");
$verification = $stmt->fetch();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Analytics Dashboard - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../staff/staff.css">

    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
                <p>Real-time insights and performance metrics</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <div class="topbar-dot"></div>
                    <span>Live Data</span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <a href="view_members.php" style="text-decoration: none; color: inherit;">
                <div class="stat-box">
                    <div class="stat-icon members">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $stats['total_members']; ?></div>
                        <div class="stat-label">Total Members</div>
                    </div>
                </div>
            </a>

            <a href="view_staff.php" style="text-decoration: none; color: inherit;">
                <div class="stat-box">
                    <div class="stat-icon registrations">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $stats['total_staff']; ?></div>
                        <div class="stat-label">Active Staff</div>
                    </div>
                </div>
            </a>

            <a href="transactions.php" style="text-decoration: none; color: inherit;">
                <div class="stat-box">
                    <div class="stat-icon equipment">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <div class="stat-value">₱<?php echo number_format($stats['total_revenue'], 0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </a>

            <a href="notification.php" style="text-decoration: none; color: inherit;">
                <div class="stat-box">
                    <div class="stat-icon notifications">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $stats['unread_notifications']; ?></div>
                        <div class="stat-label">Unread Notifications</div>
                    </div>
                </div>
            </a>
        </div>
        </section>
        <!-- Charts Row 1 -->
        <section>
            <h2><i class="bi bi-bar-chart-line"></i> Growth & Revenue Trends</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Member Growth Chart -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-graph-up-arrow"></i> Member Growth (6 Months)
                    </h3>
                    <div style="position: relative; height: 250px;">
                        <canvas id="memberGrowthChart"></canvas>
                    </div>
                </div>

                <!-- Revenue Chart -->
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
        </section>

        <!-- Charts Row 2 -->
        <section>
            <h2><i class="bi bi-activity"></i> Activity & Usage</h2>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                <!-- Daily Check-ins -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-calendar-check"></i> Daily Check-ins (Last 7 Days)
                    </h3>
                    <canvas id="checkinActivityChart" style="max-height: 250px;"></canvas>
                </div>

                <!-- User Verification Pie -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-shield-check"></i> Member Verification
                    </h3>
                    <canvas id="verificationChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </section>

        <!-- Data Tables Row -->
        <section>
            <h2><i class="bi bi-table"></i> Recent Activity & Insights</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Low Stock Items -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-exclamation-triangle"></i> Low Stock Alert
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($low_stock as $item): ?>
                            <div
                                style="display: flex; justify-content: space-between; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border);">
                                <span
                                    style="color: var(--text-primary); font-size: 13px;"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                <span class="status-badge low-stock"><?php echo $item['quantity']; ?> left</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($low_stock)): ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-muted);">All items well stocked
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Exercises -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-trophy"></i> Most Popular Exercises
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
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
                            <div style="text-align: center; padding: 20px; color: var(--text-muted);">No workout logs yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Transactions & Feedback -->
        <section>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <!-- Recent Transactions -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-receipt"></i> Recent Transactions
                    </h3>
                    <div class="inventory-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
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

                <!-- Recent Feedback -->
                <div class="registration-card">
                    <h3
                        style="font-family: 'Chakra Petch', sans-serif; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--border);">
                        <i class="bi bi-chat-dots"></i> Recent Feedback
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
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
                            <div style="text-align: center; padding: 20px; color: var(--text-muted);">No feedback yet</div>
                        <?php endif; ?>
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

        // Member Growth Chart
        new Chart(document.getElementById('memberGrowthChart'), {
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
        new Chart(document.getElementById('revenueChart'), {
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
        // Daily Check-ins Chart
        console.log('Check-in data:', <?php echo json_encode($checkin_activity); ?>);

        new Chart(document.getElementById('checkinActivityChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($checkin_activity, 'day')); ?>,
                datasets: [{
                    label: 'Member Check-ins',
                    data: <?php echo json_encode(array_column($checkin_activity, 'count')); ?>,
                    borderColor: '#22d07a',
                    backgroundColor: 'rgba(34, 208, 122, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: { display: true }
                }
            }
        });

        // Verification Pie Chart
        new Chart(document.getElementById('verificationChart'), {
            type: 'doughnut',
            data: {
                labels: ['Verified', 'Not Verified'],
                datasets: [{
                    data: [<?php echo $verification['verified']; ?>, <?php echo $verification['not_verified']; ?>],
                    backgroundColor: ['#22d07a', '#ff9f43'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, font: { size: 11 } }
                    }
                }
            }
        });
    </script>
</body>

</html>