<?php
require_once __DIR__ . '/auth_user.php';
require __DIR__ . '/../Login/connection.php';
$activePage = 'bmi';
$firstName = 'Member';
$goal = 'Primary Goal';

$userId = (int)($_SESSION['id'] ?? 0);
if ($userId > 0) {
  $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user) {
    if (!empty($user['first_name'])) {
      $firstName = trim((string)$user['first_name']);
    } elseif (!empty($user['last_name'])) {
      $firstName = trim((string)$user['last_name']);
    }
  }

  $profileStmt = $pdo->prepare('SELECT goal FROM member_profiles WHERE user_id = :user_id LIMIT 1');
  $profileStmt->execute([':user_id' => $userId]);
  $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
  if ($profile && !empty($profile['goal'])) {
    $goal = trim((string)$profile['goal']);
  }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BMI Tracker – Fit-Stop</title>
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
          
          </div>
        
        </header>
  
      <!-- BMI Section -->
        <section class="bmi-section">
 
    <!-- Main BMI Card — matches screenshot -->
    <div class="bmi-card main-bmi">
      <div class="bmi-header">
        <h3>Your BMI Analysis</h3>
        <span class="bmi-badge healthy" id="dashBadge">Healthy</span>
      </div>
      <div class="bmi-display">
        <div class="bmi-circle">
          <div class="bmi-value" id="dashBmiValue">23.87</div>
          <span class="bmi-unit">KG/M²</span>
        </div>
        <div class="bmi-info">
          <div class="info-row">
            <span class="label">Height:</span>
            <span class="value" id="dashHeight">165 cm</span>
          </div>
          <div class="info-row">
            <span class="label">Weight:</span>
            <span class="value" id="dashWeight">65 kg</span>
          </div>
          <div class="info-row">
            <span class="label">Target:</span>
            <span class="value">60 kg</span>
          </div>
          <div class="info-row">
            <span class="label">To Goal:</span>
            <span class="value" id="dashToGoal">-5 kg</span>
          </div>
        </div>
      </div>
      <div class="bmi-scale">
        <div class="scale-bar">
          <div class="scale-marker" id="dashMarker" style="left:55%"></div>
        </div>
        <div class="scale-labels">
          <span>Underweight</span><span>Normal</span><span>Overweight</span><span>Obese</span>
        </div>
      </div>
      <!-- Full-width Calculate BMI button -->
      <button class="calc-bmi-btn" onclick="openBMIModal()">
        <i class="fas fa-calculator"></i> Calculate BMI
      </button>
    </div>
 
    <!-- Weight Progress -->
    <div class="bmi-card progress-chart">
      <h3>Weight Progress</h3>
      <div class="chart-area"><div class="chart-line"></div></div>
      <div class="progress-stats">
        <div class="stat-item"><span class="stat-label">This Month</span><span class="stat-value">-2.3 kg</span></div>
        <div class="stat-item"><span class="stat-label">Total Lost</span><span class="stat-value">-8.7 kg</span></div>
      </div>
    </div>
 
    <!-- Today's Stats -->
    <div class="bmi-card quick-stats">
      <h3>Today's Stats</h3>
      <div class="quick-stat-grid">
        <div class="quick-stat"><i class="fas fa-fire"></i><div><span class="stat-number">1,458</span><span class="stat-text">Calories Burned</span></div></div>
        <div class="quick-stat"><i class="fas fa-shoe-prints"></i><div><span class="stat-number">13,946</span><span class="stat-text">Steps</span></div></div>
        <div class="quick-stat"><i class="fas fa-moon"></i><div><span class="stat-number">8h 54m</span><span class="stat-text">Sleep</span></div></div>
        <div class="quick-stat"><i class="fas fa-tint"></i><div><span class="stat-number">2.1 L</span><span class="stat-text">Water Intake</span></div></div>
      </div>
    </div>
 
  </section>
 

 
</main>
 
