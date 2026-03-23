<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id']) || (int)$_GET['id'] <= 0) {
    header('Location: exercises.php?error=' . urlencode('Invalid exercise ID.'));
    exit();
}

$exercise_id = (int)$_GET['id'];
include('../Login/connection.php');

try {
    $rowStmt = $pdo->prepare('SELECT * FROM exercises WHERE exercise_id = :id LIMIT 1');
    $rowStmt->execute(['id' => $exercise_id]);
    if (!$rowStmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: exercises.php?error=' . urlencode('Exercise not found.'));
        exit();
    }

    $dependentStmt = $pdo->prepare('SELECT COUNT(*) FROM workout_logs WHERE exercise_id = :id');
    $dependentStmt->execute(['id' => $exercise_id]);
    if ((int)$dependentStmt->fetchColumn() > 0) {
        header('Location: exercises.php?error=' . urlencode('Cannot delete exercise with logged workout history.'));
        exit();
    }
    $deleteStmt = $pdo->prepare('DELETE FROM exercises WHERE exercise_id = :id');
    $deleteStmt->execute(['id' => $exercise_id]);
    

    header('Location: exercises.php?success=' . urlencode('Exercise deleted successfully.'));
    exit();
} catch (PDOException $e) {
    header('Location: exercises.php?error=' . urlencode('Database error while deleting exercise.'));
    exit();
}
