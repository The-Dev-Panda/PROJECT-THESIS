<?php
require_once __DIR__ . '/../includes/security.php';

// Debug mode: true to show all internal info
$debug = false;

// Process login form submission
if (isset($_POST["username"])) {

    if ($debug) {
        echo "<pre>=== Debug Login Process ===\n";
        echo "Raw POST data:\n";
        print_r($_POST);
        echo "Raw input stream:\n";
        echo file_get_contents('php://input') . "\n";
        echo "</pre>";
    }

    // CSRF validation
    try {
        fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
        if ($debug) echo "CSRF validation passed.\n";
    } catch (Exception $e) {
        if ($debug) {
            echo "CSRF validation failed: " . $e->getMessage() . "\n";
        }
        header('Location: Login_Page.php?c=csrf');
        exit();
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');

    include("connection.php"); // MySQL PDO

    try {
        // Fetch user by username
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($debug) {
            echo "<pre>Fetched user:\n";
            print_r($user);
            echo "</pre>";
        }

        if ($user) {
            // Verify password
            $password_ok = password_verify($password, $user['password']);
            if ($debug) echo "Password verification: " . ($password_ok ? "TRUE" : "FALSE") . "\n";

            if ($password_ok) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                    if ($debug) echo "Session started.\n";
                }

                session_regenerate_id(true);
                $_SESSION['username'] = $username;
                $_SESSION['id'] = (int)$user['id'];
                $_SESSION['user_type'] = $user['user_type'];

                if ($debug) {
                    echo "<pre>Session variables:\n";
                    print_r($_SESSION);
                    echo "</pre>";
                }

                // Update last_logged_in
                $update_login = $pdo->prepare('UPDATE users SET last_logged_in = :last_logged_in WHERE username = :username');
                $update_login->execute([
                    'last_logged_in' => $now,
                    'username' => $username
                ]);

                if ($debug) echo "Updated last_logged_in timestamp: $now\n";
                
                if (!$debug) header('Location: success.php');
                exit();
            } else {
                if ($debug) echo "Password incorrect.\n";
                if (!$debug) header('Location: Login_Page.php?c=false');
                exit();
            }
        } else {
            if ($debug) echo "No user found with username: $username\n";
            if (!$debug) header('Location: Login_Page.php?c=false');
            exit();
        }
    } catch (PDOException $e) {
        if ($debug) echo "Database error: " . $e->getMessage() . "\n";
        if (!$debug) header('Location: Login_Page.php?c=false');
        exit();
    }
} else {
    if ($debug) echo "No username posted.\n";
}