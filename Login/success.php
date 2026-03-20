<?php
session_start();

if (empty($_SESSION['username'])) {
    session_destroy();
    header('Location: Login_Page.php');
    exit();
}

$userType = strtolower((string)($_SESSION['user_type'] ?? ''));
$memberId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

if ($userType === 'admin') {
    header('Location: ../admin/Admin_Landing_Page.php');
    exit();
}

if ($userType === 'user') {
    // Enforce Data Privacy Act consent before granting dashboard access
    include('connection.php');
    $dpaConsented = false;
    if ($memberId > 0) {
        try {
            $stmt = $pdo->prepare('SELECT dpa_consent FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $memberId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['dpa_consent'])) {
                $dpaConsented = true;
            }
        } catch (PDOException $e) {
            // Missing field or DB issue means enforce consent page to be safe
            $dpaConsented = false;
        }
    }
    if (!$dpaConsented) {
        header('Location: DPA_Consent.php');
        exit();
    }

    $target = '../user/user.html';
    if ($memberId > 0) {
        $target .= '?member_ref=' . urlencode((string)$memberId);
    }
    header('Location: ' . $target);
    exit();
}

if ($userType === 'staff') {
    header('Location: ../staff/staff.php');
    exit();
}

session_destroy();
header('Location: Login_Page.php');
exit();
?>