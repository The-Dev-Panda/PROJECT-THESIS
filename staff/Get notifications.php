<?php
session_start();
require_once '../login/connection.php';
header('Content-Type: application/json');

try {
    $txStmt = $pdo->query("
        SELECT
            t.id,
            t.receipt_number,
            t.customer_type,
            t.user_id,
            COALESCE(NULLIF(t.customer_name, ''), CONCAT_WS(' ', u.first_name, u.last_name), u.username, CONCAT('Member #', t.user_id), 'Walk-In') AS customer_name,
            t.amount,
            t.payment_method,
            t.staff_id,
            t.transaction_date,
            t.status,
            t.created_at,
            t.`desc`
        FROM transactions t
        LEFT JOIN users u ON u.id = t.user_id
        ORDER BY t.transaction_date DESC, t.created_at DESC
        LIMIT 30
    ");
    $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

    $stockStmt = $pdo->query("
        SELECT
            id,
            item_name,
            category,
            quantity,
            price,
            description,
            created_at,
            updated_at
        FROM inventory
        WHERE quantity <= 10
        ORDER BY quantity ASC, updated_at DESC
    ");
    $lowStock = $stockStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'      => true,
        'transactions' => $transactions,
        'low_stock'    => $lowStock,
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage(),
    ]);
}