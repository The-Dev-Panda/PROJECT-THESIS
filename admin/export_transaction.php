<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Get filter parameters
$from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? $_GET['from_date'] : null;
$to_date = isset($_GET['to_date']) && !empty($_GET['to_date']) ? $_GET['to_date'] : null;
$payment_method = isset($_GET['payment_method']) && !empty($_GET['payment_method']) ? $_GET['payment_method'] : null;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($from_date) {
    $where_conditions[] = "DATE(t.transaction_date) >= :from_date";
    $params['from_date'] = $from_date;
}

if ($to_date) {
    $where_conditions[] = "DATE(t.transaction_date) <= :to_date";
    $params['to_date'] = $to_date;
}

if ($payment_method) {
    $where_conditions[] = "t.payment_method = :payment_method";
    $params['payment_method'] = $payment_method;
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all transactions
$query = "SELECT 
            t.id,
            t.receipt_number,
            COALESCE(NULLIF(t.customer_name, ''), CONCAT_WS(' ', u_member.first_name, u_member.last_name), u_member.username, CONCAT('Member #', t.user_id), 'Walk-In') AS customer_name,
            t.customer_type,
            t.amount,
            t.payment_method,
            t.transaction_date,
            t.`desc`,
            u_staff.first_name AS staff_first_name,
            u_staff.last_name AS staff_last_name
          FROM transactions t
          LEFT JOIN users u_member ON t.user_id = u_member.id
          LEFT JOIN users u_staff ON t.staff_id = u_staff.id
          $where_clause
          ORDER BY t.transaction_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals
$total_amount = 0;
$transaction_count = count($transactions);
foreach ($transactions as $txn) {
    $total_amount += $txn['amount'];
}

// Build filename
$filename = 'FITSTOP_Transactions_';
if ($from_date && $to_date) {
    $filename .= date('Ymd', strtotime($from_date)) . '_to_' . date('Ymd', strtotime($to_date));
} elseif ($from_date) {
    $filename .= 'from_' . date('Ymd', strtotime($from_date));
} elseif ($to_date) {
    $filename .= 'until_' . date('Ymd', strtotime($to_date));
} else {
    $filename .= 'All_Time';
}
$filename .= '_' . date('YmdHis') . '.xls';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output Excel content
echo '<?xml version="1.0"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
    xmlns:html="http://www.w3.org/TR/REC-html40">';

// Styles
echo '<Styles>';

// Header style
echo '<Style ss:ID="header">';
echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>';
echo '<Interior ss:Color="#FFCC00" ss:Pattern="Solid"/>';
echo '<Borders>';
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2"/>';
echo '</Borders>';
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
echo '</Style>';

// Title style
echo '<Style ss:ID="title">';
echo '<Font ss:Bold="1" ss:Size="16" ss:Color="#000000"/>';
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
echo '</Style>';

// Subtitle style
echo '<Style ss:ID="subtitle">';
echo '<Font ss:Size="11" ss:Color="#666666"/>';
echo '<Alignment ss:Horizontal="Center" ss:Vertical="Center"/>';
echo '</Style>';

// Total style
echo '<Style ss:ID="total">';
echo '<Font ss:Bold="1" ss:Size="12" ss:Color="#FFFFFF"/>';
echo '<Interior ss:Color="#22d07a" ss:Pattern="Solid"/>';
echo '<Alignment ss:Horizontal="Right" ss:Vertical="Center"/>';
echo '</Style>';

// Currency style
echo '<Style ss:ID="currency">';
echo '<NumberFormat ss:Format="&quot;₱&quot;#,##0.00"/>';
echo '</Style>';

// Date style
echo '<Style ss:ID="dateStyle">';
echo '<NumberFormat ss:Format="mmm dd, yyyy hh:mm AM/PM"/>';
echo '</Style>';

echo '</Styles>';

echo '<Worksheet ss:Name="Transactions">';
echo '<Table>';

// Column widths
echo '<Column ss:Width="50"/>';   // ID
echo '<Column ss:Width="120"/>'; // Receipt
echo '<Column ss:Width="150"/>'; // Customer
echo '<Column ss:Width="100"/>'; // Type
echo '<Column ss:Width="100"/>'; // Amount
echo '<Column ss:Width="120"/>'; // Payment
echo '<Column ss:Width="150"/>'; // Date
echo '<Column ss:Width="120"/>'; // Staff
echo '<Column ss:Width="250"/>'; // Description

// Title row
echo '<Row ss:Height="25">';
echo '<Cell ss:MergeAcross="8" ss:StyleID="title"><Data ss:Type="String">FITSTOP TRANSACTION REPORT</Data></Cell>';
echo '</Row>';

// Subtitle row with date range
echo '<Row ss:Height="18">';
echo '<Cell ss:MergeAcross="8" ss:StyleID="subtitle"><Data ss:Type="String">';
if ($from_date && $to_date) {
    echo 'Period: ' . date('F d, Y', strtotime($from_date)) . ' to ' . date('F d, Y', strtotime($to_date));
} elseif ($from_date) {
    echo 'From: ' . date('F d, Y', strtotime($from_date));
} elseif ($to_date) {
    echo 'Until: ' . date('F d, Y', strtotime($to_date));
} else {
    echo 'All Transactions';
}
echo '</Data></Cell>';
echo '</Row>';

// Generated date row
echo '<Row ss:Height="16">';
echo '<Cell ss:MergeAcross="8" ss:StyleID="subtitle"><Data ss:Type="String">Generated: ' . date('F d, Y h:i A') . ' by ' . $_SESSION['username'] . '</Data></Cell>';
echo '</Row>';

// Empty row
echo '<Row ss:Height="10"><Cell></Cell></Row>';

// Header row
echo '<Row ss:Height="20">';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Receipt Number</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Customer Name</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Type</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Amount</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Payment Method</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Transaction Date</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Staff</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Description</Data></Cell>';
echo '</Row>';

// Data rows
foreach ($transactions as $txn) {
    $staff_name = trim(($txn['first_name'] ?? '') . ' ' . ($txn['last_name'] ?? ''));
    if (empty($staff_name)) {
        $staff_name = 'N/A';
    }
    
    echo '<Row>';
    echo '<Cell><Data ss:Type="Number">' . $txn['id'] . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($txn['receipt_number']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($txn['customer_name']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($txn['customer_type']) . '</Data></Cell>';
    echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $txn['amount'] . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($txn['payment_method']) . '</Data></Cell>';
    // Fix DateTime format for Excel
    $excelDate = date('Y-m-d\TH:i:s', strtotime($txn['transaction_date']));
    echo '<Cell ss:StyleID="dateStyle"><Data ss:Type="DateTime">' . $excelDate . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($staff_name) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($txn['desc'] ?? '') . '</Data></Cell>';
    echo '</Row>';
}

// Empty row before summary
echo '<Row ss:Height="10"><Cell></Cell></Row>';

// Summary rows
echo '<Row>';
echo '<Cell ss:MergeAcross="3" ss:StyleID="total"><Data ss:Type="String">TOTAL TRANSACTIONS</Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="Number">' . $transaction_count . '</Data></Cell>';
echo '<Cell ss:MergeAcross="3"></Cell>';
echo '</Row>';

echo '<Row>';
echo '<Cell ss:MergeAcross="3" ss:StyleID="total"><Data ss:Type="String">TOTAL AMOUNT</Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="Number">' . $total_amount . '</Data></Cell>';
echo '<Cell ss:MergeAcross="3"></Cell>';
echo '</Row>';

// Payment method breakdown
if (!$payment_method) {
    echo '<Row ss:Height="10"><Cell></Cell></Row>';
    echo '<Row>';
    echo '<Cell ss:MergeAcross="8" ss:StyleID="subtitle"><Data ss:Type="String">BREAKDOWN BY PAYMENT METHOD</Data></Cell>';
    echo '</Row>';
    
    $payment_breakdown = $pdo->prepare("SELECT payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM transactions t $where_clause GROUP BY payment_method");
    $payment_breakdown->execute($params);
    $breakdown_data = $payment_breakdown->fetchAll();
    
    foreach ($breakdown_data as $breakdown) {
        echo '<Row>';
        echo '<Cell ss:MergeAcross="2"><Data ss:Type="String">' . htmlspecialchars($breakdown['payment_method']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . $breakdown['count'] . ' transactions</Data></Cell>';
        echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $breakdown['total'] . '</Data></Cell>';
        echo '<Cell ss:MergeAcross="3"></Cell>';
        echo '</Row>';
    }
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
exit();
?>