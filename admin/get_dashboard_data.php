<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    header('location: ../Login/Login_Page.php');
    exit();
}

require_once("../Login/connection.php");

try {
    // Basic Stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
    $total_members = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'staff'");
    $total_staff = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions");
    $total_revenue = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notification_history WHERE is_read = 0");
    $unread_notifications = $stmt->fetch()['total'];

    // Member check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $today_member_attendance = $stmt->fetch()['count'];

    // Walk-in check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM walk_attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $today_walk_attendance = $stmt->fetch()['count'];
    
    // Financial Stats
    $stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history");
    $total_expenses = $stmt->fetch()['total'];

    $net_profit = $total_revenue - $total_expenses;
    $profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;

    // Today's Stats
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE DATE(transaction_date) = DATE('now')");
    $today_revenue = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE DATE(created_at) = DATE('now')");
    $today_expenses = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
    $this_month_revenue = $stmt->fetch()['total'];

    // Revenue by Month (6 months)
    $revenue_by_month = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = :date");
        $stmt->execute(['date' => $date]);
        $revenue_by_month[] = [
            'month' => date('M', strtotime("-$i months")),
            'total' => $stmt->fetch()['total']
        ];
    }

    // Net Profit by Month (6 months)
    $net_profit_by_month = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = :date");
        $stmt->execute(['date' => $date]);
        $monthly_revenue = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE strftime('%Y-%m', created_at) = :date");
        $stmt->execute(['date' => $date]);
        $monthly_expenses = $stmt->fetch()['total'];

        $net_profit_by_month[] = [
            'month' => date('M', strtotime("-$i months")),
            'revenue' => $monthly_revenue,
            'expenses' => $monthly_expenses,
            'net' => $monthly_revenue - $monthly_expenses
        ];
    }
    // Total Expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history");
    $total_expenses = $stmt->fetch()['total'];

    // Today's Expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE DATE(created_at) = DATE('now')");
    $today_expenses = $stmt->fetch()['total'];

    // This Month's Expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(expense), 0) as total FROM expense_history WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
    $month_expenses = $stmt->fetch()['total'];

    // Check-in Activity (7 days) - Members and Walk-ins
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

    // Revenue by Payment Method
    $stmt = $pdo->query("SELECT payment_method, COALESCE(SUM(amount), 0) as total FROM transactions GROUP BY payment_method");
    $revenue_by_payment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Expense Categories (Top 5)
    $stmt = $pdo->query("SELECT expense_name, SUM(expense) as total FROM expense_history GROUP BY expense_name ORDER BY total DESC LIMIT 5");
    $expense_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Transactions
    $stmt = $pdo->query("SELECT customer_name, amount, payment_method, transaction_date FROM transactions ORDER BY transaction_date DESC LIMIT 5");
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Backup Stats
    $stmt = $pdo->query("SELECT * FROM backup_history ORDER BY backup_date DESC LIMIT 1");
    $last_backup = $stmt->fetch();

    echo json_encode([
        'stats' => [
            'total_members' => $total_members,
            'total_staff' => $total_staff,
            'total_revenue' => $total_revenue,
            'unread_notifications' => $unread_notifications,
            'net_profit' => $net_profit,
            'profit_margin' => $profit_margin,
            'today_revenue' => $today_revenue,
            'this_month_revenue' => $this_month_revenue,
            'total_expenses' => $total_expenses,
            'today_expenses' => $today_expenses,
            'month_expenses' => $month_expenses,
            'today_walk_attendance' => $today_walk_attendance,
            'today_member_attendance' => $today_member_attendance
        ],
        'revenue_by_month' => $revenue_by_month,
        'net_profit_by_month' => $net_profit_by_month,
        'checkin_activity' => $checkin_activity,
        'revenue_by_payment' => $revenue_by_payment,
        'expense_categories' => $expense_categories,
        'recent_transactions' => $recent_transactions,
        'last_backup' => $last_backup
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>