<!-- ═══════════ BMI MODAL ═══════════ -->
<div class="bmi-modal-overlay" id="bmiModalOverlay" onclick="handleOverlayClick(event)">
  <div class="bmi-modal">
    <div class="modal-header">
      <div class="modal-header-left">
        <span class="modal-logo">Fit-Stop</span>
        <div>
          <div class="modal-title">BMI Calculator</div>
          <div class="modal-sub">Body Mass Index Tracker</div>
        </div>
      </div>
      <button class="modal-close" onclick="closeBMIModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
 
      <div class="m-card">
        <span class="m-card-label">Your Measurements</span>
        <div class="m-slider-group">     
          <div>
            <div class="m-slider-top">
              <span class="m-slider-name">Height</span>
              <span class="m-slider-val" id="mHeightVal">165 <span class="u">cm</span></span>
            </div>
            <input type="range" id="mHeightSlider" min="100" max="220" value="165" oninput="mSync(this,'mHeightVal','cm')"/>
          </div>
          <div>
            <div class="m-slider-top">
              <span class="m-slider-name">Weight</span>
              <span class="m-slider-val" id="mWeightVal">65 <span class="u">kg</span></span>
            </div>
            <input type="range" id="mWeightSlider" min="30" max="200" value="65" oninput="mSync(this,'mWeightVal','kg')"/>
          </div>
        </div>
      </div>
 
      <button class="m-calc-btn" onclick="mCalculate()">
        <i class="fas fa-bolt"></i> &nbsp;Calculate BMI
      </button>
 
      <!-- Results (hidden until calculated) -->
      <div class="m-result" id="mResult">
        <div class="m-bmi-card">
          <div class="m-bmi-top">
            <h4>Your BMI Analysis</h4>
            <span class="m-status-badge" id="mBadge">--</span>
          </div>
          <div class="m-bmi-display">
            <div class="m-bmi-box">
              <span class="m-bmi-num" id="mBmiNum">--</span>
              <span class="m-bmi-unit">kg/m²</span>
            </div>
            <div class="m-info-rows">
              <div class="m-info-row"><span class="lbl">Height</span><span class="val" id="mRH">--</span></div>
              <div class="m-info-row"><span class="lbl">Weight</span><span class="val" id="mRW">--</span></div>
              <div class="m-info-row"><span class="lbl">Healthy Range</span><span class="val" id="mRRange">--</span></div>
              <div class="m-info-row"><span class="lbl">To Ideal</span><span class="val" id="mRDiff">--</span></div>
            </div>
          </div>
          <div class="m-scale-bar"><div class="m-needle" id="mNeedle" style="left:0%"></div></div>
          <div class="m-scale-labels"><span>Underweight</span><span>Normal</span><span>Overweight</span><span>Obese</span></div>
        </div>
 
        <div class="m-tip"><i class="bi bi-lightbulb-fill"></i><span id="mTip">--</span></div>
 
 
        <p class="m-section-label">BMI Reference Chart</p>
        <div class="m-ref-grid">
          <div class="m-ref-item"><div class="m-ref-dot" style="background:#4da6ff"></div><div class="m-ref-text"><strong>Below 18.5</strong>Underweight</div></div>
          <div class="m-ref-item"><div class="m-ref-dot" style="background:#00c875"></div><div class="m-ref-text"><strong>18.5 – 24.9</strong>Normal Weight</div></div>
          <div class="m-ref-item"><div class="m-ref-dot" style="background:#f5c518"></div><div class="m-ref-text"><strong>25.0 – 29.9</strong>Overweight</div></div>
          <div class="m-ref-item"><div class="m-ref-dot" style="background:#ff4d4d"></div><div class="m-ref-text"><strong>30.0+</strong>Obese</div></div>
        </div>
      </div>
 
    </div>
  </div>
</div>
 
<!-- Toast -->
<div class="bmi-toast" id="bmiToast">
  <i class="bi bi-check-circle-fill"></i>
  <span>BMI updated on your dashboard!</span>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
    <script>window.FITSTOP_CSRF_TOKEN = <?php echo json_encode(fitstop_csrf_token()); ?>;</script>
    <script src="bmi.js"></script>
  </body>
</html>

