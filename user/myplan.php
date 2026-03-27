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

        <!-- ── NUTRITION SNAPSHOT ── -->
        <section class="diet-schedule-section">
          <div class="section-header">
            <h3>Weekly Nutrition Snapshot</h3>
            <span class="btn-outline">Live from your meal logs</span>
          </div>
          <div class="diet-calendar">
            <?php foreach ($weekDates as $dateValue): ?>
            <?php $isToday = $dateValue === $todayDate; ?>
            <?php $dayTotals = $dailyTotals[$dateValue] ?? ['calories' => 0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0, 'meal_count' => 0]; ?>
            <div class="diet-day<?php echo $isToday ? ' active' : ''; ?>">
              <div class="day-header">
                <span class="day-name"><?php echo $isToday ? 'Today' : htmlspecialchars(date('l', strtotime($dateValue)), ENT_QUOTES, 'UTF-8'); ?></span
                ><span class="day-calories"><?php echo (int)$dayTotals['calories']; ?> cal</span>
              </div>
              <div class="meals">
                <div class="meal-item<?php echo $isToday ? ' completed' : ''; ?>">
                  <span class="meal-time">Protein</span
                  ><span class="meal-name"><?php echo (int)round((float)$dayTotals['protein']); ?> g total</span
                  ><span class="meal-cal"><?php echo (int)$progressPct($dayTotals['protein'], $targetProtein); ?>%</span>
                </div>
                <div class="meal-item<?php echo $isToday ? ' completed' : ''; ?>">
                  <span class="meal-time">Carbs</span
                  ><span class="meal-name"><?php echo (int)round((float)$dayTotals['carbs']); ?> g total</span
                  ><span class="meal-cal"><?php echo (int)$progressPct($dayTotals['carbs'], $targetCarbs); ?>%</span>
                </div>
                <div class="meal-item<?php echo $isToday ? ' completed' : ''; ?>">
                  <span class="meal-time">Fat</span
                  ><span class="meal-name"><?php echo (int)round((float)$dayTotals['fat']); ?> g total</span
                  ><span class="meal-cal"><?php echo (int)$progressPct($dayTotals['fat'], $targetFat); ?>%</span>
                </div>
                <div class="meal-item">
                  <span class="meal-time">Meals</span
                  ><span class="meal-name"><?php echo (int)$dayTotals['meal_count']; ?> logged entries</span
                  ><span class="meal-cal"><?php echo (int)$targetCalories; ?> cal target</span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="nutrition-summary">
            <div class="nutrition-card">
              <div class="nutrition-icon protein">
                <i class="fas fa-drumstick-bite"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Protein</span
                ><span class="nutrition-value"><?php echo (int)round((float)$todayTotals['protein']); ?>g / <?php echo (int)$targetProtein; ?>g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: <?php echo $proteinPct; ?>%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon carbs">
                <i class="fas fa-bread-slice"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Carbs</span
                ><span class="nutrition-value"><?php echo (int)round((float)$todayTotals['carbs']); ?>g / <?php echo (int)$targetCarbs; ?>g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: <?php echo $carbsPct; ?>%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon fats">
                <i class="fas fa-cheese"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Fats</span
                ><span class="nutrition-value"><?php echo (int)round((float)$todayTotals['fat']); ?>g / <?php echo (int)$targetFat; ?>g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: <?php echo $fatPct; ?>%"></div>
                </div>
              </div>
            </div>
            <div class="nutrition-card">
              <div class="nutrition-icon fiber">
                <i class="fas fa-seedling"></i>
              </div>
              <div class="nutrition-info">
                <span class="nutrition-label">Calories</span
                ><span class="nutrition-value"><?php echo (int)$todayTotals['calories']; ?> / <?php echo (int)$targetCalories; ?> cal</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: <?php echo $caloriesPct; ?>%"></div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ── GOALS & SUGGESTIONS ── -->
        <section class="bottom-grid">
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
                  <span class="goal-name" id="myPlanPrimaryGoalName"
                    ><?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></span
                  >
                  <div class="progress-container">
                    <div class="progress-bar yellow" style="width: <?php echo $goalProgressPct; ?>%"></div>
                  </div>
                  <span class="days-count" id="myPlanPrimaryGoalHint"
                    ><?php echo $weeklyWorkoutCount; ?>/<?php echo $weeklyWorkoutTarget; ?> workout sessions this week (<?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?>)</span
                  >
                </div>
              </li>
              <li>
                <div class="icon-circle running">
                  <i class="fas fa-running"></i>
                </div>
                <div class="goal-info">
                  <span class="goal-name">Gym Attendance</span>
                  <div class="progress-container">
                    <div class="progress-bar orange" style="width: <?php echo $progressPct($weeklyAttendanceCount, 4); ?>%"></div>
                  </div>
                  <span class="days-count"><?php echo $weeklyAttendanceCount; ?> check-ins this week</span>
                </div>
              </li>
              <li>
                <div class="icon-circle water"><i class="fas fa-tint"></i></div>
                <div class="goal-info">
                  <span class="goal-name">Calorie Target</span>
                  <div class="progress-container">
                    <div class="progress-bar blue" style="width: <?php echo $caloriesPct; ?>%"></div>
                  </div>
                  <span class="days-count"><?php echo (int)$todayTotals['calories']; ?> / <?php echo (int)$targetCalories; ?> calories today</span>
                </div>
              </li>
              <li>
                <div class="icon-circle workout">
                  <i class="fas fa-dumbbell"></i>
                </div>
                <div class="goal-info">
                  <span class="goal-name">Protein Target</span>
                  <div class="progress-container">
                    <div class="progress-bar purple" style="width: <?php echo $proteinPct; ?>%"></div>
                  </div>
                  <span class="days-count"><?php echo (int)round((float)$todayTotals['protein']); ?>g / <?php echo (int)$targetProtein; ?>g protein today</span>
                </div>
              </li>
            </ul>
          </div>
          <div class="ai-suggestions-box">
            <h4>Personalized Suggestions</h4>
            <div class="suggestion-cards">
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
<?php
$workoutMode = $_GET['mode'] ?? 'moderate';
$selectedLevel = $_GET['level'] ?? $fitnessLower;

