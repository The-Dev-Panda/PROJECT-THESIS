<?php
try {
    $pdo = new PDO('sqlite:../Database/DB.sqlite');
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}
