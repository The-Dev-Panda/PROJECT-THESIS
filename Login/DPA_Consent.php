<?php
session_start();

if (empty($_SESSION['username']) || strtolower((string)($_SESSION['user_type'] ?? '')) !== 'user') {
    header('Location: Login_Page.php');
    exit();
}

$memberId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;

// Ensure consent columns exist; safe no-op if already there.
try {
    include('connection.php');
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $hasConsent = false;
    $hasConsentAt = false;
    foreach ($columns as $col) {
        if (strtolower($col['name']) === 'dpa_consent') {
            $hasConsent = true;
        }
        if (strtolower($col['name']) === 'dpa_consent_at') {
            $hasConsentAt = true;
        }
    }

    if (!$hasConsent) {
        $pdo->exec('ALTER TABLE users ADD COLUMN dpa_consent INTEGER NOT NULL DEFAULT 0');
    }
    if (!$hasConsentAt) {
        $pdo->exec('ALTER TABLE users ADD COLUMN dpa_consent_at TIMESTAMP DEFAULT NULL');
    }

    if ($memberId > 0) {
        $checkStmt = $pdo->prepare('SELECT dpa_consent FROM users WHERE id = :id LIMIT 1');
        $checkStmt->execute([':id' => $memberId]);
        $userRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow && !empty($userRow['dpa_consent'])) {
            header('Location: success.php');
            exit();
        }
    }
} catch (Exception $e) {
    // Continue to showing consent page — we can't trust user in absence of DB readiness.
}

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
<html>
<head>
    <meta charset="utf-8">
    <title>DPA Consent - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
</head>
<body class="bg-dark">
<?php include('header.php'); ?>
<div class="container py-5 mt-5" style="min-height:80vh;">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Data Privacy Consent</h2>
                    <p>Before you access your Member Dashboard, please review and accept our Terms of Use and Data Privacy policy below. This is required by the Philippines Data Privacy Act (DPA).</p>

                    <div class="border border-secondary rounded p-3 mb-3" style="max-height:220px; overflow:auto; background:#f8f9fa; color:#212529;">
                        <h5>Terms of Use and Data Privacy</h5>
                        <p>By using this platform, you agree that we may collect, process, and store your membership data, health metrics, workout logs, and payment records. These details will be used for gym services, progress tracking, reporting, and loyalty programs.</p>
                        <p>We will protect your personal data with role-based access, secure systems, and limited visibility to only authorized staff. We do not share your personal fitness data outside the organization without your explicit consent.</p>
                        <p>All traffic must be encrypted, and all members must confirm this consent before system access.</p>
                        
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="DPA_Consent.php">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="consent" name="consent" value="1">
                            <label class="form-check-label" for="consent">
                                I have read and agree to the <a href="#">Terms of Service</a> and the <a href="#">Data Privacy Policy</a>.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Continue to Dashboard</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
</body>
</html>
