<?php
header('Content-Type: application/json');

$dbPath = __DIR__ . '/DB.sqlite';

try {
    if (!file_exists($dbPath)) {
        throw new Exception('Database file not found');
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT exercise_id, name, target_muscle, movement_type FROM exercises ORDER BY name ASC");

    $exercises = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $exercises[] = [
            'exercise_id' => (int) $row['exercise_id'],
            'name' => $row['name'],
            'target_muscle' => $row['target_muscle'],
            'movement_type' => $row['movement_type']
        ];
    }

    echo json_encode([
        'success' => true,
        'exercises' => $exercises
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'exercises' => [],
        'error' => $e->getMessage()
    ]);
}