$sets = 3;
$multiplier = 1;

if ($workoutMode === 'light') {
  $sets = 2;
  $multiplier = 0.8;
} elseif ($workoutMode === 'heavy') {
  $sets = 5;
  $multiplier = 1.3;
}

if ($selectedLevel === 'advanced') {
  $sets += 1;
} elseif ($selectedLevel === 'beginner') {
  $sets = max(2, $sets - 1);
}

$exercisePlan = [
  ['name' => 'Chest Press', 'machine' => 'SeatedChestPress.php', 'cal' => 8],
  ['name' => 'Lat Pulldown', 'machine' => 'LatPulldownSeatedCableRow.php', 'cal' => 8],
  ['name' => 'Leg Press', 'machine' => 'LegPressHackSquat.php', 'cal' => 10],
  ['name' => 'Shoulder Press', 'machine' => 'ShoulderPress.php', 'cal' => 7],
  ['name' => 'Preacher Curl', 'machine' => 'PreacherCurl.php', 'cal' => 6],
  ['name' => 'Treadmill', 'machine' => 'Treadmill.php', 'cal' => 12],
];

$totalBurn = 0;
foreach ($exercisePlan as &$ex) {
  $ex['burn'] = round($ex['cal'] * $sets * $multiplier);
  $totalBurn += $ex['burn'];
}
unset($ex);

$targetBurn = 300;
if ($workoutMode === 'light') $targetBurn = 200;
if ($workoutMode === 'heavy') $targetBurn = 600;
?>

<style>
.plan-select {
  background:#0f0f0f;
  border:1px solid #383838;
  color:#fff;
  border-radius:8px;
  padding:8px 10px;
  font-size:0.85rem;
  outline:none;
}
.plan-select:focus {
  border-color:#ffcc00;
}

.plan-mode-btn.active {
  background:#ffcc00;
  color:#111 !important;
  border-color:#ffcc00;
}
</style>

<!-- ── WORKOUT PLAN ── -->
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

      <button class="mt-add-btn" onclick="addWorkout()">Add Workout</button>
    </div>

    <hr class="mt-cards-divider" />

    <div class="workout-grid" id="workoutGrid"></div>
  </div>
</section>

