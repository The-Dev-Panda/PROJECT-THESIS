<?php
require __DIR__ . '/../Login/connection.php';

$data = json_decode(file_get_contents("php://input"), true);

try {
    $stmt = $pdo->prepare("
        INSERT INTO workout_logs (user_id, exercise_id, sets, reps, weight, logged_at)
        VALUES (:uid, :eid, :sets, :reps, :weight, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        ':uid' => 1, // Replace with actual user ID
        ':eid' => $data['exercise_id'],
        ':sets' => (int)$data['sets'],
        ':reps' => (int)$data['reps'],
        ':weight' => (int)$data['weight']
    ]);

    echo json_encode(["status" => "ok"]);

} catch(Throwable $e) {
    echo json_encode(["status" => "error", "error" => $e->getMessage()]);
}
?>