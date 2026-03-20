<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_exercise.php');
    exit();
}

include('../Login/connection.php');

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$target_muscle = isset($_POST['target_muscle']) ? trim($_POST['target_muscle']) : '';
$movement_type = isset($_POST['movement_type']) ? trim($_POST['movement_type']) : '';

$movement_types = ['strength', 'cardio', 'hypertrophy', 'flexibility', 'mobility', 'other'];

if ($name === '') {
    header('Location: add_exercise.php?error=' . urlencode('Exercise name is required.'));
    exit();
}

if (!in_array($movement_type, $movement_types, true)) {
    header('Location: add_exercise.php?error=' . urlencode('Invalid movement type.'));
    exit();
}

try {
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM exercises WHERE lower(name) = lower(:name)');
    $checkStmt->execute(['name' => $name]);
    if ((int)$checkStmt->fetchColumn() > 0) {
        header('Location: add_exercise.php?error=' . urlencode('Exercise name already exists.'));
        exit();
    }

    $insertStmt = $pdo->prepare('INSERT INTO exercises (name, target_muscle, movement_type) VALUES (:name, :target_muscle, :movement_type)');
    $insertStmt->execute([
        'name' => $name,
        'target_muscle' => $target_muscle === '' ? null : $target_muscle,
        'movement_type' => $movement_type
    ]);

    header('Location: exercises.php?success=' . urlencode('Exercise added successfully.'));
    exit();
} catch (PDOException $e) {
    header('Location: add_exercise.php?error=' . urlencode('Database error when adding exercise.'));
    exit();
}
