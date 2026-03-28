<?php
require_once __DIR__ . '/auth_user.php';

$firstName = 'Member';
$goal = 'Primary Goal';
$fitnessLevel = 'Not set';
$bmiValueText = '--';
$bmiLabel = 'Not set';
$userId = 0;

try {
  require __DIR__ . '/../Login/connection.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT id, username, first_name, last_name FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $profileStmt = $pdo->prepare('SELECT height_cm, weight_kg, fitness_level, goal FROM member_profiles WHERE user_id = :user_id LIMIT 1');
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($user['first_name'])) {
      $firstName = (string)$user['first_name'];
    } elseif (!empty($user['username'])) {
      $firstName = (string)$user['username'];
    }

    if (!empty($profile['goal'])) {
      $goal = (string)$profile['goal'];
    }
    if (!empty($profile['fitness_level'])) {
      $fitnessLevel = (string)$profile['fitness_level'];
    }

    $heightCm = isset($profile['height_cm']) && $profile['height_cm'] !== null ? (float)$profile['height_cm'] : null;
    $weightKg = isset($profile['weight_kg']) && $profile['weight_kg'] !== null ? (float)$profile['weight_kg'] : null;
    $bmi = null;

    if (($bmi === null || $bmi <= 0) && $heightCm !== null && $heightCm > 0 && $weightKg !== null && $weightKg > 0) {
      $hM = $heightCm / 100;
      $bmi = round($weightKg / ($hM * $hM), 1);
    }

    if ($bmi !== null && $bmi > 0) {
      $bmiValueText = number_format($bmi, 1, '.', '');
      if ($bmi < 18.5) {
        $bmiLabel = 'Underweight';
      } elseif ($bmi < 25) {
        $bmiLabel = 'Healthy';
      } elseif ($bmi < 30) {
        $bmiLabel = 'Overweight';
      } else {
        $bmiLabel = 'Obese';
      }
    }
  }
} catch (Throwable $e) {
  // Keep template defaults if profile loading fails.
}

$goalLower = function_exists('mb_strtolower') ? mb_strtolower($goal, 'UTF-8') : strtolower($goal);
$fitnessLower = function_exists('mb_strtolower') ? mb_strtolower($fitnessLevel, 'UTF-8') : strtolower($fitnessLevel);

$coachFocus = 'Build consistency with balanced training, recovery, and nutrition.';
if (strpos($goalLower, 'lose') !== false || strpos($goalLower, 'fat') !== false || strpos($goalLower, 'weight') !== false) {
  $coachFocus = 'Prioritize calorie control, higher protein intake, and regular cardio blocks.';
} elseif (strpos($goalLower, 'gain') !== false || strpos($goalLower, 'muscle') !== false || strpos($goalLower, 'bulk') !== false) {
  $coachFocus = 'Prioritize progressive overload, recovery sleep, and protein-rich meals.';
} elseif (strpos($goalLower, 'endurance') !== false || strpos($goalLower, 'run') !== false || strpos($goalLower, 'cardio') !== false) {
  $coachFocus = 'Prioritize cardio progression, pacing, and hydration planning.';
}

$quickPromptWorkout = 'Create a safe 45-minute workout for my goal to ' . $goalLower . ' at ' . $fitnessLower . ' level.';
$quickPromptMeal = 'Suggest a post-workout meal for my goal to ' . $goalLower . ' and explain portion sizes.';
$quickPromptRecovery = 'Give me a recovery plan for today, including sleep, hydration, and stretching.';
$quickPromptProgress = 'Review my progress this week and give me 3 practical improvements.';

$targetCalories = 2200;
$targetProtein = 140;
$targetCarbs = 240;
$targetFat = 70;

if (strpos($goalLower, 'lose') !== false || strpos($goalLower, 'fat') !== false || strpos($goalLower, 'weight') !== false) {
  $targetCalories = 1800;
  $targetProtein = 160;
  $targetCarbs = 170;
  $targetFat = 60;
} elseif (strpos($goalLower, 'gain') !== false || strpos($goalLower, 'muscle') !== false || strpos($goalLower, 'bulk') !== false) {
  $targetCalories = 2900;
  $targetProtein = 190;
  $targetCarbs = 350;
  $targetFat = 85;
} elseif (strpos($goalLower, 'endurance') !== false || strpos($goalLower, 'run') !== false || strpos($goalLower, 'cardio') !== false) {
  $targetCalories = 2500;
  $targetProtein = 150;
  $targetCarbs = 320;
  $targetFat = 70;
}

if (strpos($fitnessLower, 'beginner') !== false) {
  $targetCalories = max(1500, $targetCalories - 150);
} elseif (strpos($fitnessLower, 'advanced') !== false) {
  $targetCalories += 200;
  $targetProtein += 15;
}

$todayDate = date('Y-m-d');
$weekDates = [];
$dailyTotals = [];
$todayMealsByType = [
  'Breakfast' => [],
  'Lunch' => [],
  'Snack' => [],
  'Dinner' => [],
];

for ($i = 6; $i >= 0; $i--) {
  $dateValue = date('Y-m-d', strtotime('-' . $i . ' days'));
  $weekDates[] = $dateValue;
  $dailyTotals[$dateValue] = [
    'calories' => 0,
    'protein' => 0.0,
    'carbs' => 0.0,
    'fat' => 0.0,
    'meal_count' => 0,
  ];
}

$weekStart = $weekDates[0];
$weekEnd = $weekDates[count($weekDates) - 1];
$weeklyAttendanceCount = 0;
$weeklyWorkoutCount = 0;

