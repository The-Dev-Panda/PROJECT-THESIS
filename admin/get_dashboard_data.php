<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

date_default_timezone_set('Asia/Manila');
include("../Login/connection.php");

header('Content-Type: application/json');

$data = [];

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
$data['stats']['total_members'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'staff'");
$data['stats']['total_staff'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions");
$data['stats']['total_revenue'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM notification_history WHERE is_read = 0");
$data['stats']['unread_notifications'] = $stmt->fetch()['total'];

// Revenue by Month (last 6 months)
$data['revenue_by_month'] = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE strftime('%Y-%m', transaction_date) = :date");
    $stmt->execute(['date' => $date]);
    $total = $stmt->fetch()['total'];
    $data['revenue_by_month'][] = [
        'month' => date('M', strtotime("-$i months")),
        'total' => $total
    ];
}

// Daily Check-ins (last 7 days)
$data['checkin_activity'] = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance WHERE DATE(datetime) = :date");
    $stmt->execute(['date' => $date]);
    $count = $stmt->fetch()['count'];
    $data['checkin_activity'][] = [
        'day' => date('D', strtotime("-$i days")),
        'count' => $count
    ];
}

// Revenue by Payment Method
$stmt = $pdo->query("SELECT payment_method, COALESCE(SUM(amount), 0) as total FROM transactions GROUP BY payment_method");
$data['revenue_by_payment'] = $stmt->fetchAll();

// Recent Transactions
$stmt = $pdo->query("SELECT customer_name, amount, payment_method, transaction_date FROM transactions ORDER BY transaction_date DESC LIMIT 5");
$data['recent_transactions'] = $stmt->fetchAll();

echo json_encode($data);
?>