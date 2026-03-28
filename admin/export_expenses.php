<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location:../LoginLogin_Page.php');
    exit();
}

include("../Login/connection.php");

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$time_filter = isset($_GET['time']) ? $_GET['time'] : '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(expense_name LIKE :search OR description LIKE :search OR author LIKE :search)";
    $params['search'] = "%$search%";
}

if ($time_filter) {
    switch ($time_filter) {
        case 'today':
            $where_conditions[] = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "created_at >= NOW() - INTERVAL 7 DAY";
            break;
        case 'month':
            $where_conditions[] = "created_at >= NOW() - INTERVAL 30 DAY";
            break;
        case 'year':
            $where_conditions[] = "created_at >= NOW() - INTERVAL 365 DAY";
            break;
    }
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all expenses
$stmt = $pdo->prepare("SELECT * FROM expense_history $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($expenses as $expense) {
    $total += $expense['expense'];
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="FITSTOP_Expenses_' . date('Y-m-d_His') . '.xls"');
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

echo '<Styles>';
echo '<Style ss:ID="header">';
echo '<Font ss:Bold="1" ss:Size="12"/>';
echo '<Interior ss:Color="#FFCC00" ss:Pattern="Solid"/>';
echo '<Borders>';
echo '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>';
echo '</Borders>';
echo '</Style>';
echo '<Style ss:ID="total">';
echo '<Font ss:Bold="1" ss:Size="11"/>';
echo '<Interior ss:Color="#FF4757" ss:Pattern="Solid"/>';
echo '<Font ss:Color="#FFFFFF"/>';
echo '</Style>';
echo '<Style ss:ID="currency">';
echo '<NumberFormat ss:Format="&quot;₱&quot;#,##0.00"/>';
echo '</Style>';
echo '</Styles>';

echo '<Worksheet ss:Name="Expenses">';
echo '<Table>';

// Column widths
echo '<Column ss:Width="50"/>';
echo '<Column ss:Width="200"/>';
echo '<Column ss:Width="100"/>';
echo '<Column ss:Width="300"/>';
echo '<Column ss:Width="120"/>';
echo '<Column ss:Width="150"/>';

// Header row
echo '<Row>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">ID</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Expense Name</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Amount</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Description</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Added By</Data></Cell>';
echo '<Cell ss:StyleID="header"><Data ss:Type="String">Date</Data></Cell>';
echo '</Row>';

// Data rows
foreach ($expenses as $expense) {
    echo '<Row>';
    echo '<Cell><Data ss:Type="Number">' . $expense['expense_id'] . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($expense['expense_name']) . '</Data></Cell>';
    echo '<Cell ss:StyleID="currency"><Data ss:Type="Number">' . $expense['expense'] . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($expense['description'] ?? '') . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . htmlspecialchars($expense['author']) . '</Data></Cell>';
    echo '<Cell><Data ss:Type="String">' . date('Y-m-d H:i:s', strtotime($expense['created_at'])) . '</Data></Cell>';
    echo '</Row>';
}

// Total row
echo '<Row>';
echo '<Cell ss:StyleID="total"><Data ss:Type="String"></Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="String">TOTAL</Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="Number">' . $total . '</Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="String"></Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="String"></Data></Cell>';
echo '<Cell ss:StyleID="total"><Data ss:Type="String">' . date('Y-m-d H:i:s') . '</Data></Cell>';
echo '</Row>';

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';
exit();
?>