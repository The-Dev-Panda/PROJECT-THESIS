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
    <style>
        :root {
            --yellow: #F5C800;
            --yellow-dim: #c9a400;
            --dark: #0e0e0e;
            --dark2: #161616;
            --dark3: #1e1e1e;
            --border: #2a2a2a;
            --text: #d0d0d0;
            --text-muted: #737373;
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--dark);
            font-family: 'Barlow', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Hazard stripe bar at bottom (matching homepage) ── */
        body::after {
            content: '';
            display: block;
            height: 10px;
            background: repeating-linear-gradient(
                -45deg,
                var(--yellow) 0px,
                var(--yellow) 14px,
                #111 14px,
                #111 28px
            );
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }

        /* ── Page layout ── */
        .consent-wrap {
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 16px 48px;
        }

        /* ── Card ── */
        .consent-card {
            background: var(--dark2);
            border: 1px solid var(--border);
            border-top: 3px solid var(--yellow);
            border-radius: 4px;
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            animation: slideUp 0.45s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .consent-header {
            background: var(--dark3);
            border-bottom: 1px solid var(--border);
            padding: 28px 32px 22px;
        }

        .consent-header .label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--yellow);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .consent-header h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.4rem;
            letter-spacing: 2px;
            color: #fff;
            margin: 0;
            line-height: 1;
        }

        .consent-body {
            padding: 28px 32px;
        }

        .consent-body > .desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.65;
            margin-bottom: 20px;
        }

        /* ── Scrollable policy box ── */
        .policy-box {
            background: var(--dark);
            border: 1px solid var(--border);
            border-left: 3px solid var(--yellow);
            border-radius: 3px;
            padding: 20px 20px 20px 20px;
            max-height: 220px;
            overflow-y: auto;
            margin-bottom: 22px;
            scrollbar-width: thin;
            scrollbar-color: var(--yellow) var(--dark3);
        }

        .policy-box::-webkit-scrollbar { width: 5px; }
        .policy-box::-webkit-scrollbar-track { background: var(--dark3); }
        .policy-box::-webkit-scrollbar-thumb { background: var(--yellow); border-radius: 2px; }

        .policy-box h5 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 1.5px;
            color: var(--yellow);
            margin-bottom: 12px;
        }

        .policy-box p {
            font-size: 13px;
            color: #999;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        /* ── Error ── */
        .error-box {
            background: rgba(220, 53, 69, 0.12);
            border: 1px solid rgba(220, 53, 69, 0.4);
            border-radius: 3px;
            padding: 12px 16px;
            font-size: 13px;
            color: #f08080;
            margin-bottom: 18px;
        }

        /* ── Checkbox ── */
        .check-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 24px;
        }

        .check-row input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            min-width: 18px;
            border: 2px solid #444;
            border-radius: 2px;
            background: var(--dark);
            cursor: pointer;
            position: relative;
            margin-top: 2px;
            transition: border-color 0.2s, background 0.2s;
        }

        .check-row input[type="checkbox"]:checked {
            background: var(--yellow);
            border-color: var(--yellow);
        }

        .check-row input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 3px; top: 0px;
            width: 8px; height: 11px;
            border: 2px solid #000;
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }

        .check-row label {
            font-size: 13.5px;
            color: var(--text-muted);
            cursor: pointer;
            line-height: 1.55;
        }

        .check-row label a {
            color: var(--yellow);
            text-decoration: none;
            font-weight: 600;
        }

        .check-row label a:hover {
            text-decoration: underline;
        }

        /* ── Submit button ── */
        .btn-fitstop {
            width: 100%;
            padding: 14px;
            background: var(--yellow);
            color: #000;
            border: none;
            border-radius: 3px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.15rem;
            letter-spacing: 2.5px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-fitstop:hover { background: #ffd700; }
        .btn-fitstop:active { transform: scale(0.98); }

        .btn-fitstop svg {
            width: 16px; height: 16px;
        }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0 0 22px;
        }
    </style>
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