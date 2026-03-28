<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Add Expense
    if ($_POST['action'] === 'add') {
        $expense_name = trim($_POST['expense_name']);
        $quantity = (int)$_POST['quantity'];
        $unit_price = floatval($_POST['unit_price']);
        $notes = trim($_POST['notes']);
        $author = $_SESSION['username'];
        
        // Calculate total expense
        $total_expense = $quantity * $unit_price;
        
        // Build description
        $description = "Qty: $quantity × ₱" . number_format($unit_price, 2) . " = ₱" . number_format($total_expense, 2);
        if (!empty($notes)) {
            $description .= " | Notes: $notes";
        }
        
        $stmt = $pdo->prepare("INSERT INTO expense_history (expense_name, expense, description, author) VALUES (:name, :expense, :desc, :author)");
        $stmt->execute([
            'name' => $expense_name,
            'expense' => $total_expense,
            'desc' => $description,
            'author' => $author
        ]);
        
        // Log to notification history
        $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
        $notif->execute([
            'NEW EXPENSE ADDED',
            "Expense: $expense_name - ₱" . number_format($total_expense, 2) . " ($quantity units)",
            "Added by " . $author,
            'Finance'
        ]);
        
        header('Location: expenses.php?success=added');
        exit();
    }
    
    // Update Expense
    if ($_POST['action'] === 'update') {
        $expense_id = (int)$_POST['expense_id'];
        $expense_name = trim($_POST['expense_name']);
        $expense = floatval($_POST['expense']);
        $description = trim($_POST['description']);
        
        $stmt = $pdo->prepare("UPDATE expense_history SET expense_name = :name, expense = :expense, description = :desc, updated_at = CURRENT_TIMESTAMP WHERE expense_id = :id");
        $stmt->execute([
            'name' => $expense_name,
            'expense' => $expense,
            'desc' => $description,
            'id' => $expense_id
        ]);
        
        // Log to notification history
        $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
        $notif->execute([
            'EXPENSE UPDATED',
            "Updated expense: $expense_name - ₱" . number_format($expense, 2),
            "Modified by " . $_SESSION['username'],
            'Finance'
        ]);
        
        header('Location: expenses.php?success=updated');
        exit();
    }
}

header('Location: expenses.php');
exit();
?>