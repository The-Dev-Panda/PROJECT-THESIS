<?php
require_once __DIR__ . '/auth_user.php';

$displayName = 'Member';
$firstName = 'Member';
$fitnessLevel = 'Not set';
$goal = 'Primary Goal';
$bmiBadgeClass = 'healthy';
$bmiLabel = 'Not set';
$bmiValueText = '--';
$heightText = 'Not set';
$weightText = 'Not set';
$targetWeightText = 'Not set';
$toGoalText = 'Not set';
$bmiMarkerLeft = '50';
$attendanceTitle = 'No Attendance Yet';
$attendanceDetail = 'No check-in record found.';
$workoutDays = [];
$profileInitials = 'MM';
$monthWeightChangeText = 'No data';
$totalWeightChangeText = 'No data';

try {
  require __DIR__ . '/../Login/connection.php';
  require_once __DIR__ . '/../includes/db_helpers.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT id, username, first_name, last_name, email FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $profileColumns = getTableColumns($pdo, 'member_profiles');

    $profileSelectSql = 'SELECT '
      . (in_array('age', $profileColumns, true) ? 'age' : 'NULL AS age') . ', '
      . (in_array('height_cm', $profileColumns, true) ? 'height_cm' : 'NULL AS height_cm') . ', '
      . (in_array('weight_kg', $profileColumns, true) ? 'weight_kg' : 'NULL AS weight_kg') . ', '
      . (in_array('fitness_level', $profileColumns, true) ? 'fitness_level' : 'NULL AS fitness_level') . ', '
      . (in_array('goal', $profileColumns, true) ? 'goal' : 'NULL AS goal') . ', '
      . (in_array('bmi', $profileColumns, true) ? 'bmi' : 'NULL AS bmi')
      . ' FROM member_profiles WHERE user_id = :user_id LIMIT 1';

    $profileStmt = $pdo->prepare($profileSelectSql);
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $displayNameRaw = trim(((string)($user['first_name'] ?? '')) . ' ' . ((string)($user['last_name'] ?? '')));
    if ($displayNameRaw !== '') {
      $displayName = $displayNameRaw;
    } elseif (!empty($user['username'])) {
      $displayName = (string)$user['username'];
    }

    $firstName = (string)($user['first_name'] ?? '');
    if ($firstName === '') {
      $parts = preg_split('/\s+/', $displayName);
      $firstName = $parts[0] ?? 'Member';
    }

    if (!empty($profile['fitness_level'])) {
      $fitnessLevel = (string)$profile['fitness_level'];
    }
    if (!empty($profile['goal'])) {
      $goal = (string)$profile['goal'];
    }

    $heightCm = isset($profile['height_cm']) && $profile['height_cm'] !== null ? (float)$profile['height_cm'] : null;
    $weightKg = isset($profile['weight_kg']) && $profile['weight_kg'] !== null ? (float)$profile['weight_kg'] : null;
    $bmi = isset($profile['bmi']) && $profile['bmi'] !== null ? (float)$profile['bmi'] : null;

    if (($bmi === null || $bmi <= 0) && $heightCm !== null && $heightCm > 0 && $weightKg !== null && $weightKg > 0) {
      $hM = $heightCm / 100;
      $bmi = round($weightKg / ($hM * $hM), 1);
    }

    if ($heightCm !== null && $heightCm > 0) {
      $heightText = rtrim(rtrim(number_format($heightCm, 1, '.', ''), '0'), '.') . ' cm';
    }
    if ($weightKg !== null && $weightKg > 0) {
      $weightText = rtrim(rtrim(number_format($weightKg, 1, '.', ''), '0'), '.') . ' kg';

      $formatWeightDiff = static function (float $value): string {
        $rounded = round($value, 1);
        $prefix = $rounded > 0 ? '+' : '';
        return $prefix . number_format($rounded, 1, '.', '') . ' kg';
      };

      $hasHistoryTable = tableExists($pdo, 'old_member_profiles');
      if ($hasHistoryTable) {
        $monthBaseline = null;
        $oldestWeight = null;

        $monthStmt = $pdo->prepare("SELECT weight_kg
          FROM old_member_profiles
          WHERE user_id = :user_id
            AND weight_kg IS NOT NULL
            AND archived_at <= :month_threshold
          ORDER BY archived_at DESC
          LIMIT 1");
        $monthStmt->execute([
            ':user_id' => $userId,
            ':month_threshold' => date('Y-m-d H:i:s', strtotime('-30 days')),
        ]);
        $monthBaselineRaw = $monthStmt->fetchColumn();
        if ($monthBaselineRaw !== false && $monthBaselineRaw !== null) {
          $monthBaseline = (float)$monthBaselineRaw;
        }

        $oldestStmt = $pdo->prepare("SELECT weight_kg
          FROM old_member_profiles
          WHERE user_id = :user_id
            AND weight_kg IS NOT NULL
          ORDER BY archived_at ASC
          LIMIT 1");
        $oldestStmt->execute([':user_id' => $userId]);
        $oldestWeightRaw = $oldestStmt->fetchColumn();
        if ($oldestWeightRaw !== false && $oldestWeightRaw !== null) {
          $oldestWeight = (float)$oldestWeightRaw;
          $totalWeightChangeText = $formatWeightDiff($weightKg - $oldestWeight);
        }

        if ($monthBaseline !== null) {
          $monthWeightChangeText = $formatWeightDiff($weightKg - $monthBaseline);
        } elseif ($oldestWeight !== null) {
          $monthWeightChangeText = $formatWeightDiff($weightKg - $oldestWeight);
        }
      }
    }

    if ($bmi !== null && $bmi > 0) {
      $bmiValueText = number_format($bmi, 1, '.', '');

      if ($bmi < 18.5) {
        $bmiLabel = 'Underweight';
        $bmiBadgeClass = 'underweight';
      } elseif ($bmi < 25) {
        $bmiLabel = 'Healthy';
        $bmiBadgeClass = 'healthy';
      } elseif ($bmi < 30) {
        $bmiLabel = 'Overweight';
        $bmiBadgeClass = 'overweight';
      } else {
        $bmiLabel = 'Obese';
        $bmiBadgeClass = 'obese';
      }

      $bmiMarkerLeft = (string)round(min(max((($bmi - 10) / 30) * 100, 2), 97), 1);
    }

    if ($heightCm !== null && $heightCm > 0 && $weightKg !== null && $weightKg > 0) {
      $hM = $heightCm / 100;
      $idealWeight = 21.7 * $hM * $hM;
      $diff = $weightKg - $idealWeight;
      $targetWeightText = round($idealWeight) . ' kg';
      if (abs($diff) < 0.1) {
        $toGoalText = '0 kg';
      } else {
        $toGoalText = ($diff > 0 ? '+' : '') . round($diff) . ' kg';
      }
    }

    $attendanceStmt = $pdo->prepare('SELECT datetime FROM attendance WHERE user_id = :user_id ORDER BY datetime DESC LIMIT 1');
    $attendanceStmt->execute([':user_id' => $userId]);
    $lastAttendanceRaw = $attendanceStmt->fetchColumn();
    if ($lastAttendanceRaw) {
      $attendanceAt = new DateTime((string)$lastAttendanceRaw, new DateTimeZone('UTC'));
      $attendanceAt->setTimezone(new DateTimeZone('Asia/Manila'));
      $attendanceTitle = 'Last Attendance';
      $attendanceDetail = $attendanceAt->format('M j, Y') . ' at ' . $attendanceAt->format('g:i A');
    }

    $workoutStmt = $pdo->prepare("SELECT
        DATE(wl.logged_at) AS workout_day,
        COALESCE(e.name, 'Exercise') AS exercise_name,
        COUNT(*) AS sets_count,
        SUM(COALESCE(wl.reps, 0)) AS total_reps,
        MAX(COALESCE(wl.weight, 0)) AS max_weight,
        SUM(COALESCE(wl.weight, 0) * COALESCE(wl.reps, 0)) AS total_volume,
        MIN(wl.logged_at) AS first_log_time,
        MAX(wl.logged_at) AS last_log_time
      FROM workout_logs wl
      LEFT JOIN exercises e ON e.exercise_id = wl.exercise_id
      WHERE wl.user_id = :user_id
      GROUP BY workout_day, wl.exercise_id, e.name
      ORDER BY workout_day DESC, exercise_name ASC");
    $workoutStmt->execute([':user_id' => $userId]);
    $workoutRows = $workoutStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $workoutByDay = [];
    foreach ($workoutRows as $row) {
      $day = (string)($row['workout_day'] ?? '');
      if ($day === '') {
        continue;
      }

      if (!isset($workoutByDay[$day])) {
        $workoutByDay[$day] = [
          'day' => $day,
          'first_log_time' => (string)($row['first_log_time'] ?? ''),
          'last_log_time' => (string)($row['last_log_time'] ?? ''),
          'total_sets' => 0,
          'total_volume' => 0.0,
          'exercises' => []
        ];
      }

      $workoutByDay[$day]['total_sets'] += (int)($row['sets_count'] ?? 0);
      $workoutByDay[$day]['total_volume'] += (float)($row['total_volume'] ?? 0);

      $currentFirst = $workoutByDay[$day]['first_log_time'];
      $currentLast = $workoutByDay[$day]['last_log_time'];
      $rowFirst = (string)($row['first_log_time'] ?? '');
      $rowLast = (string)($row['last_log_time'] ?? '');
      if ($rowFirst !== '' && ($currentFirst === '' || strcmp($rowFirst, $currentFirst) < 0)) {
        $workoutByDay[$day]['first_log_time'] = $rowFirst;
      }
      if ($rowLast !== '' && ($currentLast === '' || strcmp($rowLast, $currentLast) > 0)) {
        $workoutByDay[$day]['last_log_time'] = $rowLast;
      }

      $workoutByDay[$day]['exercises'][] = [
        'name' => (string)($row['exercise_name'] ?? 'Exercise'),
        'sets' => (int)($row['sets_count'] ?? 0),
        'reps' => (int)($row['total_reps'] ?? 0),
        'max_weight' => (float)($row['max_weight'] ?? 0)
      ];
    }

    $dayKeys = array_keys($workoutByDay);
    rsort($dayKeys);
    $dayKeys = array_slice($dayKeys, 0, 5);
    foreach ($dayKeys as $dayKey) {
      $entry = $workoutByDay[$dayKey];
      $dayDate = DateTime::createFromFormat('Y-m-d', $entry['day']);
      $dayLabel = $entry['day'];
      $daySub = '';

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
        $daySub = $dayDate->format('l, M j, Y');
      }

      $timeRange = '';
      if ($entry['first_log_time'] !== '' && $entry['last_log_time'] !== '') {
        $first = new DateTime($entry['first_log_time']);
        $last = new DateTime($entry['last_log_time']);
        $timeRange = $first->format('g:i A') . ' - ' . $last->format('g:i A');
      }

      $workoutDays[] = [
        'label' => $dayLabel,
        'sub' => $daySub,
        'time_range' => $timeRange,
        'total_sets' => (int)$entry['total_sets'],
        'total_volume' => round((float)$entry['total_volume'], 1),
        'exercises' => $entry['exercises']
      ];
    }
  }
} catch (Throwable $e) {
  // Keep template defaults if profile loading fails.
}

$initialParts = [];
if (isset($user) && is_array($user)) {
  $firstInitialSource = trim((string)($user['first_name'] ?? ''));
  $lastInitialSource = trim((string)($user['last_name'] ?? ''));
  if ($firstInitialSource !== '') {
    $initialParts[] = strtoupper(substr($firstInitialSource, 0, 1));
  }
  if ($lastInitialSource !== '') {
    $initialParts[] = strtoupper(substr($lastInitialSource, 0, 1));
  }
}

if (count($initialParts) < 2) {
  $nameParts = preg_split('/\s+/', trim($displayName));
  if (is_array($nameParts)) {
    foreach ($nameParts as $part) {
      if ($part !== '') {
        $initialParts[] = strtoupper(substr($part, 0, 1));
      }
      if (count($initialParts) >= 2) {
        break;
      }
    }
  }
}

if (!empty($initialParts)) {
  $profileInitials = implode('', array_slice($initialParts, 0, 2));
}
$activePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Dashboard</title>
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
            <h1 id="dashboardWelcome">Hey <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!</h1>
            <p id="dashboardWelcomeSub">Goal: <?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <div class="profile-container" style="margin-left:auto;">
            <div class="profile-content">
              <div class="profile-text">
                <strong class="profile-name" id="dashboardProfileName"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span class="profile-streak" id="dashboardFitnessLevel">🔥 Fitness Level • <?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="profile-pic">
                <span
                  class="profile-image"
                  aria-label="User initials"
                  style="display:flex;align-items:center;justify-content:center;font-family:'Chakra Petch',sans-serif;font-weight:700;font-size:16px;background:#ffcc00;color:#111;border:2px solid rgba(255,255,255,0.18);"
                ><?php echo htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </div>
        </header>

        <!-- NOTIFICATION BANNER -->
        <section class="notifications">
          <div class="notification-card attendance">
            <i class="bi bi-check-circle-fill"></i>
            <div class="notification-content">
              <h4><?php echo htmlspecialchars($attendanceTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
              <p><?php echo htmlspecialchars($attendanceDetail, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
          <div class="notification-card payment">
            <i class="bi bi-credit-card-fill"></i>
            <div class="notification-content">
              <h4>Payment Due Soon</h4>
              <p>Monthly membership renews in 5 days • ₱49.99</p>
            </div>
            <button class="notify-btn">Pay Now</button>
          </div>
        </section>

        <!-- BMI TRACKER SECTION -->
        <section class="bmi-section">
          <div class="bmi-card main-bmi">
            <div class="bmi-header">
              <h3>Your BMI Analysis</h3>
              <span class="bmi-badge <?php echo htmlspecialchars($bmiBadgeClass, ENT_QUOTES, 'UTF-8'); ?>" id="dashboardBmiBadge"><?php echo htmlspecialchars($bmiLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="bmi-display">
              <div class="bmi-circle">
                <div class="bmi-value" id="dashboardBmiValue"><?php echo htmlspecialchars($bmiValueText, ENT_QUOTES, 'UTF-8'); ?></div>
                <span class="bmi-unit">kg/m²</span>
              </div>
              <div class="bmi-info">
                <div class="info-row">
                  <span class="label">Height:</span
                  ><span class="value" id="dashboardHeight"><?php echo htmlspecialchars($heightText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                  <span class="label">Weight:</span
                  ><span class="value" id="dashboardWeight"><?php echo htmlspecialchars($weightText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                  <span class="label">Target:</span
                  ><span class="value" id="dashboardTargetWeight"><?php echo htmlspecialchars($targetWeightText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-row">
                  <span class="label">To Goal:</span
                  ><span class="value" id="dashboardBmiToGoal"><?php echo htmlspecialchars($toGoalText, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            </div>
            <div class="bmi-scale">
              <div class="scale-bar">
                <div class="scale-marker" id="dashboardBmiMarker" style="left: <?php echo htmlspecialchars($bmiMarkerLeft, ENT_QUOTES, 'UTF-8'); ?>%"></div>
              </div>
              <div class="scale-labels">
                <span>Underweight</span><span>Normal</span
                ><span>Overweight</span><span>Obese</span>
              </div>
            </div>
          </div>

          <div class="bmi-card progress-chart">
            <h3>Weight Progress</h3>
            <div class="chart-area">
              <div class="chart-line"></div>
            </div>
            <div class="progress-stats">
              <div class="stat-item">
                <span class="stat-label">This Month</span>
                <span class="stat-value"><?php echo htmlspecialchars($monthWeightChangeText, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Total Lost</span>
                <span class="stat-value"><?php echo htmlspecialchars($totalWeightChangeText, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </div>
        </section>

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

        <!-- ═══════════════════════════════════════════════════════
             LEADERBOARD SECTION (NEW)
        ══════════════════════════════════════════════════════════ -->
        <section class="leaderboard-section">
          <div class="section-header">
            <h3><i class="fas fa-trophy"></i> Gym Consistency Leaderboard</h3>
            <div class="leaderboard-filter">
              <button class="filter-btn active" data-period="weekly">Weekly</button>
              <button class="filter-btn" data-period="monthly">Monthly (30 days)</button>
              <button class="filter-btn" data-period="all_time">All Time</button>
            </div>
          </div>

          <div class="leaderboard-grid">
            <!-- Podium -->
            <div class="profile-card terms-card">
              <span class="podium-title">🏅 Top Performers</span>
              <div class="podium-row" id="leaderboardPodium">
                <!-- 2nd -->
                <div class="podium-member rank-2">
                  <div
                    class="avatar"
                    style="
                      width: 46px;
                      height: 46px;
                      border-radius: 50%;
                      background: linear-gradient(135deg, #94a3b8, #64748b);
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      color: #fff;
                      font-weight: 700;
                      font-size: 1rem;
                      border: 3px solid rgba(245, 158, 11, 0.5);
                    "
                  >
                    CC
                  </div>
                  <span class="member-name">Charles C.</span>
                  <span class="member-days">27 days</span>
                  <div class="podium-wrap">
                    <div class="podium-stand p2">2</div>
                  </div>
                </div>
                <!-- 1st -->
                <div class="podium-member rank-1">
                  <div
                    class="avatar"
                    style="
                      width: 58px;
                      height: 58px;
                      border-radius: 50%;
                      background: linear-gradient(135deg, #f59e0b, #d97706);
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      color: #fff;
                      font-weight: 700;
                      font-size: 1.1rem;
                      border: 3px solid #f59e0b;
                    "
                  >
                    SS
                  </div>
                  <span class="member-name">Sharien S. 👑</span>
                  <span class="member-days">30 days</span>
                  <div class="podium-wrap">
                    <div class="podium-stand p1">1</div>
                  </div>
                </div>
                <!-- 3rd -->
                <div class="podium-member rank-3">
                  <div
                    class="avatar"
                    style="
                      width: 46px;
                      height: 46px;
                      border-radius: 50%;
                      background: linear-gradient(135deg, #cd7f32, #92400e);
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      color: #fff;
                      font-weight: 700;
                      font-size: 1rem;
                      border: 3px solid rgba(245, 158, 11, 0.5);
                    "
                  >
                    CB
                  </div>
                  <span class="member-name">Christian B.</span>
                  <span class="member-days">25 days</span>
                  <div class="podium-wrap">
                    <div class="podium-stand p3">3</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Rankings List -->
            <div class="profile-card terms-card" id="leaderboardRankingList">
              <div class="ranking-row highlight">
                <span class="rank-num top">1</span>
                <div
                  class="rank-avatar"
                  style="
                    background: linear-gradient(135deg, #f59e0b20, #d97706, 20);
                    color: #d97706;
                    border-color: #fef3c7;
                    font-weight: 700;
                  "
                >
                  SS
                </div>
                <div class="rank-info">
                  <span class="rname"
                    >Sharien Salarda
                    <span style="font-size: 0.7rem; color: #f59e0b"
                      >★ You</span
                    ></span
                  >
                  <span class="rsub"
                    >135-day streak • 30 check-ins this week</span
                  >
                </div>
                <div class="rank-score">
                  <span class="rscore">9,840 pts</span>
                  <span class="rbadge up">▲ +2</span>
                </div>
              </div>

              <div class="ranking-row">
                <span class="rank-num top">2</span>
                <div class="rank-avatar">CC</div>
                <div class="rank-info">
                  <span class="rname">Charles C.</span>
                  <span class="rsub">98-day streak • 27 check-ins</span>
                </div>
                <div class="rank-score">
                  <span class="rscore">8,720 pts</span>
                  <span class="rbadge same">— 0</span>
                </div>
              </div>

              <div class="ranking-row">
                <span class="rank-num top">3</span>
                <div class="rank-avatar">CB</div>
                <div class="rank-info">
                  <span class="rname">Christian B.</span>
                  <span class="rsub">77-day streak • 25 check-ins</span>
                </div>
                <div class="rank-score">
                  <span class="rscore">7,560 pts</span>
                  <span class="rbadge up">▲ +1</span>
                </div>
              </div>

              <div class="ranking-row">
                <span class="rank-num">4</span>
                <div class="rank-avatar">LC</div>
                <div class="rank-info">
                  <span class="rname">Lance C.</span>
                  <span class="rsub">54-day streak • 22 check-ins</span>
                </div>
                <div class="rank-score">
                  <span class="rscore">6,340 pts</span>
                  <span class="rbadge down">▼ -1</span>
                </div>
              </div>

              <div class="ranking-row">
                <span class="rank-num">5</span>
                <div class="rank-avatar">SR</div>
                <div class="rank-info">
                  <span class="rname">Stephen R.</span>
                  <span class="rsub">42-day streak • 20 check-ins</span>
                </div>
                <div class="rank-score">
                  <span class="rscore">5,890 pts</span>
                  <span class="rbadge up">▲ +3</span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="member-records-section">
          <div class="section-header">
            <h3><i class="fas fa-clipboard-list"></i> Member Records</h3>
            <a class="view-all-link"
              >View All <i class="fas fa-chevron-right"></i
            ></a>
          </div>

          <div class="records-grid">
            <!-- Attendance Logs -->
            <div class="profile-card terms-card">
              <h4><i class="bi bi-calendar-check"></i> Attendance Logs</h4>
              <hr class="section-divider" />

              <div class="log-entry">
                <div class="log-dot"></div>
                <div class="log-info">
                  <span class="log-date">Monday, Feb 10</span>
                  <span class="log-time"
                    >Check-in: 6:28 AM &nbsp;•&nbsp; Check-out: 8:05 AM</span
                  >
                </div>
                <span class="log-status">Present</span>
              </div>

              <div class="log-entry">
                <div class="log-dot"></div>
                <div class="log-info">
                  <span class="log-date">Tuesday, Feb 11</span>
                  <span class="log-time"
                    >Check-in: 6:55 AM &nbsp;•&nbsp; Check-out: 7:50 AM</span
                  >
                </div>
                <span class="log-status">Present</span>
              </div>

              <div class="log-entry" style="border-color: #e5e7eb">
                <div class="log-dot" style="background: #e5e7eb"></div>
                <div class="log-info">
                  <span class="log-date">Wednesday, Feb 12</span>
                  <span class="log-time">Rest Day</span>
                </div>
                <span
                  class="log-status"
                  style="background: #f3f4f6; color: #6b7280"
                  >Absent</span
                >
              </div>

              <div class="log-entry">
                <div class="log-dot"></div>
                <div class="log-info">
                  <span class="log-date">Today, Feb 13</span>
                  <span class="log-time"
                    >Check-in: 6:30 AM &nbsp;•&nbsp; Ongoing</span
                  >
                </div>
                <span class="log-status">Present</span>
              </div>

              <a class="view-all-link"
                >See full log <i class="fas fa-chevron-right"></i
              ></a>
            </div>

            <!-- E-Receipts -->
            <div class="profile-card terms-card">
              <h4>
                <i class="bi bi-receipt"></i> E-Receipts & Payment History
              </h4>
              <hr class="section-divider" />

              <div class="receipt-entry">
                <div class="receipt-icon">
                  <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="receipt-info">
                  <span class="rname">Monthly Membership</span>
                  <span class="rdate"
                    >Jan 15, 2025 &nbsp;•&nbsp; Auto-renewed</span
                  >
                </div>
                <span class="receipt-amount">Php49.99</span>
              </div>

              <div class="receipt-entry">
                <div class="receipt-icon">
                  <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="receipt-info">
                  <span class="rname">Personal Training Session</span>
                  <span class="rdate"
                    >Jan 22, 2025 &nbsp;•&nbsp; 1 session</span
                  >
                </div>
                <span class="receipt-amount">Php35.00</span>
              </div>

              <div class="receipt-entry">
                <div class="receipt-icon">
                  <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="receipt-info">
                  <span class="rname">Monthly Membership</span>
                  <span class="rdate"
                    >Feb 15, 2025 &nbsp;•&nbsp; Auto-renewed</span
                  >
                </div>
                <span class="receipt-amount">Php49.99</span>
              </div>

              <a class="view-all-link"
                >Download all receipts <i class="fas fa-download"></i
              ></a>
            </div>
          </div>
        </section>
        <!-- DIET SCHEDULE SECTION -->
        <section class="diet-schedule-section">
          <div class="section-header">
            <h3>Weekly Diet Schedule</h3>
            <button class="edit-btn">
              <i class="bi bi-pencil"></i> Edit Schedule
            </button>
          </div>

          <div class="diet-calendar">
            <div class="diet-day">
              <div class="day-header">
                <span class="day-name">Monday</span>
                <span class="day-calories">2,100 cal</span>
              </div>
              <div class="meals">
                <div class="meal-item">
                  <span class="meal-time">Breakfast</span
                  ><span class="meal-name">Oatmeal with berries & nuts</span
                  ><span class="meal-cal">450 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Lunch</span
                  ><span class="meal-name">Grilled chicken salad</span
                  ><span class="meal-cal">550 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Snack</span
                  ><span class="meal-name">Protein shake & banana</span
                  ><span class="meal-cal">320 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Dinner</span
                  ><span class="meal-name">Salmon with quinoa</span
                  ><span class="meal-cal">680 cal</span>
                </div>
              </div>
            </div>

            <div class="diet-day active">
              <div class="day-header">
                <span class="day-name">Today</span>
                <span class="day-calories">2,050 cal</span>
              </div>
              <div class="meals">
                <div class="meal-item completed">
                  <span class="meal-time">Breakfast</span
                  ><span class="meal-name">Greek yogurt with granola</span
                  ><span class="meal-cal">420 cal</span>
                </div>
                <div class="meal-item completed">
                  <span class="meal-time">Lunch</span
                  ><span class="meal-name">Turkey wrap with veggies</span
                  ><span class="meal-cal">520 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Snack</span
                  ><span class="meal-name">Apple & almond butter</span
                  ><span class="meal-cal">280 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Dinner</span
                  ><span class="meal-name">Grilled steak & sweet potato</span
                  ><span class="meal-cal">730 cal</span>
                </div>
              </div>
            </div>

            <div class="diet-day">
              <div class="day-header">
                <span class="day-name">Wednesday</span>
                <span class="day-calories">2,200 cal</span>
              </div>
              <div class="meals">
                <div class="meal-item">
                  <span class="meal-time">Breakfast</span
                  ><span class="meal-name">Scrambled eggs & toast</span
                  ><span class="meal-cal">480 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Lunch</span
                  ><span class="meal-name">Pasta with vegetables</span
                  ><span class="meal-cal">600 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Snack</span
                  ><span class="meal-name">Mixed nuts</span
                  ><span class="meal-cal">300 cal</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Dinner</span
                  ><span class="meal-name">Chicken stir-fry</span
                  ><span class="meal-cal">720 cal</span>
                </div>
              </div>
            </div>
          </div>

          <div class="nutrition-summary">
            <div class="nutrition-card">
              <div class="nutrition-icon protein">
                <i class="fas fa-drumstick-bite"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Protein</span>
                <span class="nutrition-value">142g / 180g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: 79%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon carbs">
                <i class="fas fa-bread-slice"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Carbs</span>
                <span class="nutrition-value">218g / 250g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: 87%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon fats">
                <i class="fas fa-cheese"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Fats</span>
                <span class="nutrition-value">58g / 70g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: 83%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon fiber">
                <i class="fas fa-seedling"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Fiber</span>
                <span class="nutrition-value">28g / 35g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: 80%"></div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- GOALS & TRAINERS -->
        <section class="bottom-grid">
          <div class="box goals-box">
            <div class="box-header">
              <h3>Goals Progress</h3>
              <button class="btn-outline">View All</button>
            </div>
            <ul class="goals-list">
              <li>
                <div class="icon-circle cycling">
                  <i class="fas fa-bicycle"></i>
                </div>
                <div class="goal-info">
                  <span class="goal-name" id="dashboardPrimaryGoalName"><?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></span>
                  <div class="progress-container">
                    <div class="progress-bar yellow" style="width: 65%"></div>
                  </div>
                  <span class="days-count" id="dashboardPrimaryGoalHint">From your Profile settings</span>
                </div>
              </li>
              <li>
                <div class="icon-circle running">
                  <i class="fas fa-running"></i>
                </div>
                <div class="goal-info">
                  <span class="goal-name">Running</span>
                  <div class="progress-container">
                    <div class="progress-bar orange" style="width: 42%"></div>
                  </div>
                  <span class="days-count">42/100 days</span>
                </div>
              </li>
              <li>
                <div class="icon-circle water"><i class="fas fa-tint"></i></div>
                <div class="goal-info">
                  <span class="goal-name">Water Intake</span>
                  <div class="progress-container">
                    <div class="progress-bar blue" style="width: 85%"></div>
                  </div>
                  <span class="days-count">85/100 days</span>
                </div>
              </li>
              <li>
                <div class="icon-circle workout">
                  <i class="fas fa-dumbbell"></i>
                </div>
                <div class="goal-info">
                  <span class="goal-name">Strength Training</span>
                  <div class="progress-container">
                    <div class="progress-bar purple" style="width: 73%"></div>
                  </div>
                  <span class="days-count">73/100 days</span>
                </div>
              </li>
            </ul>
          </div>
        </section>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
    <script src="lightmode.js"></script>
    <script>
      const currentLeaderboardUserId = <?php echo (int)($_SESSION['id'] ?? 0); ?>;

      /* ── Star Rating ── */
      function setRating(n) {
        document.querySelectorAll(".star-btn").forEach((btn, i) => {
          btn.classList.toggle("active", i < n);
        });
      }

      function leaderboardEscapeHtml(value) {
        return String(value)
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/\"/g, "&quot;")
          .replace(/'/g, "&#39;");
      }

      function leaderboardInitials(name) {
        const parts = String(name || "")
          .trim()
          .split(/\s+/)
          .filter(Boolean);
        if (!parts.length) {
          return "??";
        }
        if (parts.length === 1) {
          return parts[0].substring(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }

      function renderLeaderboard(data) {
        const podium = document.getElementById("leaderboardPodium");
        const list = document.getElementById("leaderboardRankingList");
        if (!podium || !list) {
          return;
        }

        const top = Array.isArray(data.top_three) ? data.top_three : [];
        const rankings = Array.isArray(data.rankings) ? data.rankings : [];

        const first = top.find((entry) => entry.rank === 1) || null;
        const second = top.find((entry) => entry.rank === 2) || null;
        const third = top.find((entry) => entry.rank === 3) || null;

        function podiumCard(entry, rankClass, standClass, rankNumber) {
          if (!entry) {
            return `
              <div class="podium-member ${rankClass}">
                <div class="avatar" style="width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#374151,#111827);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;">--</div>
                <span class="member-name">No Data</span>
                <span class="member-days">0 pts</span>
                <div class="podium-wrap"><div class="podium-stand ${standClass}">${rankNumber}</div></div>
              </div>
            `;
          }

          const displayName = leaderboardEscapeHtml(entry.name);
          const initials = leaderboardEscapeHtml(leaderboardInitials(entry.name));
          const score = Number(entry.score || 0).toLocaleString();
          const avatarSize = rankNumber === 1 ? 58 : 46;
          const gradient = rankNumber === 1
            ? "linear-gradient(135deg, #f59e0b, #d97706)"
            : rankNumber === 2
              ? "linear-gradient(135deg, #94a3b8, #64748b)"
              : "linear-gradient(135deg, #cd7f32, #92400e)";

          return `
            <div class="podium-member ${rankClass}">
              <div class="avatar" style="width:${avatarSize}px;height:${avatarSize}px;border-radius:50%;background:${gradient};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;border:3px solid rgba(245,158,11,0.5);">${initials}</div>
              <span class="member-name">${displayName}</span>
              <span class="member-days">${score} pts</span>
              <div class="podium-wrap"><div class="podium-stand ${standClass}">${rankNumber}</div></div>
            </div>
          `;
        }

        podium.innerHTML = [
          podiumCard(second, "rank-2", "p2", 2),
          podiumCard(first, "rank-1", "p1", 1),
          podiumCard(third, "rank-3", "p3", 3)
        ].join("");

        if (!rankings.length) {
          list.innerHTML = '<div class="ranking-row"><div class="rank-info"><span class="rname">No leaderboard data yet.</span><span class="rsub">No attendance points found for this period.</span></div></div>';
          return;
        }

        list.innerHTML = rankings
          .map((entry) => {
            const rankClass = entry.rank <= 3 ? "top" : "";
            const isCurrentUser = Number(entry.user_id || 0) === Number(currentLeaderboardUserId || 0);
            const rowClass = ["ranking-row", entry.rank === 1 ? "highlight" : "", isCurrentUser ? "current-user" : ""]
              .filter(Boolean)
              .join(" ");
            const initials = leaderboardEscapeHtml(leaderboardInitials(entry.name));
            const name = leaderboardEscapeHtml(entry.name);
            const score = Number(entry.score || 0).toLocaleString();

            return `
              <div class="${rowClass}">
                <span class="rank-num ${rankClass}">${entry.rank}</span>
                <div class="rank-avatar">${initials}</div>
                <div class="rank-info">
                  <span class="rname">${name}</span>
                  <span class="rsub">${score} credited attendance points</span>
                </div>
                <div class="rank-score">
                  <span class="rscore">${score} pts</span>
                  <span class="rbadge same">—</span>
                </div>
              </div>
            `;
          })
          .join("");
      }

      function loadLeaderboard(period) {
        fetch(`../Database/get_leaderboard.php?period=${encodeURIComponent(period)}`)
          .then((response) => response.json())
          .then((data) => {
            if (!data.success) {
              throw new Error(data.error || "Unable to load leaderboard");
            }
            renderLeaderboard(data);
          })
          .catch(() => {
            const list = document.getElementById("leaderboardRankingList");
            if (list) {
              list.innerHTML = '<div class="ranking-row"><div class="rank-info"><span class="rname">Unable to load leaderboard.</span><span class="rsub">Please try again later.</span></div></div>';
            }
          });
      }

      document.querySelectorAll(".leaderboard-filter .filter-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
          document
            .querySelectorAll(".leaderboard-filter .filter-btn")
            .forEach((b) => b.classList.remove("active"));
          btn.classList.add("active");
          loadLeaderboard(btn.dataset.period || "weekly");
        });
      });

      function showPreviousWorkoutDayCard() {
        const hiddenCards = Array.from(document.querySelectorAll("[data-workout-hidden='1']"));
        if (!hiddenCards.length) {
          const btn = document.getElementById("showPreviousWorkoutBtn");
          if (btn) {
            btn.disabled = true;
          }
          return;
        }

        hiddenCards[0].style.display = "block";
        hiddenCards[0].removeAttribute("data-workout-hidden");

        if (!document.querySelector("[data-workout-hidden='1']")) {
          const btn = document.getElementById("showPreviousWorkoutBtn");
          if (btn) {
            btn.disabled = true;
          }
        }
      }

      function toggleWorkoutDayDetail(index) {
        const detailEl = document.getElementById(`workoutDayDetail-${index}`);
        if (!detailEl) {
          return;
        }

        const isHidden = detailEl.style.display === "none" || detailEl.style.display === "";
        detailEl.style.display = isHidden ? "block" : "none";
      }

      const showPreviousWorkoutBtn = document.getElementById("showPreviousWorkoutBtn");
      if (showPreviousWorkoutBtn) {
        showPreviousWorkoutBtn.addEventListener("click", showPreviousWorkoutDayCard);
      }

      loadLeaderboard("weekly");
    </script>
  </body>
</html>

