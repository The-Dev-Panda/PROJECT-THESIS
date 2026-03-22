<?php
session_start();
if (empty($_SESSION['username']) || strtolower((string)($_SESSION['user_type'] ?? '')) !== 'user') {
    header('Location: Login_Page.php');
    exit();
}
$memberId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
try {
    include('connection.php');
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasConsent = false;
    $hasConsentAt = false;
    foreach ($columns as $col) {
        if (strtolower($col['name']) === 'dpa_consent') $hasConsent = true;
        if (strtolower($col['name']) === 'dpa_consent_at') $hasConsentAt = true;
    }
    if (!$hasConsent) $pdo->exec('ALTER TABLE users ADD COLUMN dpa_consent INTEGER NOT NULL DEFAULT 0');
    if (!$hasConsentAt) $pdo->exec('ALTER TABLE users ADD COLUMN dpa_consent_at TIMESTAMP DEFAULT NULL');
    if ($memberId > 0) {
        $checkStmt = $pdo->prepare('SELECT dpa_consent FROM users WHERE id = :id LIMIT 1');
        $checkStmt->execute([':id' => $memberId]);
        $userRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow && !empty($userRow['dpa_consent'])) {
            header('Location: success.php');
            exit();
        }
    }
} catch (Exception $e) {}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consentGiven = isset($_POST['consent']) && $_POST['consent'] === '1';
    if (!$consentGiven) {
        $error = 'You must accept the Terms and Conditions and data privacy consent to continue.';
    } else {
        try {
            include('connection.php');
            $updateStmt = $pdo->prepare('UPDATE users SET dpa_consent = 1, dpa_consent_at = CURRENT_TIMESTAMP WHERE id = :id');
            $updateStmt->execute([':id' => $memberId]);
            header('Location: success.php');
            exit();
        } catch (Exception $e) {
            $error = 'Unable to save consent. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DPA Consent — FIT-STOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
</head>
<body>
<?php include('header.php'); ?>

<div class="consent-wrap">
    <div class="consent-card">

        <div class="consent-header">
            <div class="label">Required Before Access</div>
            <h1>DATA PRIVACY CONSENT</h1>
        </div>

        <div class="consent-body">
            <p class="desc">
                Before you access your Member Dashboard, please review and accept our Terms of Use
                and Data Privacy policy below. This is required by the Philippines Data Privacy Act (DPA).
            </p>

            <div class="policy-box">
                <h5>Terms of Use and Data Privacy</h5>
                <p>By using this platform, you agree that we may collect, process, and store your membership data, health metrics, workout logs, and payment records. These details will be used for gym services, progress tracking, reporting, and loyalty programs.</p>
                <p>We will protect your personal data with role-based access, secure systems, and limited visibility to only authorized staff. We do not share your personal fitness data outside the organization without your explicit consent.</p>
                <p>All traffic must be encrypted, and all members must confirm this consent before system access.</p>
            </div>

            <?php if ($error): ?>
                <div class="error-box">⚠ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <hr class="divider">

            <form method="POST" action="DPA_Consent.php">
                <div class="check-row">
                    <input type="checkbox" id="consent" name="consent" value="1">
                    <label for="consent">
                        I have read and agree to the
                        <a href="#">Terms of Service</a> and the
                        <a href="#">Data Privacy Policy</a>.
                    </label>
                </div>
                <button type="submit" class="btn-fitstop">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    CONTINUE TO DASHBOARD
                </button>
            </form>
        </div>

    </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>