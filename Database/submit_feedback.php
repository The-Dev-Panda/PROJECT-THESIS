<?php
require_once '../Login/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$desc = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
$about = isset($_POST['machine']) ? trim($_POST['machine']) : 'General';
$reporterID = isset($_POST['reporterID']) ? intval($_POST['reporterID']) : 1; // Default to 1 if not provided
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : 'Anonymous';

if (empty($desc)) {
    echo json_encode(['success' => false, 'message' => 'Feedback cannot be empty']);
    exit();
}

if (strlen($desc) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Feedback is too long (max 1000 characters)']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO feedback (about, reporterID, last_name, created_at, desc) VALUES (:about, :reporterID, :last_name, datetime('now'), :desc)");
    
    $stmt->bindParam(':about', $about, PDO::PARAM_STR);
    $stmt->bindParam(':reporterID', $reporterID, PDO::PARAM_INT);
    $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
    $stmt->bindParam(':desc', $desc, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your feedback!',
            'about' => $about
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