<style>
.mt-select {
  width: 100%;
  background: #0f0f0f;
  color: #fff;
  border: 1px solid #383838;
  border-radius: 8px;
  padding: 10px;
}
.mt-select:focus { border-color:#ffcc00; }

.mt-number {
  width: 100%;
  background: #0f0f0f;
  color: #fff;
  border: 1px solid #383838;
  border-radius: 8px;
  padding: 10px;
  text-align:center;
}
.mt-number:focus { border-color:#ffcc00; }

.workout-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
}

.day-card {
  background: #101010;
  border: 1px solid #2b2b2b;
  border-radius: 10px;
  padding: 12px;
}

.day-title {
  color: #ffcc00;
  font-weight: 700;
  margin-bottom: 10px;
}

.workout-item {
  background: #0d0d0d;
  border: 1px solid #2e2e2e;
  border-radius: 8px;
  padding: 8px;
  margin-bottom: 8px;
  font-size: 0.85rem;
}

.done-btn, .remove-btn {
  margin-top: 6px;
  border: none;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
}

.done-btn { background:#ffcc00; }
.remove-btn { background:#ff4d4d; color:#fff; margin-left:5px; }
</style>

<script>
const exercises = <?php echo json_encode($exerciseList); ?>;

const workoutType = document.getElementById("workoutType");
const workoutName = document.getElementById("workoutName");
const workoutGrid = document.getElementById("workoutGrid");

const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];

function initDays() {
  workoutGrid.innerHTML = "";
  days.forEach(day => {
    const card = document.createElement("div");
    card.className = "day-card";
    card.id = "day-" + day;

    card.innerHTML = `
      <div class="day-title">${day}</div>
      <div class="day-content"></div>
    `;

    workoutGrid.appendChild(card);
  });
}

function loadExercises() {
  const type = workoutType.value;
  workoutName.innerHTML = '<option value="">Select Workout</option>';

  exercises.forEach(ex => {
    if (type === "All" || ex.movement_type === type) {
      const opt = document.createElement("option");
      opt.value = ex.name;
      opt.textContent = ex.name;
      workoutName.appendChild(opt);
    }
  });
}

workoutType.addEventListener("change", loadExercises);

window.onload = () => {
  initDays();
  loadExercises();
};

// HARD LIMIT ENFORCER (2 DIGITS + REALISTIC LIMIT)
function sanitizeInput(input, maxVal) {
  input.addEventListener("input", () => {
    let val = input.value.replace(/\D/g, ""); // numbers only

    if (val.length > 2) val = val.slice(0,2); // max 2 digits

    if (parseInt(val) > maxVal) val = maxVal;

    if (val === "" || val === "0") val = 1;

    input.value = val;
  });
}

sanitizeInput(document.getElementById("sets"), 20);
sanitizeInput(document.getElementById("reps"), 50);

function addWorkout() {
  const name = workoutName.value;
  const sets = document.getElementById("sets").value;
  const reps = document.getElementById("reps").value;
  const day = document.getElementById("workoutDay").value;

  if (!name) {
    alert("Select workout first");
    return;
  }

  const dayContainer = document.querySelector("#day-" + day + " .day-content");

  const item = document.createElement("div");
  item.className = "workout-item";

  item.innerHTML = `
    <strong>${name}</strong><br>
    ${sets} sets × ${reps} reps
    <br>
    <button class="done-btn" onclick="this.parentElement.style.opacity='0.5'">Done</button>
    <button class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
  `;

  dayContainer.appendChild(item);
  item.scrollIntoView({ behavior: "smooth", block: "nearest" });
}
</script>
<!-- ════════════════════════════════════════════
           MEAL TRACKER — NEW UI (dark/yellow)
           ════════════════════════════════════════════ -->
<section class="meal-tracker-section">
  <!-- Hazard stripe -->
  <div class="mt-stripe"></div>

  <!-- Customize panel -->
  <div class="mt-panel">
    <h2 class="mt-title">Customize Today's Meal</h2>
    <hr class="mt-title-divider" />

    <!-- Input row -->
    <div class="mt-input-row">
      <!-- Meal type -->
      <div class="mt-field">
        <label for="mtMealType">Meal</label>
        <select id="mtMealType">
          <option>Breakfast</option>
          <option>Lunch</option>
          <option>Dinner</option>
          <option>Snack</option>
        </select>
      </div>

      <!-- Food search -->
      <div class="mt-field" style="flex: 1; min-width: 220px">
        <label for="mtFoodName">Food Name</label>
        <div class="mt-suggest-wrap" id="mtSuggestWrap">
          <input
            id="mtFoodName"
            type="text"
            placeholder="Type food (e.g. rice, egg, sinigang…)"
            autocomplete="off"
          />
          <div id="mtSuggestions"></div>
        </div>
      </div>

      <!-- Qty stepper -->
      <div class="mt-qty-group">
        <label
          style="
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
          "
          >Qty / Servings</label
        >
        <div class="mt-qty-wrap">
          <button class="mt-qty-btn" onclick="mtChangeQty(-0.5)">−</button>
          <input id="mtQtyInput" type="number" value="1" min="0.5" step="0.5" />
          <button class="mt-qty-btn" onclick="mtChangeQty(0.5)">+</button>
        </div>
      </div>

      <button class="mt-add-btn" id="mtAddBtn" onclick="mtAddFood()" disabled>
        Add Food
      </button>
    </div>

    <!-- Nutrition preview (auto-filled) -->
    <div class="mt-nutrition-preview" id="mtNutritionPreview">
      <div class="mt-preview-top">
        <div class="mt-nutr-item">
          <span class="mt-nutr-val" id="mtPvCal">0</span
          ><span class="mt-nutr-lbl">Calories</span>
        </div>
        <div class="mt-nutr-item">
          <span class="mt-nutr-val" id="mtPvP">0g</span
          ><span class="mt-nutr-lbl">Protein</span>
        </div>
        <div class="mt-nutr-item">
          <span class="mt-nutr-val" id="mtPvC">0g</span
          ><span class="mt-nutr-lbl">Carbs</span>
        </div>
        <div class="mt-nutr-item">
          <span class="mt-nutr-val" id="mtPvF">0g</span
          ><span class="mt-nutr-lbl">Fat</span>
        </div>
      </div>
      <div class="mt-preview-serving" id="mtPvServing"></div>
    </div>

    <div id="mtStatus">
      Type a food name — nutrition auto-fills based on quantity.
    </div>

    <!-- Today's meal cards -->
    <hr class="mt-cards-divider" />
    <div class="mt-meals-grid" id="mtTodayMeals"></div>
  </div>

  <!-- Weekly panel -->
  <div class="mt-weekly-panel">
    <div class="mt-weekly-header">
      <h2 class="mt-weekly-title">Weekly Diet Schedule</h2>
      <button class="mt-clear-btn" onclick="mtClearToday()">✕ Clear Today</button>
    </div>
    <div id="mtWeekGrid"></div>
  </div>
</section>

<!-- Toast -->
<div id="mtToast">✓ Food added!</div>

<style>
/* ─── MEAL TRACKER DESIGN ─── */
.meal-tracker-section, .mt-panel, .mt-weekly-panel {
  background: #000; /* black background */
  border-radius: 12px;
  padding: 12px;
}

/* Hazard stripe now yellow */
.mt-stripe {
  height: 4px;
  background: linear-gradient(90deg, #ffcc00, #000);
  margin-bottom: 8px;
}

/* Inputs, selects, numbers */
.mt-select, .mt-number, #mtFoodName {
  width: 100%;
  background: #0f0f0f;
  color: #fff;
  border: 1px solid #383838;
  border-radius: 8px;
  padding: 10px;
}
.mt-select:focus, .mt-number:focus, #mtFoodName:focus { border-color:#ffcc00; }

/* Buttons */
.mt-add-btn, .mt-clear-btn, .mt-qty-btn {
  background: #ffcc00;
  border: none;
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
}
.mt-add-btn:disabled { opacity:0.5; cursor:not-allowed; }

/* Grids */
.mt-meals-grid, #mtWeekGrid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-top:10px; }

/* Meal/Week Cards */
.mt-meal-card, .mt-week-card {
  background: #101010;
  border: 1px solid #2b2b2b;
  border-radius: 10px;
  padding: 12px;
  font-size: 0.85rem;
}
.mt-meal-title, .mt-week-day { color:#ffcc00; font-weight:700; margin-bottom:6px; }
.mt-meal-info { background:#0d0d0d; border:1px solid #2e2e2e; border-radius:8px; padding:6px; margin-bottom:6px; }

/* Nutrition Preview */
.mt-nutrition-preview { background:#0f0f0f; border:1px solid #383838; border-radius:8px; padding:8px; margin-top:10px; display:flex; justify-content:space-between; flex-wrap:wrap; }
.mt-nutr-item { text-align:center; flex:1; }
.mt-nutr-val { font-weight:700; color:#ffcc00; display:block; }
.mt-nutr-lbl { font-size:0.75rem; color:#aaa; }

/* Qty stepper */
.mt-qty-wrap { display:flex; justify-content:center; align-items:center; gap:6px; }
.mt-qty-btn { background:#ffcc00; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-size:0.75rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
<script src="lightmode.js"></script>
<script>
  window.MT_CONFIG = {
    apiBase: "../Database",
    csrfToken: <?php echo json_encode(fitstop_csrf_token()); ?>,
  };
</script>
<script src="meal.js"></script>

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

