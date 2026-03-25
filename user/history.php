<?php
require_once __DIR__ . '/auth_user.php';
$workoutDays = [];
$welcomeName = 'Member';
$selectedPeriod = isset($_GET['period']) ? strtolower(trim((string)$_GET['period'])) : 'week';
$allowedPeriods = ['week', 'month', 'year'];
if (!in_array($selectedPeriod, $allowedPeriods, true)) {
  $selectedPeriod = 'week';
}

$periodWindowMap = [
  'week' => '-6 days',
  'month' => '-29 days',
  'year' => '-364 days'
];
$windowModifier = $periodWindowMap[$selectedPeriod];

$historyRows = [];

function historyIconClass(string $exerciseNames): string {
  $haystack = strtolower($exerciseNames);
  if (preg_match('/chest|tricep|bench|press|pec/', $haystack)) {
    return 'chest';
  }
  if (preg_match('/leg|squat|lunge|hamstring|calf/', $haystack)) {
    return 'legs';
  }
  if (preg_match('/cardio|run|treadmill|bike|cycling|walk|elliptical/', $haystack)) {
    return 'cardio';
  }
  return 'back';
}

function historyIconSymbol(string $iconClass): string {
  if ($iconClass === 'chest') {
    return 'fa-dumbbell';
  }
  if ($iconClass === 'legs') {
    return 'fa-running';
  }
  if ($iconClass === 'cardio') {
    return 'fa-heartbeat';
  }
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
    $historyStmt->execute([
      ':user_id' => $userId,
      ':window' => $windowModifier
    ]);
    $rows = $historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
      $dayValue = (string)($row['workout_day'] ?? '');
      if ($dayValue === '') {
        continue;
      }

      $dayLabel = $dayValue;
      $dayFull = $dayValue;
      $dayDate = DateTime::createFromFormat('Y-m-d', $dayValue);
      if ($dayDate instanceof DateTime) {
        $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
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
      $lastTime = (string)($row['last_log_time'] ?? '');
      $timeRange = 'Time not available';
      if ($firstTime !== '' && $lastTime !== '') {
        $start = new DateTime($firstTime);
        $end = new DateTime($lastTime);
        $timeRange = $start->format('g:i A') . ' - ' . $end->format('g:i A');
      }

      $exerciseNamesCsv = (string)($row['exercise_names'] ?? '');
      $exerciseNames = array_values(array_filter(array_map('trim', explode(',', $exerciseNamesCsv))));
      $exerciseCount = (int)($row['exercise_count'] ?? count($exerciseNames));

      $title = 'Workout Session';
      if (count($exerciseNames) === 1) {
        $title = $exerciseNames[0];
      } elseif (count($exerciseNames) > 1) {
        $title = $exerciseNames[0] . ' + ' . (count($exerciseNames) - 1) . ' more';
      }

      $iconClass = historyIconClass($exerciseNamesCsv);
      $iconSymbol = historyIconSymbol($iconClass);
      $totalSets = (int)($row['total_sets'] ?? 0);
      $totalVolume = (float)($row['total_volume'] ?? 0);

      $historyRows[] = [
        'day_label' => $dayLabel,
        'day_full' => $dayFull,
        'title' => $title,
        'time_range' => $timeRange,
        'icon_class' => $iconClass,
        'icon_symbol' => $iconSymbol,
        'total_sets' => $totalSets,
        'exercise_count' => $exerciseCount,
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
    <title>Exercise History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-OERcA2zY1OHt4q4Fv8B+U7MeM3NnN3KK2eEbV5t8JSaI1zlzW3URy9Bv1WTRi7v8Q" crossorigin="anonymous">
    <link rel="stylesheet" href="user.css" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Chakra+Petch:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
      <!-- MAIN CONTENT -->
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
        <section class="history-section">
          <div class="section-header">
            <h3>Exercise History</h3>
            <div class="filter-buttons">
              <button class="filter-btn active">Latest Workouts</button>
              <button class="filter-btn" id="showPreviousWorkoutBtn" <?php echo count($workoutDays) <= 1 ? 'disabled' : ''; ?>>Show Previous</button>
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
              <?php foreach ($workoutDays as $workoutDayIndex => $workoutDay): ?>
                <div
                  class="history-card"
                  data-workout-card="1"
                  <?php echo $workoutDayIndex === 0 ? '' : 'style="display:none" data-workout-hidden="1"'; ?>
                >
                  <div class="history-date">
                    <span class="date-day"><?php echo htmlspecialchars($workoutDay['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="date-full"><?php echo htmlspecialchars($workoutDay['sub'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="history-workout">
                    <div class="workout-icon chest">
                      <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="workout-details" style="width:100%;">
                      <h4>Workout Summary</h4>
                      <p><?php echo htmlspecialchars($workoutDay['time_range'] !== '' ? $workoutDay['time_range'] : 'Time not available', ENT_QUOTES, 'UTF-8'); ?></p>
                      <div class="workout-stats-mini">
                        <span><i class="fas fa-layer-group"></i> <?php echo (int)$workoutDay['total_sets']; ?> sets</span>
                        <span><i class="fas fa-weight-hanging"></i> <?php echo htmlspecialchars(number_format((float)$workoutDay['total_volume'], 1), ENT_QUOTES, 'UTF-8'); ?> kg volume</span>
                      </div>
                      <div id="workoutDayDetail-<?php echo (int)$workoutDayIndex; ?>" style="display:none;margin-top:10px;">
                        <?php foreach ($workoutDay['exercises'] as $exercise): ?>
                          <div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.08);">
                            <span><?php echo htmlspecialchars($exercise['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span style="opacity:0.9;"><?php echo (int)$exercise['sets']; ?> sets, <?php echo (int)$exercise['reps']; ?> reps, max <?php echo htmlspecialchars(number_format((float)$exercise['max_weight'], 1), ENT_QUOTES, 'UTF-8'); ?> kg</span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                      <button class="notify-btn" style="margin-top:10px;" onclick="toggleWorkoutDayDetail(<?php echo (int)$workoutDayIndex; ?>)">Show workout details</button>
                    </div>
                  </div>
                  <span class="completion-badge completed">Completed</span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
   <!-- E-Receipts -->
          <div class="profile-card terms-card">
            <h4><i class="bi bi-receipt"></i> E-Receipts & Payment History</h4>
            <hr class="section-divider" />

            <?php if (empty($transactions)): ?>
              <p style="text-align: center; color: #999; padding: 20px;">No transactions found.</p>
            <?php else: ?>
              <?php foreach ($transactions as $transaction): ?>
              <div class="receipt-entry">
                <div class="receipt-icon">
                  <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="receipt-info">
                  <span class="rname"><?php echo htmlspecialchars($transaction['desc'] ?? 'Payment'); ?></span>
                  <span class="rdate"><?php echo htmlspecialchars($transaction['created_at'] ?? date('M d, Y')); ?> &nbsp;•&nbsp; <?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></span>
                </div>
                <span class="receipt-amount">₱<?php echo number_format((float)$transaction['amount'], 2); ?></span>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <a class="view-all-link">Download all receipts <i class="fas fa-download"></i></a>
          </div>
      </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
    <script src="lightmode.js"></script>
  </body>
</html>

