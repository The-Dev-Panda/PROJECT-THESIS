<?php
require_once __DIR__ . '/auth_user.php';
$transactions = [];
try {
    $dbPath = __DIR__ . '/../Database/DB.sqlite';
    if (file_exists($dbPath)) {
        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare('SELECT receipt_number, amount, payment_method, status, "desc", created_at FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute([':user_id' => $_SESSION['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Database error - continue without transactions
}
?>
<?php $activePage = 'payments';
$workoutDays = [];
$welcomeName = 'Member';
$selectedPeriod = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'week';
$allowedPeriods = ['week', 'month', 'year'];
if (!in_array($selectedPeriod, $allowedPeriods, true)) {
    $selectedPeriod = 'week';
}

$periodWindowMap = [
    'week'  => '-6 days',
    'month' => '-29 days',
    'year'  => '-364 days'
];
$windowModifier = $periodWindowMap[$selectedPeriod];

$historyRows = [];

function historyIconClass(string $exerciseNames): string {
    $haystack = strtolower($exerciseNames);
    if (preg_match('/chest|tricep|bench|press|pec/', $haystack)) return 'chest';
    if (preg_match('/leg|squat|lunge|hamstring|calf/', $haystack)) return 'legs';
    if (preg_match('/cardio|run|treadmill|bike|cycling|walk|elliptical/', $haystack)) return 'cardio';
    return 'back';
}

function historyIconSymbol(string $iconClass): string {
    if ($iconClass === 'chest') return 'fa-dumbbell';
    if ($iconClass === 'legs') return 'fa-running';
    if ($iconClass === 'cardio') return 'fa-heartbeat';
    return 'fa-user';
}

try {
    require __DIR__ . '/../Login/connection.php';
    $userId = (int)($_SESSION['id'] ?? 0);
    if ($userId > 0) {
        $userStmt = $pdo->prepare('SELECT first_name, username FROM users WHERE id = :id LIMIT 1');
        $userStmt->execute([':id' => $userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($user['first_name'])) {
            $welcomeName = (string)$user['first_name'];
        } elseif (!empty($user['username'])) {
            $welcomeName = (string)$user['username'];
        }

        $historyStmt = $pdo->prepare("SELECT
            date(datetime(wl.logged_at, 'localtime')) AS workout_day,
            MIN(datetime(wl.logged_at, 'localtime')) AS first_log_time,
            MAX(datetime(wl.logged_at, 'localtime')) AS last_log_time,
            COUNT(*) AS total_sets,
            COUNT(DISTINCT wl.exercise_id) AS exercise_count,
            SUM(COALESCE(wl.weight, 0) * COALESCE(wl.reps, 0)) AS total_volume,
            GROUP_CONCAT(DISTINCT COALESCE(e.name, 'Exercise')) AS exercise_names
          FROM workout_logs wl
          LEFT JOIN exercises e ON e.exercise_id = wl.exercise_id
          WHERE wl.user_id = :user_id
            AND datetime(wl.logged_at, 'localtime') >= datetime('now', 'localtime', :window)
          GROUP BY workout_day
          ORDER BY workout_day DESC
          LIMIT 60");
        $historyStmt->execute([':user_id' => $userId, ':window' => $windowModifier]);
        $rows = $historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $row) {
            $dayValue = (string)($row['workout_day'] ?? '');
            if ($dayValue === '') continue;
            $dayLabel = $dayValue;
            $dayFull  = $dayValue;
            $dayDate  = DateTime::createFromFormat('Y-m-d', $dayValue);
            if ($dayDate instanceof DateTime) {
                $today     = new DateTime('now', new DateTimeZone('Asia/Manila'));
                $yesterday = (clone $today)->modify('-1 day');
                if ($dayDate->format('Y-m-d') === $today->format('Y-m-d')) {
                    $dayLabel = 'Today';
                } elseif ($dayDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                    $dayLabel = 'Yesterday';
                } else {
                    $dayLabel = $dayDate->format('M j');
                }
                $dayFull = $dayDate->format('l, M j, Y');
            }
            $firstTime = (string)($row['first_log_time'] ?? '');
            $lastTime  = (string)($row['last_log_time'] ?? '');
            $timeRange = 'Time not available';
            if ($firstTime !== '' && $lastTime !== '') {
                $start     = new DateTime($firstTime);
                $end       = new DateTime($lastTime);
                $timeRange = $start->format('g:i A') . ' - ' . $end->format('g:i A');
            }
            $exerciseNamesCsv = (string)($row['exercise_names'] ?? '');
            $exerciseNames    = array_values(array_filter(array_map('trim', explode(',', $exerciseNamesCsv))));
            $exerciseCount    = (int)($row['exercise_count'] ?? count($exerciseNames));
            $title = 'Workout Session';
            if (count($exerciseNames) === 1) {
                $title = $exerciseNames[0];
            } elseif (count($exerciseNames) > 1) {
                $title = $exerciseNames[0] . ' + ' . (count($exerciseNames) - 1) . ' more';
            }
            $iconClass   = historyIconClass($exerciseNamesCsv);
            $iconSymbol  = historyIconSymbol($iconClass);
            $totalSets   = (int)($row['total_sets'] ?? 0);
            $totalVolume = (float)($row['total_volume'] ?? 0);
            $historyRows[] = [
                'day_label'         => $dayLabel,
                'day_full'          => $dayFull,
                'title'             => $title,
                'time_range'        => $timeRange,
                'icon_class'        => $iconClass,
                'icon_symbol'       => $iconSymbol,
                'total_sets'        => $totalSets,
                'exercise_count'    => $exerciseCount,
                'total_volume_text' => number_format($totalVolume, 0) . ' kg total'
            ];
        }
    }
} catch (Throwable $e) {
    // Show empty-state card if loading from DB fails.
}
?>
<?php $activePage = 'history'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="user.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

</head>
<body>
<div class="dashboard">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <!-- TOP BAR -->
        <header class="topbar">
            <div class="welcome">
                <h1>History</h1>
                <p>
                    Hi <?php echo htmlspecialchars($welcomeName, ENT_QUOTES, 'UTF-8'); ?>!
                    This is all your history for the past
                    <?php echo $selectedPeriod === 'week' ? 'week' : ($selectedPeriod === 'month' ? 'month' : 'year'); ?>.
                </p>
            </div>
        </header>

        <!-- EXERCISE HISTORY -->
        <?php
        $workoutDays = [];
        try {
            require __DIR__ . '/../Login/connection.php';
            $stmt = $pdo->prepare("
                SELECT w.logged_at, w.reps, w.weight, w.sets, e.name
                FROM workout_logs w
                LEFT JOIN exercises e ON e.exercise_id = w.exercise_id
                WHERE w.user_id = :uid
                ORDER BY w.logged_at DESC
            ");
            $stmt->execute([':uid' => 1]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $dateKey = date('Y-m-d', strtotime($r['logged_at']));
                $sets    = (int)$r['sets'];
                $volume  = $sets * (int)$r['reps'] * (int)$r['weight'];
                if (!isset($workoutDays[$dateKey])) {
                    $workoutDays[$dateKey] = [
                        'label'        => date('l', strtotime($dateKey)),
                        'sub'          => date('F d, Y', strtotime($dateKey)),
                        'total_sets'   => $sets,
                        'total_volume' => $volume,
                        'exercises'    => [['name' => $r['name'], 'sets' => $sets, 'reps' => (int)$r['reps'], 'max_weight' => (int)$r['weight']]]
                    ];
                } else {
                    $workoutDays[$dateKey]['total_sets']   += $sets;
                    $workoutDays[$dateKey]['total_volume']  += $volume;
                    $workoutDays[$dateKey]['exercises'][]   = ['name' => $r['name'], 'sets' => $sets, 'reps' => (int)$r['reps'], 'max_weight' => (int)$r['weight']];
                }
            }
            $workoutDays = array_values($workoutDays);
        } catch (Throwable $e) {
            $workoutDays = [];
        }
        ?>

        <section class="history-section">
            <div class="section-header">
                <h3>Exercise History</h3>
                <div class="filter-buttons">
                    <button class="filter-btn active">Latest Workouts</button>
                    <button class="filter-btn" <?php echo count($workoutDays) <= 1 ? 'disabled' : ''; ?>>Show Previous</button>
                </div>
            </div>
            <div class="history-grid">
                <?php if (count($workoutDays) === 0): ?>
                    <div class="history-card">
                        <div class="history-date">
                            <span class="date-day">No workouts yet</span>
                            <span class="date-full">Start logging your exercises to see history.</span>
                        </div>
                        <span class="completion-badge">No data</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($workoutDays as $wIdx => $wDay): ?>
                        <div class="history-card" <?php echo $wIdx !== 0 ? 'style="display:none"' : ''; ?>>
                            <div class="history-date">
                                <span class="date-day"><?php echo htmlspecialchars($wDay['label']); ?></span>
                                <span class="date-full"><?php echo htmlspecialchars($wDay['sub']); ?></span>
                            </div>
                            <div class="history-workout">
                                <div class="workout-icon chest"><i class="fas fa-dumbbell"></i></div>
                                <div class="workout-details" style="width:100%;">
                                    <h4>Workout Summary</h4>
                                    <p>Time not available</p>
                                    <div class="workout-stats-mini">
                                        <span><i class="fas fa-layer-group"></i> <?php echo (int)$wDay['total_sets']; ?> sets</span>
                                        <span><i class="fas fa-weight-hanging"></i> <?php echo number_format((int)$wDay['total_volume'], 0); ?> kg volume</span>
                                    </div>
                                    <div style="display:none; margin-top:10px;">
                                        <?php foreach ($wDay['exercises'] as $ex): ?>
                                            <div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.08);">
                                                <span><?php echo htmlspecialchars($ex['name']); ?></span>
                                                <span><?php echo (int)$ex['sets']; ?> sets, <?php echo (int)$ex['reps']; ?> reps, max <?php echo (int)$ex['max_weight']; ?> kg</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="notify-btn" style="margin-top:10px;"
                                        onclick="this.previousElementSibling.style.display=this.previousElementSibling.style.display==='none'?'block':'none'">
                                        Show workout details
                                    </button>
                                </div>
                            </div>
                            <span class="completion-badge completed">Completed</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══════════════════════════════════════
             E-RECEIPTS & PAYMENT HISTORY
        ══════════════════════════════════════ -->
        <div class="profile-card terms-card">
            <h4><i class="bi bi-receipt"></i> E-Receipts &amp; Payment History</h4>
            <hr class="section-divider" />

            <?php if (empty($transactions)): ?>
                <p style="text-align:center;color:#999;padding:20px;">No transactions found.</p>
            <?php else: ?>
                <?php foreach ($transactions as $transaction):
                    $desc   = htmlspecialchars($transaction['desc'] ?? 'Payment', ENT_QUOTES, 'UTF-8');
                    $date   = htmlspecialchars($transaction['created_at'] ?? '', ENT_QUOTES, 'UTF-8');
                    $method = htmlspecialchars($transaction['payment_method'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    $amount = number_format((float)$transaction['amount'], 2);
                    $status = htmlspecialchars($transaction['status'] ?? 'Paid', ENT_QUOTES, 'UTF-8');
                    $receiptNo = htmlspecialchars($transaction['receipt_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

                    $dataJson = htmlspecialchars(json_encode([
                        'desc'      => $transaction['desc'] ?? 'Payment',
                        'date'      => $transaction['created_at'] ?? '',
                        'method'    => $transaction['payment_method'] ?? 'N/A',
                        'amount'    => $amount,
                        'status'    => $transaction['status'] ?? 'Paid',
                        'receiptNo' => $transaction['receipt_number'] ?? 'N/A',
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="receipt-entry" onclick="openReceiptModal(<?php echo $dataJson; ?>)">
                    <div class="receipt-icon">
                        <!-- Cash / banknote icon -->
                        <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.8">
                            <rect x="2" y="6" width="20" height="12" rx="1"/>
                            <path d="M22 10H2"/>
                            <path d="M6 14h.01M10 14h4"/>
                        </svg>
                    </div>
                    <div class="receipt-info">
                        <span class="rname"><?php echo $desc; ?></span>
                        <span class="rdate"><?php echo $date; ?> &nbsp;•&nbsp; <?php echo $method; ?></span>
                    </div>
                    <span class="receipt-amount">&#8369;<?php echo $amount; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ══════════════════════════════════════
     RECEIPT DETAIL MODAL
══════════════════════════════════════ -->
<div class="receipt-overlay" id="receiptOverlay" onclick="if(event.target===this)closeReceiptModal()">
    <div class="receipt-modal" id="receiptModalBox">

        <div class="modal-head">
            <div class="modal-head-left">
                <span class="modal-logo">Fit-Stop</span>
                <div>
                    <span class="modal-title">Receipt Detail</span>
                    <span class="modal-sub">Official E-Receipt</span>
                </div>
            </div>
            <button class="modal-close" onclick="closeReceiptModal()">&#10005;</button>
        </div>

        <div class="modal-body">
            <div class="modal-icon-wrap">
                <div class="modal-icon-big">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.8">
                        <rect x="2" y="6" width="20" height="12" rx="1"/>
                        <path d="M22 10H2"/>
                        <path d="M6 14h.01M10 14h4"/>
                    </svg>
                </div>
            </div>

            <div class="modal-row">
                <span class="modal-label">Receipt No.</span>
                <span class="modal-value" id="m-receiptNo">—</span>
            </div>
            <div class="modal-row">
                <span class="modal-label">Description</span>
                <span class="modal-value" id="m-desc">—</span>
            </div>
            <div class="modal-row">
                <span class="modal-label">Date &amp; Time</span>
                <span class="modal-value" id="m-date">—</span>
            </div>
            <div class="modal-row">
                <span class="modal-label">Payment Method</span>
                <span class="modal-value" id="m-method">—</span>
            </div>
            <div class="modal-row">
                <span class="modal-label">Amount</span>
                <span class="modal-value modal-amount" id="m-amount">—</span>
            </div>
            <div class="modal-row">
                <span class="modal-label">Status</span>
                <span class="modal-status" id="m-status">PAID</span>
            </div>
        </div>

        <div class="modal-footer">
            <button class="dl-single-btn" onclick="downloadSingleReceipt()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download This Receipt
            </button>
        </div>

    </div>
</div>

<div class="receipt-toast" id="receiptToast">&#10003; Downloaded!</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="lightmode.js"></script>

<script>
    function openReceiptModal(r) {
        document.getElementById('m-receiptNo').textContent = r.receiptNo || '—';
        document.getElementById('m-desc').textContent      = r.desc    || '—';
        document.getElementById('m-date').textContent      = r.date    || '—';
        document.getElementById('m-method').textContent   = r.method  || '—';
        document.getElementById('m-amount').textContent   = '₱' + r.amount;
        document.getElementById('m-status').textContent   = (r.status || 'Paid').toUpperCase();
        document.getElementById('receiptOverlay').classList.add('open');
    }

    function closeReceiptModal() {
        document.getElementById('receiptOverlay').classList.remove('open');
    }

    function downloadSingleReceipt() {
        const box = document.getElementById('receiptModalBox');
        html2canvas(box, {
            backgroundColor: '#1a1a1a',
            scale: 2,
            useCORS: true,
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'receipt-' + Date.now() + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();

            const toast = document.getElementById('receiptToast');
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }).catch(() => {
            alert('Download failed. Please try again.');
        });
    }
</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>