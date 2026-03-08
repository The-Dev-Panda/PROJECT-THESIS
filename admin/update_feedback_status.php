<?php
session_start();

// Check if user is admin
if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate inputs
$feedbackId = isset($input['id']) ? intval($input['id']) : 0;
$newStatus = isset($input['status']) ? trim($input['status']) : '';
$adminResponse = isset($input['admin_response']) ? trim($input['admin_response']) : null;

if ($feedbackId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit();
}

// Validate status
$validStatuses = ['pending', 'in_progress', 'resolved', 'closed'];
if (!in_array($newStatus, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    include('../Login/connection.php');
    
    // Update feedback status
    $stmt = $pdo->prepare("
        UPDATE feedback 
        SET status = :status,
            admin_response = :admin_response,
            updated_at = datetime('now')
        WHERE id = :id
    ");
    
    $stmt->execute([
        'status' => $newStatus,
        'admin_response' => $adminResponse,
        'id' => $feedbackId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Feedback status updated successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Feedback not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Feedback status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
