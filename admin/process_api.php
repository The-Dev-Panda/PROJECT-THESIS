<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

include("../Login/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Add API
    if ($_POST['action'] === 'add') {
        $api_name = trim($_POST['api_name']);
        $api_url = trim($_POST['api_url']);
        $api_key = trim($_POST['api_key']);
        
        $stmt = $pdo->prepare("INSERT INTO api_table (api_name, api_url, api_key, status) VALUES (:name, :url, :key, 'active')");
        $stmt->execute([
            'name' => $api_name,
            'url' => $api_url,
            'key' => $api_key
        ]);
        
        // Log to notification history
        $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
        $notif->execute([
            'NEW API ADDED',
            "API '$api_name' has been registered",
            "Added by " . $_SESSION['username'],
            'System'
        ]);
        
        header('Location: api_manage.php?success=added');
        exit();
    }
    
    // Update API
    if ($_POST['action'] === 'update') {
        $api_id = (int)$_POST['api_id'];
        $api_name = trim($_POST['api_name']);
        $api_url = trim($_POST['api_url']);
        $api_key = trim($_POST['api_key']);
        
        $stmt = $pdo->prepare("UPDATE api_table SET api_name = :name, api_url = :url, api_key = :key, updated_at = CURRENT_TIMESTAMP WHERE api_id = :id");
        $stmt->execute([
            'name' => $api_name,
            'url' => $api_url,
            'key' => $api_key,
            'id' => $api_id
        ]);
        
        // Log to notification history
        $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
        $notif->execute([
            'API UPDATED',
            "API '$api_name' has been modified",
            "Updated by " . $_SESSION['username'],
            'System'
        ]);
        
        header('Location: api_manage.php?success=updated');
        exit();
    }
}

header('Location: api_manage.php');
exit();
?>