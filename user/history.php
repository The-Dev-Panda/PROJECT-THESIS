<?php
require_once __DIR__ . '/auth_user.php';

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
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Exercise History</title>
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
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <!-- LOGO -->
        <div class="sidebar-header">
          <img
            src="userimage/FIT-STOP LOGO.png"
            alt="Fit-Stop Logo"
            class="logo-img"
          />
          <span class="logo-text">Fit-Stop</span>
        </div>
        <!-- MENU -->
        <ul class="menu">
          <li>
            <a href="user.php">
              <i class="bi bi-grid-1x2"></i>
              <span>Dashboard</span>
            </a>
          </li>

          <li>
            <a href="bmi.php">
              <i class="bi bi-heart-pulse"></i>
              <span>BMI Tracker</span>
            </a>
          </li>

          <li>
            <a href="myplan.php">
              <i class="bi bi-clipboard-check"></i>
              <span>My Plan</span>
            </a>
          </li>

          <li class="active">
            <a href="history.php">
              <i class="bi bi-clock-history"></i>
              <span>History</span>
            </a>
          </li>

          <li>
            <a href="payments.php">
              <i class="bi bi-credit-card"></i>
              <span>Payments</span>
            </a>
          </li>

          <li>
            <a href="AI_ADVISOR.php">
              <i class="bi bi-robot"></i>
              <span>AI Advisor</span>
            </a>
          </li>

          <li>
            <a href="profile.php">
              <i class="bi bi-person"></i>
              <span>Profile</span>
            </a>
          </li>

          <li>
            <a href="settings.php">
              <i class="bi bi-gear"></i>
              <span>Settings</span>
            </a>
          </li>

          <li>
            <a href="logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Logout</span>
            </a>
          </li>
        </ul>
      </aside>
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

          <div class="search-container">
            <div>
              <i class="fas fa-search search-icon"></i>
              <input
                type="text"
                class="search-input"
                placeholder="What is your goal today?"
              />
            </div>
            <button class="search-btn">Search</button>
          </div>
        </header>

        <!-- EXERCISE HISTORY SECTION -->
        <section class="history-section">
          <div class="section-header">
            <h3>Exercise History</h3>
            <div class="filter-buttons">
              <a class="filter-btn <?php echo $selectedPeriod === 'week' ? 'active' : ''; ?>" href="history.php?period=week" style="text-decoration:none;">Week</a>
              <a class="filter-btn <?php echo $selectedPeriod === 'month' ? 'active' : ''; ?>" href="history.php?period=month" style="text-decoration:none;">Month</a>
              <a class="filter-btn <?php echo $selectedPeriod === 'year' ? 'active' : ''; ?>" href="history.php?period=year" style="text-decoration:none;">Year</a>
            </div>
          </div>

          <div class="history-grid">
            <?php if (!empty($historyRows)): ?>
              <?php foreach ($historyRows as $historyRow): ?>
                <div class="history-card">
                  <div class="history-date">
                    <span class="date-day"><?php echo htmlspecialchars($historyRow['day_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="date-full"><?php echo htmlspecialchars($historyRow['day_full'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="history-workout">
                    <div class="workout-icon <?php echo htmlspecialchars($historyRow['icon_class'], ENT_QUOTES, 'UTF-8'); ?>">
                      <i class="fas <?php echo htmlspecialchars($historyRow['icon_symbol'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </div>
                    <div class="workout-details">
                      <h4><?php echo htmlspecialchars($historyRow['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                      <p><?php echo htmlspecialchars($historyRow['time_range'], ENT_QUOTES, 'UTF-8'); ?></p>
                      <div class="workout-stats-mini">
                        <span><i class="fas fa-fire"></i> <?php echo (int)$historyRow['total_sets']; ?> sets</span>
                        <span><i class="fas fa-list"></i> <?php echo (int)$historyRow['exercise_count']; ?> exercises</span>
                        <span><i class="fas fa-weight-hanging"></i> <?php echo htmlspecialchars($historyRow['total_volume_text'], ENT_QUOTES, 'UTF-8'); ?></span>
                      </div>
                    </div>
                  </div>
                  <span class="completion-badge completed">Completed</span>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="history-card">
                <div class="history-date">
                  <span class="date-day">No Workouts Yet</span>
                  <span class="date-full">Start logging workouts to build your history.</span>
                </div>
                <div class="history-workout">
                  <div class="workout-icon cardio">
                    <i class="fas fa-dumbbell"></i>
                  </div>
                  <div class="workout-details">
                    <h4>No records found</h4>
                    <p>Try checking a different period or log your first workout.</p>
                  </div>
                </div>
                <span class="completion-badge" style="background:#374151;color:#e5e7eb;">No Data</span>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </main>
    </div>
    <script src="lightmode.js"></script>
  </body>
</html>