if (isset($pdo) && $userId > 0) {
  try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS meal_logs (
      meal_id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      logged_date TEXT NOT NULL,
      meal_type TEXT NOT NULL,
      food_name TEXT NOT NULL,
      quantity REAL NOT NULL,
      calories INTEGER NOT NULL,
      protein REAL NOT NULL,
      carbs REAL NOT NULL,
      fat REAL NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_meal_logs_user_date ON meal_logs(user_id, logged_date)');

    $mealStmt = $pdo->prepare('SELECT meal_id, logged_date, meal_type, food_name, quantity, calories, protein, carbs, fat
      FROM meal_logs
      WHERE user_id = :user_id
      AND logged_date >= :start_date
      AND logged_date <= :end_date
      ORDER BY logged_date ASC, created_at ASC, meal_id ASC');
    $mealStmt->execute([
      ':user_id' => $userId,
      ':start_date' => $weekStart,
      ':end_date' => $weekEnd,
    ]);
    $mealRows = $mealStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($mealRows as $row) {
      $loggedDate = (string)($row['logged_date'] ?? '');
      if (!isset($dailyTotals[$loggedDate])) {
        continue;
      }

      $calories = (int)($row['calories'] ?? 0);
      $protein = (float)($row['protein'] ?? 0);
      $carbs = (float)($row['carbs'] ?? 0);
      $fat = (float)($row['fat'] ?? 0);

      $dailyTotals[$loggedDate]['calories'] += $calories;
      $dailyTotals[$loggedDate]['protein'] += $protein;
      $dailyTotals[$loggedDate]['carbs'] += $carbs;
      $dailyTotals[$loggedDate]['fat'] += $fat;
      $dailyTotals[$loggedDate]['meal_count'] += 1;

      $mealType = (string)($row['meal_type'] ?? '');
      if ($loggedDate === $todayDate && isset($todayMealsByType[$mealType])) {
        $todayMealsByType[$mealType][] = [
          'food_name' => (string)($row['food_name'] ?? ''),
          'quantity' => (float)($row['quantity'] ?? 0),
          'calories' => $calories,
          'protein' => $protein,
          'carbs' => $carbs,
          'fat' => $fat,
        ];
      }
    }

    $attendanceStmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE user_id = :user_id AND date(datetime) >= :start_date AND date(datetime) <= :end_date');
    $attendanceStmt->execute([
      ':user_id' => $userId,
      ':start_date' => $weekStart,
      ':end_date' => $weekEnd,
    ]);
    $weeklyAttendanceCount = (int)$attendanceStmt->fetchColumn();

    $workoutStmt = $pdo->prepare('SELECT COUNT(*) FROM workout_logs WHERE user_id = :user_id AND date(logged_at) >= :start_date AND date(logged_at) <= :end_date');
    $workoutStmt->execute([
      ':user_id' => $userId,
      ':start_date' => $weekStart,
      ':end_date' => $weekEnd,
    ]);
    $weeklyWorkoutCount = (int)$workoutStmt->fetchColumn();
  } catch (Throwable $e) {
    // Keep defaults if stats loading fails.
  }
}

$todayTotals = $dailyTotals[$todayDate] ?? ['calories' => 0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0, 'meal_count' => 0];

$progressPct = static function ($value, $target): int {
  $targetValue = (float)$target;
  if ($targetValue <= 0) {
    return 0;
  }

  return (int)max(0, min(100, round((((float)$value) / $targetValue) * 100)));
};

$caloriesPct = $progressPct($todayTotals['calories'], $targetCalories);
$proteinPct = $progressPct($todayTotals['protein'], $targetProtein);
$carbsPct = $progressPct($todayTotals['carbs'], $targetCarbs);
$fatPct = $progressPct($todayTotals['fat'], $targetFat);

$weeklyWorkoutTarget = 4;
if (strpos($fitnessLower, 'beginner') !== false) {
  $weeklyWorkoutTarget = 3;
} elseif (strpos($fitnessLower, 'advanced') !== false) {
  $weeklyWorkoutTarget = 5;
}
$goalProgressPct = $progressPct($weeklyWorkoutCount, $weeklyWorkoutTarget);

$smartSuggestions = [];
if ($proteinPct < 70) {
  $smartSuggestions[] = 'Protein intake is behind target today. Add a high-protein snack after training.';
}
if ($caloriesPct < 60) {
  $smartSuggestions[] = 'Calories are still low for your target. Add one balanced meal before day end.';
}
if ($weeklyWorkoutCount < $weeklyWorkoutTarget) {
  $smartSuggestions[] = 'You are at ' . $weeklyWorkoutCount . '/' . $weeklyWorkoutTarget . ' workout sessions this week. Schedule one extra session.';
}
if ($weeklyAttendanceCount < 3) {
  $smartSuggestions[] = 'Gym attendance is light this week. Lock in at least one visit in the next 24 hours.';
}
if (count($smartSuggestions) < 3) {
  $smartSuggestions[] = 'Use the AI quick prompts to get a specific workout and meal recommendation for your current stats.';
}
?>
<?php $activePage = 'myplan'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-OERcA2zY1OHt4q4Fv8B+U7MeM3NnN3KK2eEbV5t8JSaI1zlzW3URy9Bv1WTRi7v8Q" crossorigin="anonymous">
    <link rel="stylesheet" href="user.css" />
    <link rel="stylesheet" href="meal-tracker.css" />
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
    <style>
      .plan-ai-shell {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 16px;
      }

      .plan-ai-card {
        background: #141414;
        border: 1px solid #2e2e2e;
        border-radius: 12px;
        padding: 18px;
      }

      .plan-ai-kicker {
        font-size: 0.72rem;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        color: #a0a0a0;
        margin-bottom: 10px;
      }

      .plan-ai-title {
        font-size: 1rem;
        margin-bottom: 12px;
      }

      .plan-ai-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 0;
        padding: 0;
      }

      .plan-ai-list li {
        font-size: 0.86rem;
        color: #dedede;
        background: #101010;
        border: 1px solid #2b2b2b;
        border-radius: 8px;
        padding: 10px 12px;
      }

      .plan-ai-query-form {
        display: flex;
        gap: 10px;
      }

      .plan-ai-query {
        flex: 1;
        background: #0f0f0f;
        color: #fff;
        border: 1px solid #383838;
        border-radius: 8px;
        padding: 11px 12px;
        font-size: 0.87rem;
        outline: none;
      }

      .plan-ai-query:focus {
        border-color: #ffcc00;
      }

      .plan-ai-submit {
        border: none;
        border-radius: 8px;
        padding: 11px 14px;
        font-weight: 700;
        color: #111;
        background: #ffcc00;
        cursor: pointer;
      }

      .plan-ai-chip-grid {
        margin-top: 12px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
      }

      .plan-ai-chip {
        text-align: left;
        border: 1px solid #3a3a3a;
        background: #101010;
        color: #ededed;
        border-radius: 8px;
        padding: 10px 11px;
        font-size: 0.8rem;
        cursor: pointer;
      }

      .plan-ai-chip:hover {
        border-color: #ffcc00;
        color: #ffcc00;
      }

      .plan-ai-note {
        margin-top: 10px;
        font-size: 0.74rem;
        color: #9a9a9a;
      }

      @media (max-width: 980px) {
        .plan-ai-shell {
          grid-template-columns: 1fr;
        }

        .plan-ai-chip-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>
  </head>

  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <header class="topbar">
          <div class="welcome">
            <h1>My Plan</h1>
            <p id="myPlanWelcome">Hi <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>! Goal: <?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?>)</p>
          </div>
        </header>

        <!-- AI ADVISER -->
        <section class="ai-adviser-section">
          <div class="section-header">
            <h3><i class="fas fa-robot"></i> AI Fitness Adviser</h3>
            <a class="btn-outline" href="AI_ADVISOR.php">Open Full Chat</a>
          </div>

          <div class="plan-ai-shell">
            <article class="plan-ai-card">
              <p class="plan-ai-kicker">Today&apos;s coach brief</p>
              <h4 class="plan-ai-title">Built from your current profile</h4>
              <ul class="plan-ai-list">
                <li><strong>Goal:</strong> <?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>Fitness level:</strong> <?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?></li>
                <li><strong>BMI:</strong> <?php echo htmlspecialchars($bmiValueText, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($bmiLabel, ENT_QUOTES, 'UTF-8'); ?>)</li>
                <li><strong>Focus:</strong> <?php echo htmlspecialchars($coachFocus, ENT_QUOTES, 'UTF-8'); ?></li>
              </ul>
              <p class="plan-ai-note">The quick actions send your question to the real AI adviser and continue in the full chat page.</p>
            </article>

            <article class="plan-ai-card">
              <p class="plan-ai-kicker">Ask AI now</p>
              <h4 class="plan-ai-title">Get a focused recommendation</h4>
              <form class="plan-ai-query-form" method="POST" action="process_AI.php" id="planAiForm">
                <?php echo fitstop_csrf_input(); ?>
                <input
                  class="plan-ai-query"
                  type="text"
                  id="planAiQuery"
                  name="query"
                  maxlength="500"
                  placeholder="Example: Build my workout for today based on my goal"
                  required
                />
                <button class="plan-ai-submit" type="submit">Ask</button>
              </form>

              <div class="plan-ai-chip-grid">
                <button class="plan-ai-chip" type="button" data-prompt="<?php echo htmlspecialchars($quickPromptWorkout, ENT_QUOTES, 'UTF-8'); ?>">Build today&apos;s workout</button>
                <button class="plan-ai-chip" type="button" data-prompt="<?php echo htmlspecialchars($quickPromptMeal, ENT_QUOTES, 'UTF-8'); ?>">Suggest my post-workout meal</button>
                <button class="plan-ai-chip" type="button" data-prompt="<?php echo htmlspecialchars($quickPromptRecovery, ENT_QUOTES, 'UTF-8'); ?>">Plan my recovery for tonight</button>
                <button class="plan-ai-chip" type="button" data-prompt="<?php echo htmlspecialchars($quickPromptProgress, ENT_QUOTES, 'UTF-8'); ?>">Review my weekly progress</button>
              </div>
            </article>
          </div>
        </section>

<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

date_default_timezone_set('Asia/Manila');

