<?php
require_once __DIR__ . '/auth_user.php';

$firstName = 'Member';
$goal = 'Primary Goal';
$fitnessLevel = 'Not set';
$bmiValueText = '--';

try {
  require __DIR__ . '/../Login/connection.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT id, username, first_name, last_name FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $profileStmt = $pdo->prepare('SELECT height_cm, weight_kg, fitness_level, goal, bmi FROM member_profiles WHERE user_id = :user_id LIMIT 1');
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
    $bmi = isset($profile['bmi']) && $profile['bmi'] !== null ? (float)$profile['bmi'] : null;

    if (($bmi === null || $bmi <= 0) && $heightCm !== null && $heightCm > 0 && $weightKg !== null && $weightKg > 0) {
      $hM = $heightCm / 100;
      $bmi = round($weightKg / ($hM * $hM), 1);
    }

    if ($bmi !== null && $bmi > 0) {
      $bmiValueText = number_format($bmi, 1, '.', '');
    }
  }
} catch (Throwable $e) {
  // Keep template defaults if profile loading fails.
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Plan</title>
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
  </head>

  <body>
    <div class="dashboard">
      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-header">
          <img
            src="userimage/FIT-STOP LOGO.png"
            alt="Fit-Stop Logo"
            class="logo-img"
          />
          <span class="logo-text">Fit-Stop</span>
        </div>
        <ul class="menu">
          <li>
            <a href="user.php"
              ><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a
            >
          </li>
          <li>
            <a href="bmi.php"
              ><i class="bi bi-heart-pulse"></i><span>BMI Tracker</span></a
            >
          </li>
          <li class="active">
            <a href="myplan.php"
              ><i class="bi bi-clipboard-check"></i><span>My Plan</span></a
            >
          </li>
          <li>
            <a href="history.php"
              ><i class="bi bi-clock-history"></i><span>History</span></a
            >
          </li>
          <li>
            <a href="payments.php"
              ><i class="bi bi-credit-card"></i><span>Payments</span></a
            >
          </li>
          <li>
            <a href="profile.php"
              ><i class="bi bi-person"></i><span>Profile</span></a
            >
          </li>
          <li>
            <a href="settings.php"
              ><i class="bi bi-gear"></i><span>Settings</span></a
            >
          </li>
          <li>
            <a href="logout.php"
              ><i class="bi bi-box-arrow-right"></i><span>Logout</span></a
            >
          </li>
        </ul>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <header class="topbar">
          <div class="welcome">
            <h1>My Plan</h1>
            <p id="myPlanWelcome">Hi <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>! Goal: <?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?>)</p>
          </div>
        </header>

        <!-- ── AI ADVISER ── -->
        <section class="ai-adviser-section">
          <div class="section-header">
            <h3><i class="fas fa-robot"></i> AI Fitness Adviser</h3>
            <button class="btn-outline">Full Chat</button>
          </div>
          <div class="fitbot-container">
            <div class="fitbot-bubble" onclick="toggleFitbot()">
              <i class="fas fa-robot"></i>
            </div>
            <div class="fitbot-chat" id="fitbotChat">
              <div class="fitbot-header">
                <div class="fitbot-title">
                  <i class="fas fa-robot"></i><span>FitBot AI</span>
                </div>
                <button class="bmi-modal-x" onclick="toggleFitbot()">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
              <div class="fitbot-messages" id="fitbotMessages">
                <div class="bot-msg">
                  Hey Sharien 👋 Ready to crush today's workout?
                </div>
              </div>
              <div class="fitbot-input">
                <input
                  type="text"
                  id="fitbotInput"
                  placeholder="Ask FitBot..."
                />
                <button onclick="sendFitbotMessage()">
                  <i class="fas fa-paper-plane"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="ai-adviser-grid">
            <div class="ai-chat-box">
              <div class="ai-chat-header">
                <div class="ai-avatar"><i class="fas fa-robot"></i></div>
                <div class="ai-chat-header-text">
                  <h4>FitBot AI</h4>
                  <span>● Online • Personalized to your data</span>
                </div>
              </div>
              <div class="ai-messages" id="aiMessages">
                <div class="ai-msg">
                  <div class="ai-msg-bubble">
                    Hey <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>! 👋 Based on your BMI of
                    <strong><?php echo htmlspecialchars($bmiValueText, ENT_QUOTES, 'UTF-8'); ?></strong> and goal to <?php echo htmlspecialchars(strtolower($goal), ENT_QUOTES, 'UTF-8'); ?>, I recommend
                    adding 2 cardio sessions this week. You're on track!
                  </div>
                </div>
                <div class="ai-msg user-msg">
                  <div class="ai-msg-bubble">
                    What should I eat after my workout today?
                  </div>
                </div>
                <div class="ai-msg">
                  <div class="ai-msg-bubble">
                    Great question! Post-workout, aim for a 3:1 carb-to-protein
                    ratio. Try <strong>Greek yogurt + banana</strong> or a
                    <strong>protein shake with oats</strong> within 30 minutes.
                    🍌
                  </div>
                </div>
              </div>
              <div class="ai-input-row">
                <input
                  type="text"
                  class="ai-input"
                  id="aiInput"
                  placeholder="Ask about your fitness or nutrition…"
                />
                <button class="ai-send-btn" onclick="sendAIMessage()">
                  <i class="fas fa-paper-plane"></i>
                </button>
              </div>
            </div>
            <div class="ai-suggestions-box">
              <h4>Personalized Suggestions</h4>
              <div class="suggestion-tabs">
                <button
                  class="tab-btn active"
                  onclick="switchTab(this, 'workout')"
                >
                  Workout
                </button>
                <button class="tab-btn" onclick="switchTab(this, 'meal')">
                  Meal Plan
                </button>
                <button class="tab-btn" onclick="switchTab(this, 'rest')">
                  Recovery
                </button>
              </div>
              <div class="suggestion-cards" id="suggestionCards">
                <div class="suggestion-card" data-tab="workout">
                  <div class="suggestion-icon workout">
                    <i class="fas fa-dumbbell"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>HIIT Session — 30 mins</h5>
                    <p>Based on your cardio history & calorie goal</p>
                  </div>
                </div>
                <div class="suggestion-card" data-tab="workout">
                  <div class="suggestion-icon workout">
                    <i class="fas fa-running"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>5 km Morning Run</h5>
                    <p>Aligns with your running goal (42/100 days)</p>
                  </div>
                </div>
                <div class="suggestion-card" data-tab="workout">
                  <div class="suggestion-icon workout">
                    <i class="fas fa-bicycle"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Cycling — 45 mins</h5>
                    <p>Boost your cycling streak (65/100 days)</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="meal"
                  style="display: none"
                >
                  <div class="suggestion-icon meal">
                    <i class="fas fa-egg"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>High-Protein Breakfast</h5>
                    <p>3 eggs + oats to hit your 180g protein goal</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="meal"
                  style="display: none"
                >
                  <div class="suggestion-icon meal">
                    <i class="fas fa-fish"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Salmon & Quinoa Dinner</h5>
                    <p>Rich in omega-3 for muscle recovery</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="meal"
                  style="display: none"
                >
                  <div class="suggestion-icon meal">
                    <i class="fas fa-apple-alt"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Post-Workout Snack</h5>
                    <p>Banana + Greek yogurt within 30 mins</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="rest"
                  style="display: none"
                >
                  <div class="suggestion-icon rest">
                    <i class="fas fa-moon"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Sleep by 10 PM</h5>
                    <p>Maintain your 8h 54m average sleep</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="rest"
                  style="display: none"
                >
                  <div class="suggestion-icon rest">
                    <i class="fas fa-spa"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Stretching — 10 mins</h5>
                    <p>Reduces soreness after Back & Biceps day</p>
                  </div>
                </div>
                <div
                  class="suggestion-card"
                  data-tab="rest"
                  style="display: none"
                >
                  <div class="suggestion-icon rest">
                    <i class="fas fa-tint"></i>
                  </div>
                  <div class="suggestion-text">
                    <h5>Hydration Reminder</h5>
                    <p>You need 0.4 L more to hit your 2.5 L goal</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ── STATIC DIET SCHEDULE ── -->
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
                <span class="day-name">Monday</span
                ><span class="day-calories">2,100 cal</span>
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
                <span class="day-name">Today</span
                ><span class="day-calories">2,050 cal</span>
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
                <span class="day-name">Wednesday</span
                ><span class="day-calories">2,200 cal</span>
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
                <span class="nutrition-label">Protein</span
                ><span class="nutrition-value">142g / 180g</span>
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
                <span class="nutrition-label">Carbs</span
                ><span class="nutrition-value">218g / 250g</span>
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
                <span class="nutrition-label">Fats</span
                ><span class="nutrition-value">58g / 70g</span>
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
                <span class="nutrition-label">Fiber</span
                ><span class="nutrition-value">28g / 35g</span>
                <div class="nutrition-bar">
                  <div class="bar-fill" style="width: 80%"></div>
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
              <button class="btn-outline">View All</button>
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
                    <div class="progress-bar yellow" style="width: 65%"></div>
                  </div>
                  <span class="days-count" id="myPlanPrimaryGoalHint"
                    >Fitness level: <?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?></span
                  >
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
          <div class="ai-suggestions-box">
            <h4>Personalized Suggestions</h4>
            <div class="suggestion-tabs">
              <button
                class="tab-btn active"
                onclick="switchTab(this, 'workout')"
              >
                Workout
              </button>
              <button class="tab-btn" onclick="switchTab(this, 'meal')">
                Meal Plan
              </button>
              <button class="tab-btn" onclick="switchTab(this, 'rest')">
                Recovery
              </button>
            </div>
            <div class="suggestion-cards">
              <div class="suggestion-card" data-tab="workout">
                <div class="suggestion-icon workout">
                  <i class="fas fa-dumbbell"></i>
                </div>
                <div class="suggestion-text">
                  <h5>HIIT Session — 30 mins</h5>
                  <p>Based on your cardio history & calorie goal</p>
                </div>
              </div>
              <div class="suggestion-card" data-tab="workout">
                <div class="suggestion-icon workout">
                  <i class="fas fa-running"></i>
                </div>
                <div class="suggestion-text">
                  <h5>5 km Morning Run</h5>
                  <p>Aligns with your running goal (42/100 days)</p>
                </div>
              </div>
              <div class="suggestion-card" data-tab="workout">
                <div class="suggestion-icon workout">
                  <i class="fas fa-bicycle"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Cycling — 45 mins</h5>
                  <p>Boost your cycling streak (65/100 days)</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="meal"
                style="display: none"
              >
                <div class="suggestion-icon meal">
                  <i class="fas fa-egg"></i>
                </div>
                <div class="suggestion-text">
                  <h5>High-Protein Breakfast</h5>
                  <p>3 eggs + oats to hit your 180g protein goal</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="meal"
                style="display: none"
              >
                <div class="suggestion-icon meal">
                  <i class="fas fa-fish"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Salmon & Quinoa Dinner</h5>
                  <p>Rich in omega-3 for muscle recovery</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="meal"
                style="display: none"
              >
                <div class="suggestion-icon meal">
                  <i class="fas fa-apple-alt"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Post-Workout Snack</h5>
                  <p>Banana + Greek yogurt within 30 mins</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="rest"
                style="display: none"
              >
                <div class="suggestion-icon rest">
                  <i class="fas fa-moon"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Sleep by 10 PM</h5>
                  <p>Maintain your 8h 54m average sleep</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="rest"
                style="display: none"
              >
                <div class="suggestion-icon rest">
                  <i class="fas fa-spa"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Stretching — 10 mins</h5>
                  <p>Reduces soreness after Back & Biceps day</p>
                </div>
              </div>
              <div
                class="suggestion-card"
                data-tab="rest"
                style="display: none"
              >
                <div class="suggestion-icon rest">
                  <i class="fas fa-tint"></i>
                </div>
                <div class="suggestion-text">
                  <h5>Hydration Reminder</h5>
                  <p>You need 0.4 L more to hit your 2.5 L goal</p>
                </div>
              </div>
            </div>
          </div>
        </section>

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
                    color: #888;
                  "
                  >Qty / Servings</label
                >
                <div class="mt-qty-wrap">
                  <button class="mt-qty-btn" onclick="mtChangeQty(-0.5)">
                    −
                  </button>
                  <input
                    id="mtQtyInput"
                    type="number"
                    value="1"
                    min="0.5"
                    step="0.5"
                  />
                  <button class="mt-qty-btn" onclick="mtChangeQty(0.5)">
                    +
                  </button>
                </div>
              </div>

              <button
                class="mt-add-btn"
                id="mtAddBtn"
                onclick="mtAddFood()"
                disabled
              >
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
              <button class="mt-clear-btn" onclick="mtClearToday()">
                ✕ Clear Today
              </button>
            </div>
            <div id="mtWeekGrid"></div>
          </div>
        </section>
        <!-- ══════════════════════════════════════════ -->
      </main>
    </div>

    <!-- Toast -->
    <div id="mtToast">✓ Food added!</div>

    <script src="lightmode.js"></script>
    <script src="meal.js"></script>

    <script>
      function toggleFitbot() {
        const chat = document.getElementById("fitbotChat");
        chat.style.display = chat.style.display === "flex" ? "none" : "flex";
      }
      function sendFitbotMessage() {
        const input = document.getElementById("fitbotInput");
        const messages = document.getElementById("fitbotMessages");
        const text = input.value.trim();
        if (!text) return;
        messages.innerHTML += `<div class="user-msg-fitbot">${text}</div>`;
        input.value = "";
        setTimeout(() => {
          const replies = [
            "Stay consistent. Discipline beats motivation. 💪",
            "Increase protein intake slightly today.",
            "Hydrate more. You're 400ml short.",
            "Try progressive overload this week.",
          ];
          messages.innerHTML += `<div class="bot-msg">${replies[Math.floor(Math.random() * replies.length)]}</div>`;
          messages.scrollTop = messages.scrollHeight;
        }, 600);
      }
      document
        .getElementById("fitbotInput")
        .addEventListener("keydown", (e) => {
          if (e.key === "Enter") sendFitbotMessage();
        });

      function switchTab(btn, tab) {
        document
          .querySelectorAll(".tab-btn")
          .forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        document.querySelectorAll(".suggestion-card").forEach((card) => {
          card.style.display = card.dataset.tab === tab ? "flex" : "none";
        });
      }

      function sendAIMessage() {
        const input = document.getElementById("aiInput");
        const messages = document.getElementById("aiMessages");
        const text = input.value.trim();
        if (!text) return;
        messages.innerHTML += `<div class="ai-msg user-msg"><div class="ai-msg-bubble">${text}</div></div>`;
        input.value = "";
        setTimeout(() => {
          const replies = [
            "Great question! Keep pushing towards your goals. 💪",
            "Based on your data, I recommend increasing your protein intake today.",
            "Don't forget to hydrate! Aim for at least 2.5L of water.",
            "Your progress looks great! Consider adding a rest day tomorrow.",
          ];
          messages.innerHTML += `<div class="ai-msg"><div class="ai-msg-bubble">${replies[Math.floor(Math.random() * replies.length)]}</div></div>`;
          messages.scrollTop = messages.scrollHeight;
        }, 700);
      }
      document.getElementById("aiInput").addEventListener("keydown", (e) => {
        if (e.key === "Enter") sendAIMessage();
      });

    </script>
  </body>
</html>

