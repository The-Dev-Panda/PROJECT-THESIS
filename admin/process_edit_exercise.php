<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exercises.php');
    exit();
}

include('../Login/connection.php');

$exercise_id = isset($_POST['exercise_id']) ? (int)$_POST['exercise_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$target_muscle = isset($_POST['target_muscle']) ? trim($_POST['target_muscle']) : '';
$movement_type = isset($_POST['movement_type']) ? trim($_POST['movement_type']) : '';

$movement_types = ['push', 'pull', 'legs', 'cardio', 'other', 'arms', 'back', 'chest', 'core', 'shoulders'];

if ($exercise_id <= 0) {
    header('Location: edit_exercise.php?id=' . $exercise_id . '&error=' . urlencode('Invalid exercise ID.'));
    exit();
}

if ($name === '') {
    header('Location: edit_exercise.php?id=' . $exercise_id . '&error=' . urlencode('Exercise name is required.'));
    exit();
}

if (!in_array($movement_type, $movement_types, true)) {
    header('Location: edit_exercise.php?id=' . $exercise_id . '&error=' . urlencode('Invalid movement type: ' . $movement_type));
    exit();
}

try {
    $rowStmt = $pdo->prepare('SELECT * FROM exercises WHERE exercise_id = :id LIMIT 1');
    $rowStmt->execute(['id' => $exercise_id]);
    if (!$rowStmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: exercises.php?error=' . urlencode('Exercise not found.'));
        exit();
    }

    $duplicateStmt = $pdo->prepare('SELECT COUNT(*) FROM exercises WHERE lower(name) = lower(:name) AND exercise_id != :id');
    $duplicateStmt->execute(['name' => $name, 'id' => $exercise_id]);
    if ((int)$duplicateStmt->fetchColumn() > 0) {
        header('Location: edit_exercise.php?id=' . $exercise_id . '&error=' . urlencode('Another exercise has the same name.'));
        exit();
    }

    $updateStmt = $pdo->prepare('UPDATE exercises SET name = :name, target_muscle = :target_muscle, movement_type = :movement_type WHERE exercise_id = :id');
    $updateStmt->execute([
        'name' => $name,
        'target_muscle' => $target_muscle === '' ? null : $target_muscle,
        'movement_type' => $movement_type,
        'id' => $exercise_id
    ]);

    header('Location: exercises.php?success=' . urlencode('Exercise updated successfully.'));
    exit();
} catch (PDOException $e) {
    header('Location: edit_exercise.php?id=' . $exercise_id . '&error=' . urlencode('Database error when updating the exercise.'));
    exit();
}
