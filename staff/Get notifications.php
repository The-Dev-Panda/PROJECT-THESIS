<?php
session_start();
require_once '../login/connection.php';
header('Content-Type: application/json');

try {
    $txStmt = $pdo->query("
        SELECT
            id,
            receipt_number,
            customer_type,
            user_id,
            customer_name,
            amount,
            payment_method,
            staff_id,
            transaction_date,
            status,
            created_at,
            `desc`
        FROM transactions
        ORDER BY transaction_date DESC, created_at DESC
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