if (!isset($conn)) {
  $dbPath = __DIR__ . '/../Database/DB.sqlite';
  try {
    $conn = new PDO("sqlite:" . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
  }
}

if (!function_exists('progressPct')) {
  function progressPct($value, $target) {
    $value = (float)$value;
    $target = (float)$target;
    if ($target <= 0) {
      return 0;
    }
    $pct = round(($value / $target) * 100);
    if ($pct < 0) $pct = 0;
    if ($pct > 100) $pct = 100;
    return $pct;
  }
}

if (!function_exists('myplan_build_live_nutrition_data')) {
  function myplan_build_live_nutrition_data($conn, $goal, $fitnessLevel, $weeklyWorkoutCount, $weeklyWorkoutTarget, $weeklyAttendanceCount) {
    $userId = 0;
    if (isset($_SESSION['user_id'])) {
      $userId = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['id'])) {
      $userId = (int)$_SESSION['id'];
    }

    $todayDate = date('Y-m-d');
    $weekDates = [];
    for ($i = 6; $i >= 0; $i--) {
      $weekDates[] = date('Y-m-d', strtotime("-{$i} day"));
    }

    $dailyTotals = [];
    foreach ($weekDates as $dateValue) {
      $dailyTotals[$dateValue] = [
        'meal_count' => 0,
        'entry_count' => 0
      ];
    }

    $todayTotals = [
      'meal_count' => 0,
      'entry_count' => 0
    ];

    if ($userId > 0) {
      $weekStart = $weekDates[0];
      $weekEnd = $weekDates[count($weekDates) - 1];

      $nutritionStmt = $conn->prepare("
        SELECT 
          log_date,
          COUNT(*) AS entry_count,
          COUNT(DISTINCT meal_type) AS meal_count
        FROM meal_logs
        WHERE user_id = ? AND log_date BETWEEN ? AND ?
        GROUP BY log_date
        ORDER BY log_date ASC
      ");
      $nutritionStmt->execute([$userId, $weekStart, $weekEnd]);
      $nutritionRows = $nutritionStmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($nutritionRows as $row) {
        $logDate = $row['log_date'];
        if (isset($dailyTotals[$logDate])) {
          $dailyTotals[$logDate] = [
            'meal_count' => (int)$row['meal_count'],
            'entry_count' => (int)$row['entry_count']
          ];
        }
      }

      $todayTotals = $dailyTotals[$todayDate] ?? $todayTotals;
    }

    $targetMeals = 4;
    $targetEntries = 4;

    $mealsPct = progressPct($todayTotals['meal_count'], $targetMeals);
    $entriesPct = progressPct($todayTotals['entry_count'], $targetEntries);

    $weeklyNutritionEntries = 0;
    foreach ($dailyTotals as $dayData) {
      $weeklyNutritionEntries += (int)$dayData['entry_count'];
    }

    $goalProgressPct = progressPct((int)$weeklyWorkoutCount, (int)$weeklyWorkoutTarget);

    $smartSuggestions = [];

    if ($todayTotals['entry_count'] === 0) {
      $smartSuggestions[] = "You have no meal logs today. Start with your first meal entry to keep your nutrition tracking updated.";
    }
    if ($todayTotals['meal_count'] < 3) {
      $smartSuggestions[] = "You have only logged {$todayTotals['meal_count']} meal types today. Try spreading your intake across breakfast, lunch, dinner, and snacks.";
    }
    if ($weeklyNutritionEntries >= 20) {
      $smartSuggestions[] = "Great consistency this week. Your meal logging habit is staying active across multiple days.";
    }

    if (empty($smartSuggestions)) {
      $smartSuggestions[] = "Your nutrition logs are on track. Keep recording meals consistently to improve your weekly snapshot.";
    }

    $calendarDays = [];
    foreach ($weekDates as $dateValue) {
      $isToday = $dateValue === $todayDate;
      $dayTotals = $dailyTotals[$dateValue] ?? ['meal_count' => 0, 'entry_count' => 0];
      $calendarDays[] = [
        'date' => $dateValue,
        'is_today' => $isToday,
        'day_name' => $isToday ? 'Today' : date('l', strtotime($dateValue)),
        'meal_count' => (int)$dayTotals['meal_count'],
        'entry_count' => (int)$dayTotals['entry_count'],
        'meal_pct' => (int)progressPct($dayTotals['meal_count'], $targetMeals),
        'entry_pct' => (int)progressPct($dayTotals['entry_count'], $targetEntries)
      ];
    }

    return [
      'todayTotals' => [
        'meal_count' => (int)$todayTotals['meal_count'],
        'entry_count' => (int)$todayTotals['entry_count']
      ],
      'targets' => [
        'targetMeals' => (int)$targetMeals,
        'targetEntries' => (int)$targetEntries
      ],
      'percentages' => [
        'mealsPct' => (int)$mealsPct,
        'entriesPct' => (int)$entriesPct,
        'goalProgressPct' => (int)$goalProgressPct,
        'attendancePct' => (int)progressPct((int)$weeklyAttendanceCount, 4)
      ],
      'goal' => (string)$goal,
      'fitnessLevel' => (string)$fitnessLevel,
      'weeklyWorkoutCount' => (int)$weeklyWorkoutCount,
      'weeklyWorkoutTarget' => (int)$weeklyWorkoutTarget,
      'weeklyAttendanceCount' => (int)$weeklyAttendanceCount,
      'calendarDays' => $calendarDays,
      'smartSuggestions' => $smartSuggestions
    ];
  }
}

$goal = $goal ?? 'Fitness Goal';
$fitnessLevel = $fitnessLevel ?? 'beginner';
$fitnessLower = strtolower($fitnessLevel);
$weeklyWorkoutCount = isset($weeklyWorkoutCount) ? (int)$weeklyWorkoutCount : 0;
$weeklyWorkoutTarget = isset($weeklyWorkoutTarget) ? (int)$weeklyWorkoutTarget : 5;
$weeklyAttendanceCount = isset($weeklyAttendanceCount) ? (int)$weeklyAttendanceCount : 0;

if (isset($_GET['nutrition_live']) && $_GET['nutrition_live'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(
    myplan_build_live_nutrition_data(
      $conn,
      $goal,
      $fitnessLevel,
      $weeklyWorkoutCount,
      $weeklyWorkoutTarget,
      $weeklyAttendanceCount
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
  );
  exit;
}

$liveNutritionData = myplan_build_live_nutrition_data(
  $conn,
  $goal,
  $fitnessLevel,
  $weeklyWorkoutCount,
  $weeklyWorkoutTarget,
  $weeklyAttendanceCount
);

$todayTotals = $liveNutritionData['todayTotals'];
$targetMeals = $liveNutritionData['targets']['targetMeals'];
$targetEntries = $liveNutritionData['targets']['targetEntries'];
$mealsPct = $liveNutritionData['percentages']['mealsPct'];
$entriesPct = $liveNutritionData['percentages']['entriesPct'];
$goalProgressPct = $liveNutritionData['percentages']['goalProgressPct'];
$attendancePct = $liveNutritionData['percentages']['attendancePct'];
$smartSuggestions = $liveNutritionData['smartSuggestions'];

?>

<!-- ── NUTRITION SNAPSHOT ── -->
<section class="diet-schedule-section" id="liveNutritionSection">
  <div class="section-header">
    <h3>Weekly Nutrition Snapshot</h3>
    <span class="btn-outline">Live from your meal logs</span>
  </div>

  <div class="diet-calendar" id="liveDietCalendar">
    <?php foreach ($liveNutritionData['calendarDays'] as $dayData): ?>
    <div class="diet-day<?php echo $dayData['is_today'] ? ' active' : ''; ?>">
      <div class="day-header">
        <span class="day-name"><?php echo htmlspecialchars($dayData['day_name'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="day-calories"><?php echo (int)$dayData['entry_count']; ?> entries</span>
      </div>

      <div class="meals">
        <div class="meal-item<?php echo $dayData['is_today'] ? ' completed' : ''; ?>">
          <span class="meal-time">Meal Types</span>
          <span class="meal-name"><?php echo (int)$dayData['meal_count']; ?> types logged</span>
          <span class="meal-cal"><?php echo (int)$dayData['meal_pct']; ?>%</span>
        </div>

        <div class="meal-item<?php echo $dayData['is_today'] ? ' completed' : ''; ?>">
          <span class="meal-time">Entries</span>
          <span class="meal-name"><?php echo (int)$dayData['entry_count']; ?> food entries</span>
          <span class="meal-cal"><?php echo (int)$dayData['entry_pct']; ?>%</span>
        </div>

        <div class="meal-item">
          <span class="meal-time">Target</span>
          <span class="meal-name"><?php echo (int)$targetMeals; ?> meal types</span>
          <span class="meal-cal"><?php echo (int)$targetEntries; ?> entries</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="nutrition-summary">
    <div class="nutrition-card">
      <div class="nutrition-icon fats">
        <i class="fas fa-utensils"></i>
      </div>
      <div class="nutrition-info">
        <span class="nutrition-label">Meal Types</span>
        <span class="nutrition-value" id="liveMealTypesValue"><?php echo (int)$todayTotals['meal_count']; ?> / <?php echo (int)$targetMeals; ?></span>
        <div class="nutrition-bar">
          <div class="bar-fill" id="liveMealTypesBar" style="width: <?php echo $mealsPct; ?>%"></div>
        </div>
      </div>
    </div>

    <div class="nutrition-card">
      <div class="nutrition-icon fiber">
        <i class="fas fa-clipboard-list"></i>
      </div>
      <div class="nutrition-info">
        <span class="nutrition-label">Food Entries</span>
        <span class="nutrition-value" id="liveEntriesValue"><?php echo (int)$todayTotals['entry_count']; ?> / <?php echo (int)$targetEntries; ?> entries</span>
        <div class="nutrition-bar">
          <div class="bar-fill" id="liveEntriesBar" style="width: <?php echo $entriesPct; ?>%"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

date_default_timezone_set('Asia/Manila');

if (!isset($conn)) {
  $dbPath = __DIR__ . '/../Database/DB.sqlite';
  try {
    $conn = new PDO("sqlite:" . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
  }
}

if (!function_exists('progressPct')) {
  function progressPct($value, $target) {
    $value = (float)$value;
    $target = (float)$target;
    if ($target <= 0) {
      return 0;
    }
    $pct = round(($value / $target) * 100);
    if ($pct < 0) $pct = 0;
    if ($pct > 100) $pct = 100;
    return $pct;
  }
}

if (!function_exists('myplan_build_live_dashboard_data')) {
  function myplan_build_live_dashboard_data($conn, $goal, $fitnessLevel) {
    $userId = 0;
    if (isset($_SESSION['user_id'])) {
      $userId = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['id'])) {
      $userId = (int)$_SESSION['id'];
    }

    $goalLower = function_exists('mb_strtolower') ? mb_strtolower((string)$goal, 'UTF-8') : strtolower((string)$goal);
    $fitnessLower = function_exists('mb_strtolower') ? mb_strtolower((string)$fitnessLevel, 'UTF-8') : strtolower((string)$fitnessLevel);

    $todayDate = date('Y-m-d');
    $weekDates = [];
    for ($i = 6; $i >= 0; $i--) {
      $weekDates[] = date('Y-m-d', strtotime("-{$i} day"));
    }

    $dailyTotals = [];
    foreach ($weekDates as $dateValue) {
      $dailyTotals[$dateValue] = [
        'meal_count' => 0,
        'entry_count' => 0
      ];
    }

    $todayTotals = [
      'meal_count' => 0,
      'entry_count' => 0
    ];

    $weeklyAttendanceCount = 0;
    $weeklyWorkoutCount = 0;

    $targetMeals = 4;
    $targetEntries = 4;
    $weeklyWorkoutTarget = 4;

    if (strpos($fitnessLower, 'beginner') !== false) {
      $weeklyWorkoutTarget = 3;
    } elseif (strpos($fitnessLower, 'advanced') !== false) {
      $weeklyWorkoutTarget = 5;
    }

    if ($userId > 0) {
      $weekStart = $weekDates[0];
      $weekEnd = $weekDates[count($weekDates) - 1];

      try {
        $nutritionStmt = $conn->prepare("
          SELECT 
            log_date,
            COUNT(*) AS entry_count,
            COUNT(DISTINCT meal_type) AS meal_count
          FROM meal_logs
          WHERE user_id = ? AND log_date BETWEEN ? AND ?
          GROUP BY log_date
          ORDER BY log_date ASC
        ");
        $nutritionStmt->execute([$userId, $weekStart, $weekEnd]);
        $nutritionRows = $nutritionStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($nutritionRows as $row) {
          $logDate = (string)$row['log_date'];
          if (isset($dailyTotals[$logDate])) {
            $dailyTotals[$logDate] = [
              'meal_count' => (int)$row['meal_count'],
              'entry_count' => (int)$row['entry_count']
            ];
          }
        }

        $todayTotals = $dailyTotals[$todayDate] ?? $todayTotals;
      } catch (Throwable $e) {
      }

      try {
        $attendanceStmt = $conn->prepare("
          SELECT COUNT(*)
          FROM attendance
          WHERE user_id = ?
          AND date(datetime) BETWEEN ? AND ?
        ");
        $attendanceStmt->execute([$userId, $weekStart, $weekEnd]);
        $weeklyAttendanceCount = (int)$attendanceStmt->fetchColumn();
      } catch (Throwable $e) {
        $weeklyAttendanceCount = 0;
      }

      try {
        $workoutStmt = $conn->prepare("
          SELECT COUNT(*)
          FROM workout_logs
          WHERE user_id = ?
          AND date(logged_at) BETWEEN ? AND ?
        ");
        $workoutStmt->execute([$userId, $weekStart, $weekEnd]);
        $weeklyWorkoutCount = (int)$workoutStmt->fetchColumn();
      } catch (Throwable $e) {
        $weeklyWorkoutCount = 0;
      }
    }

    $mealsPct = progressPct($todayTotals['meal_count'], $targetMeals);
    $entriesPct = progressPct($todayTotals['entry_count'], $targetEntries);
    $goalProgressPct = progressPct($weeklyWorkoutCount, $weeklyWorkoutTarget);
    $attendancePct = progressPct($weeklyAttendanceCount, 4);

    $weeklyNutritionEntries = 0;
    foreach ($dailyTotals as $dayData) {
      $weeklyNutritionEntries += (int)$dayData['entry_count'];
    }

    $smartSuggestions = [];

    if (strpos($goalLower, 'lose') !== false || strpos($goalLower, 'fat') !== false || strpos($goalLower, 'weight') !== false) {
      if ($todayTotals['entry_count'] < 3) {
        $smartSuggestions[] = 'For fat loss, keep meal logging consistent so portions stay controlled.';
      }
      if ($weeklyWorkoutCount < $weeklyWorkoutTarget) {
        $smartSuggestions[] = 'You are below your weekly workout target. Add one more cardio or full-body session.';
      }
    } elseif (strpos($goalLower, 'gain') !== false || strpos($goalLower, 'muscle') !== false || strpos($goalLower, 'bulk') !== false) {
      if ($todayTotals['entry_count'] < 4) {
        $smartSuggestions[] = 'For muscle gain, spread meals more evenly through the day.';
      }
      if ($weeklyWorkoutCount < $weeklyWorkoutTarget) {
        $smartSuggestions[] = 'Add another strength session this week to stay aligned with muscle gain.';
      }
    } elseif (strpos($goalLower, 'endurance') !== false || strpos($goalLower, 'run') !== false || strpos($goalLower, 'cardio') !== false) {
      $smartSuggestions[] = 'Keep your meal timing regular so your energy stays steady for endurance work.';
      if ($weeklyWorkoutCount < $weeklyWorkoutTarget) {
        $smartSuggestions[] = 'You still have room for another cardio-focused workout this week.';
      }
    } else {
      if ($todayTotals['meal_count'] < 3) {
        $smartSuggestions[] = 'Try completing more meal types today to build a more balanced routine.';
      }
    }

    if ($todayTotals['entry_count'] === 0) {
      $smartSuggestions[] = 'You have no meal logs today. Start with your first meal entry.';
    }

    if ($todayTotals['meal_count'] < 3) {
      $smartSuggestions[] = 'You have only logged ' . $todayTotals['meal_count'] . ' meal types today. Try spreading meals across breakfast, lunch, dinner, and snacks.';
    }

    if ($weeklyAttendanceCount < 3) {
      $smartSuggestions[] = 'Gym attendance is still light this week. Lock in another visit to stay on track.';
    }

    if ($weeklyNutritionEntries >= 20) {
      $smartSuggestions[] = 'Great consistency this week. Your meal logging habit is staying active.';
    }

    if (empty($smartSuggestions)) {
      $smartSuggestions[] = 'Your current routine is on track. Keep logging meals and finishing workouts consistently.';
    }

    $smartSuggestions = array_values(array_unique($smartSuggestions));
    $smartSuggestions = array_slice($smartSuggestions, 0, 4);

    $calendarDays = [];
    foreach ($weekDates as $dateValue) {
      $isToday = $dateValue === $todayDate;
      $dayTotals = $dailyTotals[$dateValue] ?? ['meal_count' => 0, 'entry_count' => 0];
      $calendarDays[] = [
        'date' => $dateValue,
        'is_today' => $isToday,
        'day_name' => $isToday ? 'Today' : date('l', strtotime($dateValue)),
        'meal_count' => (int)$dayTotals['meal_count'],
        'entry_count' => (int)$dayTotals['entry_count'],
        'meal_pct' => (int)progressPct($dayTotals['meal_count'], $targetMeals),
        'entry_pct' => (int)progressPct($dayTotals['entry_count'], $targetEntries)
      ];
    }

    return [
      'todayTotals' => [
        'meal_count' => (int)$todayTotals['meal_count'],
        'entry_count' => (int)$todayTotals['entry_count']
      ],
      'targets' => [
        'targetMeals' => (int)$targetMeals,
        'targetEntries' => (int)$targetEntries
      ],
      'percentages' => [
        'mealsPct' => (int)$mealsPct,
        'entriesPct' => (int)$entriesPct,
        'goalProgressPct' => (int)$goalProgressPct,
        'attendancePct' => (int)$attendancePct
      ],
      'goal' => (string)$goal,
      'fitnessLevel' => (string)$fitnessLevel,
      'weeklyWorkoutCount' => (int)$weeklyWorkoutCount,
      'weeklyWorkoutTarget' => (int)$weeklyWorkoutTarget,
      'weeklyAttendanceCount' => (int)$weeklyAttendanceCount,
      'calendarDays' => $calendarDays,
      'smartSuggestions' => $smartSuggestions
    ];
  }
}

$goal = $goal ?? 'Fitness Goal';
$fitnessLevel = $fitnessLevel ?? 'beginner';
$fitnessLower = strtolower($fitnessLevel);

if (isset($_GET['dashboard_live']) && $_GET['dashboard_live'] === '1') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(
    myplan_build_live_dashboard_data($conn, $goal, $fitnessLevel),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
  );
  exit;
}

$liveDashboardData = myplan_build_live_dashboard_data($conn, $goal, $fitnessLevel);

$todayTotals = $liveDashboardData['todayTotals'];
$targetMeals = $liveDashboardData['targets']['targetMeals'];
$targetEntries = $liveDashboardData['targets']['targetEntries'];
$mealsPct = $liveDashboardData['percentages']['mealsPct'];
$entriesPct = $liveDashboardData['percentages']['entriesPct'];
$goalProgressPct = $liveDashboardData['percentages']['goalProgressPct'];
$attendancePct = $liveDashboardData['percentages']['attendancePct'];
$weeklyWorkoutCount = $liveDashboardData['weeklyWorkoutCount'];
$weeklyWorkoutTarget = $liveDashboardData['weeklyWorkoutTarget'];
$weeklyAttendanceCount = $liveDashboardData['weeklyAttendanceCount'];
$smartSuggestions = $liveDashboardData['smartSuggestions'];
?>

<!-- ── GOALS & SUGGESTIONS ── -->
<section class="bottom-grid" id="liveGoalsSection">
  <div class="box goals-box">
    <div class="box-header">
      <h3>Goals Progress</h3>
      <span class="btn-outline">Weekly metrics</span>
    </div>

    <ul class="goals-list">
      <li>
        <div class="icon-circle cycling">
          <i class="fas fa-bicycle"></i>
        </div>
        <div class="goal-info">
          <span class="goal-name" id="myPlanPrimaryGoalName"><?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="progress-container">
            <div class="progress-bar yellow" id="liveGoalProgressBar" style="width: <?php echo $goalProgressPct; ?>%"></div>
          </div>
          <span class="days-count" id="myPlanPrimaryGoalHint"><?php echo $weeklyWorkoutCount; ?>/<?php echo $weeklyWorkoutTarget; ?> workout sessions this week (<?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?>)</span>
        </div>
      </li>

      <li>
        <div class="icon-circle running">
          <i class="fas fa-running"></i>
        </div>
        <div class="goal-info">
          <span class="goal-name">Gym Attendance</span>
          <div class="progress-container">
            <div class="progress-bar orange" id="liveAttendanceBar" style="width: <?php echo $attendancePct; ?>%"></div>
          </div>
          <span class="days-count" id="liveAttendanceText"><?php echo $weeklyAttendanceCount; ?> check-ins this week</span>
        </div>
      </li>

      <li>
        <div class="icon-circle workout">
          <i class="fas fa-utensils"></i>
        </div>
        <div class="goal-info">
          <span class="goal-name">Meal Logging</span>
          <div class="progress-container">
            <div class="progress-bar purple" id="liveMealLoggingBar" style="width: <?php echo $entriesPct; ?>%"></div>
          </div>
          <span class="days-count" id="liveMealLoggingText"><?php echo (int)$todayTotals['entry_count']; ?> / <?php echo (int)$targetEntries; ?> food entries today</span>
        </div>
      </li>
    </ul>
  </div>

  <div class="ai-suggestions-box">
    <h4>Personalized Suggestions</h4>
    <div class="suggestion-cards" id="liveSuggestionCards">
      <?php foreach ($smartSuggestions as $suggestion): ?>
      <div class="suggestion-card" data-tab="workout" style="display: flex">
        <div class="suggestion-icon workout">
          <i class="fas fa-bolt"></i>
        </div>
        <div class="suggestion-text">
          <h5>Action Item</h5>
          <p><?php echo htmlspecialchars($suggestion, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── NUTRITION SNAPSHOT ── -->
<section class="diet-schedule-section" id="liveNutritionSection">
  <div class="section-header">
    <h3>Weekly Nutrition Snapshot</h3>
    <span class="btn-outline">Live from your meal logs</span>
  </div>

  <div class="diet-calendar" id="liveDietCalendar">
    <?php foreach ($liveDashboardData['calendarDays'] as $dayData): ?>
    <div class="diet-day<?php echo $dayData['is_today'] ? ' active' : ''; ?>">
      <div class="day-header">
        <span class="day-name"><?php echo htmlspecialchars($dayData['day_name'], ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="day-calories"><?php echo (int)$dayData['entry_count']; ?> entries</span>
      </div>

      <div class="meals">
        <div class="meal-item<?php echo $dayData['is_today'] ? ' completed' : ''; ?>">
          <span class="meal-time">Meal Types</span>
          <span class="meal-name"><?php echo (int)$dayData['meal_count']; ?> types logged</span>
          <span class="meal-cal"><?php echo (int)$dayData['meal_pct']; ?>%</span>
        </div>

        <div class="meal-item<?php echo $dayData['is_today'] ? ' completed' : ''; ?>">
          <span class="meal-time">Entries</span>
          <span class="meal-name"><?php echo (int)$dayData['entry_count']; ?> food entries</span>
          <span class="meal-cal"><?php echo (int)$dayData['entry_pct']; ?>%</span>
        </div>

        <div class="meal-item">
          <span class="meal-time">Target</span>
          <span class="meal-name"><?php echo (int)$targetMeals; ?> meal types</span>
          <span class="meal-cal"><?php echo (int)$targetEntries; ?> entries</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="nutrition-summary">
    <div class="nutrition-card">
      <div class="nutrition-icon fats">
        <i class="fas fa-utensils"></i>
      </div>
      <div class="nutrition-info">
        <span class="nutrition-label">Meal Types</span>
        <span class="nutrition-value" id="liveMealTypesValue"><?php echo (int)$todayTotals['meal_count']; ?> / <?php echo (int)$targetMeals; ?></span>
        <div class="nutrition-bar">
          <div class="bar-fill" id="liveMealTypesBar" style="width: <?php echo $mealsPct; ?>%"></div>
        </div>
      </div>
    </div>

    <div class="nutrition-card">
      <div class="nutrition-icon fiber">
        <i class="fas fa-clipboard-list"></i>
      </div>
      <div class="nutrition-info">
        <span class="nutrition-label">Food Entries</span>
        <span class="nutrition-value" id="liveEntriesValue"><?php echo (int)$todayTotals['entry_count']; ?> / <?php echo (int)$targetEntries; ?> entries</span>
        <div class="nutrition-bar">
          <div class="bar-fill" id="liveEntriesBar" style="width: <?php echo $entriesPct; ?>%"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$exerciseList = [];

try {
  require __DIR__ . '/../Login/connection.php';
  $stmt = $pdo->query("SELECT exercise_id, name, movement_type FROM exercises ORDER BY name ASC");
  $exerciseList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $exerciseList = [];
}
?>

<!-- ── WORKOUT PLAN ── -->
<section class="workout-tracker-section">
  <div class="mt-stripe"></div>
  <div class="mt-panel">
    <h2 class="mt-title">Customize Today's Workout</h2>
    <hr class="mt-title-divider" />
    <div class="mt-input-row">
      <div class="mt-field">
        <label>Workout Type</label>
        <select id="workoutType" class="mt-select">
          <option value="All">All</option>
          <option value="Push">Push</option>
          <option value="Pull">Pull</option>
          <option value="Legs">Legs</option>
          <option value="Cardio">Cardio</option>
        </select>
      </div>

      <div class="mt-field" style="flex:1;">
        <label>Workout Name</label>
        <select id="workoutName" class="mt-select">
          <option value="">Select Workout</option>
        </select>
      </div>

      <div class="mt-field">
        <label>Sets</label>
        <input type="number" id="sets" class="mt-number" value="3" min="1" max="20">
      </div>

      <div class="mt-field">
        <label>Reps</label>
        <input type="number" id="reps" class="mt-number" value="10" min="1" max="50">
      </div>

      <div class="mt-field">
        <label>Weight</label>
        <input type="number" id="weight" class="mt-number" value="0" min="0" max="999">
      </div>

      <div class="mt-field">
        <label>Day</label>
        <select id="workoutDay" class="mt-select">
          <option>Monday</option>
          <option>Tuesday</option>
          <option>Wednesday</option>
          <option>Thursday</option>
          <option>Friday</option>
          <option>Saturday</option>
          <option>Sunday</option>
        </select>
      </div>

      <button class="mt-add-btn" type="button" onclick="addWorkout()">Add Workout</button>
    </div>

    <hr class="mt-cards-divider" />
    <div class="workout-grid" id="workoutGrid"></div>
  </div>
</section>

<style>
.plan-select {
  background: #0f0f0f;
  border: 1px solid #383838;
  color: #fff;
  border-radius: 8px;
  padding: 8px 10px;
  font-size: 0.85rem;
  outline: none;
}

.plan-select:focus {
  border-color: #ffcc00;
}

.plan-mode-btn.active {
  background: #ffcc00;
  color: #111 !important;
  border-color: #ffcc00;
}

.mt-select,
.mt-number {
  width:100%;
  background:#0f0f0f;
  color:#fff;
  border:1px solid #383838;
  border-radius:8px;
  padding:10px;
}

.mt-select:focus,
.mt-number:focus {
  border-color:#ffcc00;
  text-align:center;
}

.workout-grid {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:12px;
}

.day-card {
  background:#101010;
  border:1px solid #2b2b2b;
  border-radius:10px;
  padding:12px;
}

.day-title {
  color:#ffcc00;
  font-weight:700;
  margin-bottom:10px;
}

.workout-item {
  background:#0d0d0d;
  border:1px solid #2e2e2e;
  border-radius:8px;
  padding:8px;
  margin-bottom:8px;
  font-size:0.85rem;
}

.done-btn,
.remove-btn {
  margin-top:6px;
  border:none;
  padding:4px 8px;
  border-radius:6px;
  cursor:pointer;
  font-size:0.75rem;
}

.done-btn {
  background:#ffcc00;
}

.remove-btn {
  background:#ff4d4d;
  color:#fff;
  margin-left:5px;
}
</style>

<script>
(function () {
  const liveUrl = window.location.pathname + '?dashboard_live=1';

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderCalendar(days, targets) {
    const wrap = document.getElementById('liveDietCalendar');
    if (!wrap) return;

    wrap.innerHTML = days.map(function (day) {
      return `
        <div class="diet-day${day.is_today ? ' active' : ''}">
          <div class="day-header">
            <span class="day-name">${escapeHtml(day.day_name)}</span>
            <span class="day-calories">${day.entry_count} entries</span>
          </div>

          <div class="meals">
            <div class="meal-item${day.is_today ? ' completed' : ''}">
              <span class="meal-time">Meal Types</span>
              <span class="meal-name">${day.meal_count} types logged</span>
              <span class="meal-cal">${day.meal_pct}%</span>
            </div>

            <div class="meal-item${day.is_today ? ' completed' : ''}">
              <span class="meal-time">Entries</span>
              <span class="meal-name">${day.entry_count} food entries</span>
              <span class="meal-cal">${day.entry_pct}%</span>
            </div>

            <div class="meal-item">
              <span class="meal-time">Target</span>
              <span class="meal-name">${targets.targetMeals} meal types</span>
              <span class="meal-cal">${targets.targetEntries} entries</span>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  function renderSuggestions(items) {
    const wrap = document.getElementById('liveSuggestionCards');
    if (!wrap) return;

    wrap.innerHTML = items.map(function (text) {
      return `
        <div class="suggestion-card" data-tab="workout" style="display: flex">
          <div class="suggestion-icon workout">
            <i class="fas fa-bolt"></i>
          </div>
          <div class="suggestion-text">
            <h5>Action Item</h5>
            <p>${escapeHtml(text)}</p>
          </div>
        </div>
      `;
    }).join('');
  }

  function applyDashboardData(data) {
    renderCalendar(data.calendarDays, data.targets);
    renderSuggestions(data.smartSuggestions);

    const liveMealTypesValue = document.getElementById('liveMealTypesValue');
    const liveMealTypesBar = document.getElementById('liveMealTypesBar');
    const liveEntriesValue = document.getElementById('liveEntriesValue');
    const liveEntriesBar = document.getElementById('liveEntriesBar');
    const liveGoalProgressBar = document.getElementById('liveGoalProgressBar');
    const myPlanPrimaryGoalName = document.getElementById('myPlanPrimaryGoalName');
    const myPlanPrimaryGoalHint = document.getElementById('myPlanPrimaryGoalHint');
    const liveAttendanceBar = document.getElementById('liveAttendanceBar');
    const liveAttendanceText = document.getElementById('liveAttendanceText');
    const liveMealLoggingBar = document.getElementById('liveMealLoggingBar');
    const liveMealLoggingText = document.getElementById('liveMealLoggingText');

    if (myPlanPrimaryGoalName) {
      myPlanPrimaryGoalName.textContent = data.goal;
    }
    if (liveMealTypesValue) {
      liveMealTypesValue.textContent = data.todayTotals.meal_count + ' / ' + data.targets.targetMeals;
    }
    if (liveMealTypesBar) {
      liveMealTypesBar.style.width = data.percentages.mealsPct + '%';
    }
    if (liveEntriesValue) {
      liveEntriesValue.textContent = data.todayTotals.entry_count + ' / ' + data.targets.targetEntries + ' entries';
    }
    if (liveEntriesBar) {
      liveEntriesBar.style.width = data.percentages.entriesPct + '%';
    }
    if (liveGoalProgressBar) {
      liveGoalProgressBar.style.width = data.percentages.goalProgressPct + '%';
    }
    if (myPlanPrimaryGoalHint) {
      myPlanPrimaryGoalHint.textContent = data.weeklyWorkoutCount + '/' + data.weeklyWorkoutTarget + ' workout sessions this week (' + data.fitnessLevel + ')';
    }
    if (liveAttendanceBar) {
      liveAttendanceBar.style.width = data.percentages.attendancePct + '%';
    }
    if (liveAttendanceText) {
      liveAttendanceText.textContent = data.weeklyAttendanceCount + ' check-ins this week';
    }
    if (liveMealLoggingBar) {
      liveMealLoggingBar.style.width = data.percentages.entriesPct + '%';
    }
    if (liveMealLoggingText) {
      liveMealLoggingText.textContent = data.todayTotals.entry_count + ' / ' + data.targets.targetEntries + ' food entries today';
    }
  }

  async function refreshDashboardLive() {
    try {
      const response = await fetch(liveUrl, {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) return;
      const data = await response.json();
      applyDashboardData(data);
    } catch (error) {
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    refreshDashboardLive();
    setInterval(refreshDashboardLive, 3000);
  });

  document.addEventListener('dashboardDataUpdated', function () {
    refreshDashboardLive();
  });

  window.refreshDashboardLive = refreshDashboardLive;
})();

const exercises = <?php echo json_encode($exerciseList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const workoutType = document.getElementById("workoutType");
const workoutName = document.getElementById("workoutName");
const workoutGrid = document.getElementById("workoutGrid");
const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
let savedWorkouts = JSON.parse(localStorage.getItem("workoutLogs") || "[]");

function initDays() {
  workoutGrid.innerHTML = "";
  days.forEach(day => {
    const card = document.createElement("div");
    card.className = "day-card";
    card.id = "day-" + day;
    card.innerHTML = `<div class="day-title">${day}</div><div class="day-content"></div>`;
    workoutGrid.appendChild(card);
  });

  savedWorkouts.forEach(w => {
    const container = document.querySelector("#day-" + w.day + " .day-content");
    if (container) {
      const item = document.createElement("div");
      item.className = "workout-item";
      item.innerHTML = `
        <strong>${w.name}</strong><br>
        ${w.sets} sets × ${w.reps} reps × ${w.weight} kg<br>
        <button class="done-btn">Done</button>
        <button class="remove-btn">Remove</button>
      `;

      item.querySelector(".remove-btn").onclick = () => {
        item.remove();
        savedWorkouts = savedWorkouts.filter(s => !(s.id === w.id));
        localStorage.setItem("workoutLogs", JSON.stringify(savedWorkouts));
      };

      item.querySelector(".done-btn").onclick = () => saveWorkout(w, item);
      container.appendChild(item);
    }
  });
}

function loadExercises() {
  const type = workoutType.value;
  workoutName.innerHTML = '<option value="">Select Workout</option>';
  exercises.forEach(ex => {
    if (type === "All" || ex.movement_type === type) {
      const opt = document.createElement("option");
      opt.value = ex.exercise_id;
      opt.textContent = ex.name;
      workoutName.appendChild(opt);
    }
  });
}

workoutType.addEventListener("change", loadExercises);
window.addEventListener("load", () => { initDays(); loadExercises(); });

function addWorkout() {
  const exId = workoutName.value;
  const exName = workoutName.options[workoutName.selectedIndex].text;
  const sets = document.getElementById("sets").value;
  const reps = document.getElementById("reps").value;
  const weight = document.getElementById("weight").value;
  const day = document.getElementById("workoutDay").value;

  if (!exId) return alert("Select workout");

  const container = document.querySelector("#day-" + day + " .day-content");
  const id = Date.now();
  const item = document.createElement("div");
  item.className = "workout-item";

  item.innerHTML = `
    <strong>${exName}</strong><br>
    ${sets} sets × ${reps} reps × ${weight} kg<br>
    <button class="done-btn">Done</button>
    <button class="remove-btn">Remove</button>
  `;

  item.querySelector(".remove-btn").onclick = () => {
    item.remove();
    savedWorkouts = savedWorkouts.filter(s => s.id !== id);
    localStorage.setItem("workoutLogs", JSON.stringify(savedWorkouts));
  };

  item.querySelector(".done-btn").onclick = () => {
    saveWorkout({id, exercise_id: exId, name: exName, sets, reps, weight, day}, item);
  };

  container.appendChild(item);

  savedWorkouts.push({id, exercise_id: exId, name: exName, sets, reps, weight, day});
  localStorage.setItem("workoutLogs", JSON.stringify(savedWorkouts));
}

function saveWorkout(workout, itemEl) {
  fetch("save_workout.php", {
    method: "POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify({
      exercise_id: workout.exercise_id,
      reps: workout.reps,
      sets: workout.sets,
      weight: workout.weight
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "ok") {
      savedWorkouts = savedWorkouts.filter(s => s.id !== workout.id);
      localStorage.setItem("workoutLogs", JSON.stringify(savedWorkouts));
      if (itemEl) itemEl.remove();
      document.dispatchEvent(new CustomEvent("dashboardDataUpdated"));
    } else {
      alert("Error: " + data.error);
    }
  })
  .catch(err => alert("Error: " + err));
}
</script>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

if (!function_exists('fitstop_csrf_token')) {
    function fitstop_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

$dbPath = __DIR__ . '/../Database/DB.sqlite';

try {
    $conn = new PDO("sqlite:" . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$mt_user_id = 0;
if (isset($_SESSION['user_id'])) {
    $mt_user_id = (int) $_SESSION['user_id'];
} elseif (isset($_SESSION['id'])) {
    $mt_user_id = (int) $_SESSION['id'];
}

$mt_today = date('Y-m-d');
$mt_status_message = "Type a food name — grams auto-fill based on selected food.";
$mt_status_type = "info";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mt_action'])) {
    $posted_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals(fitstop_csrf_token(), $posted_token)) {
        $mt_status_message = "Invalid CSRF token.";
        $mt_status_type = "error";
    } elseif ($mt_user_id <= 0) {
        $mt_status_message = "User not logged in.";
        $mt_status_type = "error";
    } else {
        if ($_POST['mt_action'] === 'add_food') {
            $meal_id = isset($_POST['meal_id']) ? (int) $_POST['meal_id'] : 0;
            $meal_type = isset($_POST['meal_type']) ? strtolower(trim($_POST['meal_type'])) : '';
            $grams_consumed = isset($_POST['grams_consumed']) ? (float) $_POST['grams_consumed'] : 0;

            $allowed_types = ['breakfast', 'lunch', 'dinner', 'snack'];

            if ($meal_id <= 0 || $grams_consumed <= 0 || !in_array($meal_type, $allowed_types, true)) {
                $mt_status_message = "Please select a valid food and grams.";
                $mt_status_type = "error";
            } else {
                $meal_stmt = $conn->prepare("SELECT meal_id, food_name, serving_grams FROM meals WHERE meal_id = ? LIMIT 1");
                $meal_stmt->execute([$meal_id]);
                $meal_row = $meal_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$meal_row) {
                    $mt_status_message = "Selected food was not found.";
                    $mt_status_type = "error";
                } else {
                    $insert_stmt = $conn->prepare("
                        INSERT INTO meal_logs (user_id, meal_id, meal_type, log_date, grams_consumed)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $ok = $insert_stmt->execute([
                        $mt_user_id,
                        $meal_id,
                        $meal_type,
                        $mt_today,
                        $grams_consumed
                    ]);

                    if ($ok) {
                        $mt_status_message = "Food added to meal log.";
                        $mt_status_type = "success";
                    } else {
                        $mt_status_message = "Failed to add food.";
                        $mt_status_type = "error";
                    }
                }
            }
        }

        if ($_POST['mt_action'] === 'clear_today') {
            $clear_stmt = $conn->prepare("DELETE FROM meal_logs WHERE user_id = ? AND log_date = ?");
            $ok = $clear_stmt->execute([$mt_user_id, $mt_today]);

            if ($ok) {
                $mt_status_message = "Today's meal logs cleared.";
                $mt_status_type = "success";
            } else {
                $mt_status_message = "Failed to clear today's meal logs.";
                $mt_status_type = "error";
            }
        }
    }
}

$mt_meals = [];
$mt_meals_stmt = $conn->query("SELECT meal_id, food_name, serving_grams FROM meals ORDER BY food_name ASC");
while ($row = $mt_meals_stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['meal_id'] = (int) $row['meal_id'];
    $row['serving_grams'] = (float) $row['serving_grams'];
    $mt_meals[] = $row;
}

$mt_today_logs = [];
if ($mt_user_id > 0) {
    $logs_stmt = $conn->prepare("
        SELECT ml.log_id, ml.meal_type, ml.grams_consumed, m.food_name
        FROM meal_logs ml
        INNER JOIN meals m ON ml.meal_id = m.meal_id
        WHERE ml.user_id = ? AND ml.log_date = ?
        ORDER BY ml.log_id DESC
    ");
    $logs_stmt->execute([$mt_user_id, $mt_today]);
    $mt_today_logs = $logs_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$mt_grouped_logs = [
    'breakfast' => [],
    'lunch' => [],
    'dinner' => [],
    'snack' => []
];

foreach ($mt_today_logs as $log) {
    $type = strtolower($log['meal_type']);
    if (isset($mt_grouped_logs[$type])) {
        $mt_grouped_logs[$type][] = $log;
    }
}
?>

<section class="meal-tracker-section">
  <div class="mt-stripe"></div>

  <div class="mt-panel">
    <h2 class="mt-title">Customize Today's Meal</h2>
    <hr class="mt-title-divider" />

    <form method="POST" id="mtForm" autocomplete="off">
      <input type="hidden" name="mt_action" id="mtAction" value="add_food" />
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(fitstop_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
      <input type="hidden" name="meal_id" id="mtMealId" value="" />

      <div class="mt-input-row">
        <div class="mt-field">
          <label for="mtMealType">Meal</label>
          <select id="mtMealType" name="meal_type" class="mt-select">
            <option value="breakfast">Breakfast</option>
            <option value="lunch">Lunch</option>
            <option value="dinner">Dinner</option>
            <option value="snack">Snack</option>
          </select>
        </div>

        <div class="mt-field" style="flex: 1; min-width: 220px">
          <label for="mtFoodName">Food Name</label>
          <div class="mt-suggest-wrap" id="mtSuggestWrap">
            <input
              id="mtFoodName"
              type="text"
              placeholder="Type food (e.g. rice, egg, sinigang...)"
              autocomplete="off"
            />
            <div id="mtSuggestions"></div>
          </div>
        </div>

        <div class="mt-qty-group">
          <label
            style="
              font-family: 'Barlow Condensed', sans-serif;
              font-size: 0.7rem;
              font-weight: 700;
              letter-spacing: 2px;
              text-transform: uppercase;
            "
          >Qty / Grams</label>
          <div class="mt-qty-wrap">
            <button class="mt-qty-btn" type="button" onclick="mtChangeQty(-10)">−</button>
            <input id="mtQtyInput" class="mt-number" name="grams_consumed" type="number" value="0" min="1" step="1" />
            <button class="mt-qty-btn" type="button" onclick="mtChangeQty(10)">+</button>
          </div>
        </div>

        <button class="mt-add-btn" id="mtAddBtn" type="submit" disabled>
          Add Food
        </button>
      </div>
    </form>

    <div class="mt-nutrition-preview" id="mtNutritionPreview">
      <div class="mt-preview-top">
        <div class="mt-nutr-item">
          <span class="mt-nutr-val" id="mtPvGrams">0g</span>
          <span class="mt-nutr-lbl">Grams</span>
        </div>
      </div>
      <div class="mt-preview-serving" id="mtPvServing"></div>
      <div class="mt-preview-note">Nutritional value varies depending on ingredients used.</div>
    </div>

    <div id="mtStatus" class="mt-status <?php echo $mt_status_type === 'success' ? 'mt-status-success' : ($mt_status_type === 'error' ? 'mt-status-error' : ''); ?>">
      <?php echo htmlspecialchars($mt_status_message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
  </div>

  <div class="mt-weekly-panel">
    <div class="mt-weekly-header">
      <h2 class="mt-weekly-title">Today's Meal Schedule</h2>
      <form method="POST" id="mtClearForm" style="margin:0;">
        <input type="hidden" name="mt_action" value="clear_today" />
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(fitstop_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
        <button class="mt-clear-btn" type="submit">✕ Clear Today</button>
      </form>
    </div>

    <div id="mtWeekGrid">
      <?php foreach ($mt_grouped_logs as $meal_type => $logs) { ?>
        <div class="mt-week-card">
          <div class="mt-week-day"><?php echo htmlspecialchars(ucfirst($meal_type), ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if (!empty($logs)) { ?>
            <?php foreach ($logs as $log) { ?>
              <div class="mt-meal-info">
                <?php echo htmlspecialchars($log['food_name'], ENT_QUOTES, 'UTF-8'); ?><br />
                <?php echo (float) $log['grams_consumed']; ?>g
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="mt-meal-info">No food added.</div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </div>

  <div class="mt-log-panel">
    <h2 class="mt-log-title">Today's Meal Log</h2>
    <hr class="mt-cards-divider" />
    <div class="mt-meals-grid" id="mtTodayMeals">
      <?php if (!empty($mt_today_logs)) { ?>
        <?php foreach ($mt_today_logs as $log) { ?>
          <div class="mt-meal-card">
            <div class="mt-meal-title"><?php echo htmlspecialchars($log['food_name'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="mt-meal-info">
              Meal: <?php echo htmlspecialchars(ucfirst($log['meal_type']), ENT_QUOTES, 'UTF-8'); ?><br />
              Grams: <?php echo (float) $log['grams_consumed']; ?>g
            </div>
          </div>
        <?php } ?>
      <?php } else { ?>
        <div class="mt-meal-card">
          <div class="mt-meal-title">No meals yet</div>
          <div class="mt-meal-info">Add food to create today's meal log.</div>
        </div>
      <?php } ?>
    </div>
  </div>
</section>

<div id="mtToast">✓ Food added!</div>

<style>
.meal-tracker-section,
.mt-panel,
.mt-weekly-panel,
.mt-log-panel {
  background: #000;
  border-radius: 12px;
  padding: 12px;
}

.meal-tracker-section {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.mt-stripe {
  height: 4px;
  background: linear-gradient(90deg, #ffcc00, #000);
  margin-bottom: 0;
}

.mt-title,
.mt-weekly-title,
.mt-log-title {
  color: #fff;
  margin: 0;
}

.mt-input-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: end;
}

.mt-field {
  min-width: 160px;
}

.mt-field label,
.mt-qty-group label {
  color: #fff;
  display: block;
  margin-bottom: 6px;
}

.mt-select,
.mt-number,
#mtFoodName {
  width: 100%;
  background: #0f0f0f;
  color: #fff;
  border: 1px solid #383838;
  border-radius: 8px;
  padding: 10px;
}

.mt-select:focus,
.mt-number:focus,
#mtFoodName:focus {
  border-color: #ffcc00;
  outline: none;
}

.mt-add-btn,
.mt-clear-btn,
.mt-qty-btn {
  background: #ffcc00;
  border: none;
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
  color: #000;
}

.mt-add-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.mt-meals-grid,
#mtWeekGrid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 10px;
}

.mt-meal-card,
.mt-week-card {
  background: #101010;
  border: 1px solid #2b2b2b;
  border-radius: 10px;
  padding: 12px;
  font-size: 0.85rem;
  color: #fff;
}

.mt-meal-title,
.mt-week-day {
  color: #ffcc00;
  font-weight: 700;
  margin-bottom: 6px;
}

.mt-meal-info {
  background: #0d0d0d;
  border: 1px solid #2e2e2e;
  border-radius: 8px;
  padding: 6px;
  margin-bottom: 6px;
}

.mt-nutrition-preview {
  background: #0f0f0f;
  border: 1px solid #383838;
  border-radius: 8px;
  padding: 8px;
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
}

.mt-preview-top {
  display: flex;
  width: 100%;
  gap: 12px;
}

.mt-nutr-item {
  text-align: center;
  flex: 1;
}

.mt-nutr-val {
  font-weight: 700;
  color: #ffcc00;
  display: block;
}

.mt-nutr-lbl {
  font-size: 0.75rem;
  color: #aaa;
}

.mt-preview-serving {
  width: 100%;
  margin-top: 8px;
  color: #ccc;
  font-size: 0.82rem;
}

.mt-preview-note {
  width: 100%;
  margin-top: 8px;
  color: #9a9a9a;
  font-size: 0.76rem;
  line-height: 1.4;
  border-top: 1px solid #232323;
  padding-top: 8px;
}

.mt-qty-wrap {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 6px;
}

.mt-qty-btn {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.75rem;
}

.mt-weekly-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.mt-suggest-wrap {
  position: relative;
}

#mtSuggestions {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: #0f0f0f;
  border: 1px solid #383838;
  border-radius: 8px;
  z-index: 1000;
  max-height: 240px;
  overflow-y: auto;
  display: none;
}

.mt-suggestion-item {
  padding: 10px;
  cursor: pointer;
  color: #fff;
  border-bottom: 1px solid #1d1d1d;
}

.mt-suggestion-item:last-child {
  border-bottom: none;
}

.mt-suggestion-item:hover,
.mt-suggestion-item.active {
  background: #1b1b1b;
}

#mtStatus {
  margin-top: 10px;
  color: #d6d6d6;
}

.mt-status-success {
  color: #9bff8d !important;
}

.mt-status-error {
  color: #ff8d8d !important;
}

#mtToast {
  position: fixed;
  right: 18px;
  bottom: 18px;
  background: #ffcc00;
  color: #000;
  padding: 10px 14px;
  border-radius: 8px;
  font-weight: 700;
  box-shadow: 0 8px 20px rgba(0,0,0,0.35);
  opacity: 0;
  transform: translateY(8px);
  pointer-events: none;
  transition: 0.25s ease;
  z-index: 9999;
}

#mtToast.show {
  opacity: 1;
  transform: translateY(0);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
<script src="lightmode.js"></script>

<script>
const mtMeals = <?php echo json_encode($mt_meals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let mtSelectedMeal = null;

const mtFoodName = document.getElementById("mtFoodName");
const mtMealId = document.getElementById("mtMealId");
const mtQtyInput = document.getElementById("mtQtyInput");
const mtSuggestions = document.getElementById("mtSuggestions");
const mtAddBtn = document.getElementById("mtAddBtn");
const mtPvGrams = document.getElementById("mtPvGrams");
const mtPvServing = document.getElementById("mtPvServing");
const mtStatus = document.getElementById("mtStatus");
const mtForm = document.getElementById("mtForm");
const mtClearForm = document.getElementById("mtClearForm");
const mtToast = document.getElementById("mtToast");

function mtChangeQty(change) {
  let current = parseFloat(mtQtyInput.value) || 0;
  current += change;
  if (current < 1) current = 1;
  mtQtyInput.value = Math.round(current);
  mtUpdatePreview();
}

function mtSetSelectedMeal(meal) {
  mtSelectedMeal = meal;
  mtMealId.value = meal.meal_id;
  mtFoodName.value = meal.food_name;
  mtQtyInput.value = Math.round(parseFloat(meal.serving_grams) || 100);
  mtSuggestions.style.display = "none";
  mtUpdatePreview();
}

function mtUpdatePreview() {
  const grams = parseFloat(mtQtyInput.value) || 0;

  if (!mtSelectedMeal) {
    mtAddBtn.disabled = true;
    mtPvGrams.textContent = grams + "g";
    mtPvServing.textContent = "";
    return;
  }

  const baseGrams = parseFloat(mtSelectedMeal.serving_grams) || 1;
  mtPvGrams.textContent = grams + "g";
  mtPvServing.textContent = mtSelectedMeal.food_name + " • standard serving " + baseGrams + "g";
  mtAddBtn.disabled = false;
}

function mtRenderSuggestions(list) {
  if (!list.length) {
    mtSuggestions.innerHTML = "";
    mtSuggestions.style.display = "none";
    return;
  }

  mtSuggestions.innerHTML = list.map(item => `
    <div class="mt-suggestion-item" data-id="${item.meal_id}">
      <strong>${item.food_name}</strong><br>
      <small>${item.serving_grams}g</small>
    </div>
  `).join("");

  mtSuggestions.style.display = "block";

  document.querySelectorAll(".mt-suggestion-item").forEach((item) => {
    item.addEventListener("click", function () {
      const id = parseInt(this.getAttribute("data-id"), 10);
      const meal = mtMeals.find(m => parseInt(m.meal_id, 10) === id);
      if (meal) {
        mtSetSelectedMeal(meal);
      }
    });
  });
}

function mtShowToast(message) {
  mtToast.textContent = message;
  mtToast.classList.add("show");
  setTimeout(function () {
    mtToast.classList.remove("show");
  }, 1800);
}

function mtSetStatus(message, type) {
  mtStatus.textContent = message;
  mtStatus.classList.remove("mt-status-success");
  mtStatus.classList.remove("mt-status-error");

  if (type === "success") {
    mtStatus.classList.add("mt-status-success");
  } else if (type === "error") {
    mtStatus.classList.add("mt-status-error");
  }
}

function mtSyncTokensFromResponse(doc) {
  const newAddToken = doc.querySelector('#mtForm input[name="csrf_token"]');
  const currentAddToken = mtForm.querySelector('input[name="csrf_token"]');
  if (newAddToken && currentAddToken) {
    currentAddToken.value = newAddToken.value;
  }

  const newClearToken = doc.querySelector('#mtClearForm input[name="csrf_token"]');
  const currentClearToken = mtClearForm.querySelector('input[name="csrf_token"]');
  if (newClearToken && currentClearToken) {
    currentClearToken.value = newClearToken.value;
  }
}

function mtRefreshSectionsFromHtml(html) {
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, "text/html");

  const newWeekGrid = doc.querySelector("#mtWeekGrid");
  const currentWeekGrid = document.querySelector("#mtWeekGrid");
  if (newWeekGrid && currentWeekGrid) {
    currentWeekGrid.innerHTML = newWeekGrid.innerHTML;
  }

  const newTodayMeals = doc.querySelector("#mtTodayMeals");
  const currentTodayMeals = document.querySelector("#mtTodayMeals");
  if (newTodayMeals && currentTodayMeals) {
    currentTodayMeals.innerHTML = newTodayMeals.innerHTML;
  }

  const newStatus = doc.querySelector("#mtStatus");
  if (newStatus) {
    mtStatus.className = newStatus.className;
    mtStatus.innerHTML = newStatus.innerHTML;
  }

  mtSyncTokensFromResponse(doc);
}

async function mtPostForm(formElement) {
  const formData = new FormData(formElement);

  const response = await fetch(window.location.href, {
    method: "POST",
    body: formData,
    headers: {
      "X-Requested-With": "XMLHttpRequest"
    }
  });

  return await response.text();
}

mtFoodName.addEventListener("input", function () {
  const q = this.value.trim().toLowerCase();

  mtMealId.value = "";
  mtSelectedMeal = null;
  mtAddBtn.disabled = true;
  mtPvServing.textContent = "";

  if (!q) {
    mtSuggestions.innerHTML = "";
    mtSuggestions.style.display = "none";
    mtSetStatus("Type a food name — grams auto-fill based on selected food.", "info");
    mtUpdatePreview();
    return;
  }

  const exactMeal = mtMeals.find(item => item.food_name.toLowerCase() === q);
  if (exactMeal) {
    mtSetSelectedMeal(exactMeal);
    return;
  }

  const matches = mtMeals.filter(item =>
    item.food_name.toLowerCase().includes(q)
  ).slice(0, 10);

  mtRenderSuggestions(matches);
});

mtQtyInput.addEventListener("input", mtUpdatePreview);

document.addEventListener("click", function (e) {
  const wrap = document.getElementById("mtSuggestWrap");
  if (!wrap.contains(e.target)) {
    mtSuggestions.style.display = "none";
  }
});

mtForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  if (!mtMealId.value || !mtSelectedMeal) {
    mtSetStatus("Please select a food from the suggestions.", "error");
    return;
  }

  const previousScrollY = window.scrollY;

  try {
    const html = await mtPostForm(mtForm);
    mtRefreshSectionsFromHtml(html);

    mtFoodName.value = "";
    mtMealId.value = "";
    mtSelectedMeal = null;
    mtQtyInput.value = "100";
    mtSuggestions.innerHTML = "";
    mtSuggestions.style.display = "none";
    mtUpdatePreview();

    window.scrollTo(0, previousScrollY);
    mtShowToast("✓ Food added!");
  } catch (error) {
    mtSetStatus("Failed to add food.", "error");
    window.scrollTo(0, previousScrollY);
  }
});

mtClearForm.addEventListener("submit", async function (e) {
  e.preventDefault();

  const previousScrollY = window.scrollY;

  try {
    const html = await mtPostForm(mtClearForm);
    mtRefreshSectionsFromHtml(html);
    window.scrollTo(0, previousScrollY);
    mtShowToast("✓ Today cleared!");
  } catch (error) {
    mtSetStatus("Failed to clear today's meal logs.", "error");
    window.scrollTo(0, previousScrollY);
  }
});

mtUpdatePreview();
</script>

<script>
document.querySelectorAll(".plan-ai-chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    const prompt = chip.dataset.prompt || "";
    const queryInput = document.getElementById("planAiQuery");
    const form = document.getElementById("planAiForm");
    if (!queryInput || !form || prompt.trim() === "") {
      return;
    }
    queryInput.value = prompt;
    form.submit();
  });
});
</script>