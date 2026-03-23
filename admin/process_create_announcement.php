<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $created_by = $_SESSION['username'];
    $image_data = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        $file_type = $_FILES['image']['type'];

        $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($file_type, $allowed_types)) {
            header('Location: create_announcement.php?error=invalid_type');
            exit();
        }
        if ($file_size > 5 * 1024 * 1024) {
            header('Location: create_announcement.php?error=file_too_large');
            exit();
        }
        $image_data = file_get_contents($file_tmp);
    }
    try {
        include("../Login/connection.php");
        $stmt = $pdo->prepare("INSERT INTO announcements (title, description, image, created_by) VALUES (:title, :description, :image, :created_by)");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'image' => $image_data,
            'created_by' => $created_by
        ]);
        $stmt = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (:name, :description, :remarks, :category)");
        $stmt->execute([
            'name' => 'ANNOUNCEMENT CREATED',
            'description' => $description,
            'remarks' => $created_by,
            'category' => 'Announcements'
        ]);

        header('Location: create_announcement.php?success=created');
        exit();

    } catch (PDOException $e) {
        header('Location: create_announcement.php?error=database');
        exit();
    }
} else {
    header('Location: create_announcement.php');
    exit();
}