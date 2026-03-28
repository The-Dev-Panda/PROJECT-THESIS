<?php
require_once __DIR__ . '/auth_user.php';

$displayName = 'Member';
$emailAddress = 'Not set';
$addressValue = 'Not set';
$remarksValue = 'Not set';
$memberIdDisplay = 'Not set';
$ageValue = null;
$ageDisplay = 'Not set';
$heightValue = '';
$weightValue = '';
$contactValue = '';
$genderValue = 'Not set';
$fitnessLevel = 'Not set';
$goal = 'Not set';
$eNameValue = 'Not set';
$eContactValue = 'Not set';
$attendanceStreakDays = 0;
$avatarInitials = 'M';
$selectedGender = '';
$selectedFitnessLevel = '';
$selectedGoal = '';
$qrPayload = json_encode(['member_ref' => (string)($_SESSION['id'] ?? ''), 'username' => '']);

// Initialize Monthly variables
$monthlyId = null;
$monthlyName = 'None';
$monthlyExpiry = null;
$monthlyStatus = 'None';
$daysRemaining = 0;

try {
  require __DIR__ . '/../Login/connection.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    // 1. Fetch User Data
    $userStmt = $pdo->prepare('SELECT id, username, first_name, last_name, email, address FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // 2. Fetch Profile Data (dynamic schema check)
    $profileColumns = [];
    $profileColumnStmt = $pdo->query('SHOW COLUMNS FROM member_profiles');
    if ($profileColumnStmt) {
      $profileColumnRows = $profileColumnStmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($profileColumnRows as $profileColumnRow) {
        if (isset($profileColumnRow['Field'])) {
          $profileColumns[] = (string)$profileColumnRow['Field'];
        }
      }
    }

    $profileSelectSql = 'SELECT '
      . (in_array('age', $profileColumns, true) ? 'age' : 'NULL AS age') . ', '
      . (in_array('height_cm', $profileColumns, true) ? 'height_cm' : 'NULL AS height_cm') . ', '
      . (in_array('weight_kg', $profileColumns, true) ? 'weight_kg' : 'NULL AS weight_kg') . ', '
      . (in_array('contact', $profileColumns, true) ? 'contact' : 'NULL AS contact') . ', '
      . (in_array('gender', $profileColumns, true) ? 'gender' : 'NULL AS gender') . ', '
      . (in_array('fitness_level', $profileColumns, true) ? 'fitness_level' : 'NULL AS fitness_level') . ', '
      . (in_array('goal', $profileColumns, true) ? 'goal' : 'NULL AS goal') . ', '
      . (in_array('remarks', $profileColumns, true) ? 'remarks' : 'NULL AS remarks') . ', '
      . (in_array('e_name', $profileColumns, true) ? 'e_name' : 'NULL AS e_name') . ', '
      . (in_array('e_contact', $profileColumns, true) ? 'e_contact' : 'NULL AS e_contact')
      . ' FROM member_profiles WHERE user_id = :user_id LIMIT 1';

    $profileStmt = $pdo->prepare($profileSelectSql);
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // 3. Fetch Monthly Membership Link
    $monthly = null;
    $mStmt = $pdo->prepare("
      SELECT id, name, expires_in, member
      FROM monthly
      WHERE member = :user_id
      ORDER BY date(expires_in) DESC
      LIMIT 1
    ");
    $mStmt->execute([':user_id' => $userId]);
    $monthly = $mStmt->fetch(PDO::FETCH_ASSOC);

    $monthlyId = $monthly ? $monthly['id'] : null;
    $monthlyName = $monthly ? $monthly['name'] : 'None';
    $monthlyExpiry = $monthly ? $monthly['expires_in'] : null;
    
    if ($monthlyExpiry) {
        $nowDate = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $expDate = new DateTime($monthlyExpiry);
        $daysRemaining = (int) $nowDate->diff($expDate)->format('%r%a');
        $monthlyStatus = $daysRemaining >= 0 ? "Active ($daysRemaining days)" : "Expired (" . abs($daysRemaining) . " days)";
    }

    // Assign mapped values
    $nameRaw = trim(((string)($user['first_name'] ?? '')) . ' ' . ((string)($user['last_name'] ?? '')));
    if ($nameRaw !== '') {
      $displayName = $nameRaw;
    } elseif (!empty($user['username'])) {
      $displayName = (string)$user['username'];
    }

    $firstInitial = strtoupper(substr(trim((string)($user['first_name'] ?? '')), 0, 1));
    $lastInitial = strtoupper(substr(trim((string)($user['last_name'] ?? '')), 0, 1));
    if ($firstInitial !== '' || $lastInitial !== '') {
      $avatarInitials = $firstInitial . $lastInitial;
    } elseif (!empty($user['username'])) {
      $avatarInitials = strtoupper(substr((string)$user['username'], 0, 2));
    }

    if (!empty($user['email'])) {
      $emailAddress = (string)$user['email'];
    }

    if (!empty($user['address'])) {
      $addressValue = (string)$user['address'];
    }

    $memberIdDisplay = 'FS-' . date('Y') . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);

    if (isset($profile['age']) && $profile['age'] !== null && $profile['age'] !== '') {
      $ageValue = (int)$profile['age'];
      if ($ageValue > 0) {
        $ageDisplay = $ageValue . ' Years';
      }
    }

    if (isset($profile['height_cm']) && $profile['height_cm'] !== null && $profile['height_cm'] !== '') {
      $heightValue = (string)$profile['height_cm'];
    }

    if (isset($profile['weight_kg']) && $profile['weight_kg'] !== null && $profile['weight_kg'] !== '') {
      $weightValue = (string)$profile['weight_kg'];
    }

    if (isset($profile['contact']) && $profile['contact'] !== null && $profile['contact'] !== '') {
      $contactValue = (string)$profile['contact'];
    }

    if (isset($profile['gender']) && $profile['gender'] !== null && $profile['gender'] !== '') {
      $genderValue = (string)$profile['gender'];
      $selectedGender = (string)$profile['gender'];
    }

    if (isset($profile['remarks']) && $profile['remarks'] !== null && $profile['remarks'] !== '') {
      $remarksValue = (string)$profile['remarks'];
    }

    if (!empty($profile['fitness_level'])) {
      $fitnessLevel = (string)$profile['fitness_level'];
      $selectedFitnessLevel = $fitnessLevel;
    }

    if (!empty($profile['goal'])) {
      $goal = (string)$profile['goal'];
      $selectedGoal = $goal;
    }
    
    if (!empty($profile['e_name'])) {
      $eNameValue = (string)$profile['e_name'];
    }
    
    if (!empty($profile['e_contact'])) {
      $eContactValue = (string)$profile['e_contact'];
    }

    $attendanceStmt = $pdo->prepare("SELECT DISTINCT DATE(`datetime`) AS attendance_day FROM attendance WHERE user_id = :user_id ORDER BY attendance_day DESC");
    $attendanceStmt->execute([':user_id' => $userId]);
    $attendanceDays = $attendanceStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    if (!empty($attendanceDays)) {
      $latestDay = DateTimeImmutable::createFromFormat('Y-m-d', (string)$attendanceDays[0]);
      if ($latestDay instanceof DateTimeImmutable) {
        $expectedDay = $latestDay;
        foreach ($attendanceDays as $attendanceDay) {
          $currentDay = DateTimeImmutable::createFromFormat('Y-m-d', (string)$attendanceDay);
          if (!($currentDay instanceof DateTimeImmutable)) {
            continue;
          }

          if ($currentDay->format('Y-m-d') !== $expectedDay->format('Y-m-d')) {
            break;
          }

          $attendanceStreakDays++;
          $expectedDay = $expectedDay->modify('-1 day');
        }
      }
    }

    $qrPayload = json_encode([
      'member_ref' => (string)$userId,
      'username' => (string)($user['username'] ?? '')
    ]);
  }
} catch (Throwable $e) {
  // Keep template defaults if profile loading fails.
}
?>
<?php $activePage = 'profile'; ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fit-Stop - User Profile</title>
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
    <style>
      .profile-header-content .profile-avatar {
        background: #ffcc00;
        border-radius: 50%;
      }

      .profile-avatar .avatar-initials {
        font-size: 42px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        line-height: 1;
        color: #111111;
      }

      .onboard-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
      }

      .onboard-label {
        font-size: 11px;
        letter-spacing: 0.7px;
        text-transform: uppercase;
        font-weight: 700;
        color: #a0a0a0;
      }

      .onboard-actions {
        display: flex;
        align-items: flex-end;
      }
      
      .status-badge {
        font-size: 0.8em;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 5px;
      }
      .status-badge.active { background-color: #d4edda; color: #155724; }
      .status-badge.expired { background-color: #f8d7da; color: #721c24; }
    </style>
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

      <main class="main-content">
        <header class="profile-banner">
          <div class="banner-bg"></div>
          <div class="profile-header-content">
            <div class="profile-avatar">
              <span class="avatar-initials"><?php echo htmlspecialchars($avatarInitials, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="profile-header-info">
              <h1 id="profileHeaderName"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="profile-subtitle"><?php echo htmlspecialchars($monthlyName, ENT_QUOTES, 'UTF-8'); ?> • Active Since <?php echo date('Y'); ?></p>
              <div class="profile-stats-mini">
                <div class="stat-badge">
                  <i class="fas fa-fire"></i>
                  <span><?php echo (int)$attendanceStreakDays . ' Day' . ($attendanceStreakDays === 1 ? '' : 's') . ' Streak'; ?></span>
                </div>
                <div class="stat-badge">
                  <i class="fas fa-dumbbell"></i>
                  <span><?php echo htmlspecialchars($monthlyName, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="stat-badge">
                  <i class="fas fa-check-circle"></i>
                  <span>Verified</span>
                </div>
              </div>
            </div>
          </div>
        </header>
        <section class="profile-page">
          <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
            <button class="btn-action primary" id="editProfileToggleBtn" onclick="toggleEditPanel()" style="width:auto;">
              <i class="fas fa-edit" id="editProfileIcon"></i>
              <span id="editProfileBtnText">Edit Profile</span>
            </button>
          </div>
          
          <div class="profile-card">
            <div class="card-header-hazard">
              <h3><i class="bi bi-person-badge"></i> User Information</h3>
            </div>
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Full Name</span>
                <span class="info-value" id="profileFullName"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Age</span>
                <span class="info-value" id="profileAge"><?php echo htmlspecialchars($ageDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Gender</span>
                <span class="info-value"><?php echo htmlspecialchars($genderValue, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Health Concerns</span>
                <span class="info-value"><?php echo htmlspecialchars($remarksValue !== '' ? $remarksValue : 'Not set', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item"> 
                <span class="info-label">Address</span>
                <span class="info-value"><?php echo htmlspecialchars($addressValue !== '' ? $addressValue : 'Not set', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Contact Number</span>
                <span class="info-value"><?php echo htmlspecialchars($contactValue !== '' ? $contactValue : 'Not set', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Email Address</span>
                <span class="info-value" id="profileEmail"><?php echo htmlspecialchars($emailAddress, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Member ID</span>
                <span class="info-value" id="profileMemberId"><?php echo htmlspecialchars($memberIdDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item" style="grid-column: 1 / -1;">
                <span class="info-label">Monthly Access</span>
                <span class="info-value">
                  <?php if ($monthly): ?>
                    ID <?= htmlspecialchars($monthlyId) ?>, Expires <?= htmlspecialchars(date('M j, Y', strtotime($monthlyExpiry))) ?> 
                    <span class="status-badge <?= $daysRemaining >= 0 ? 'active' : 'expired' ?>"><?= htmlspecialchars($monthlyStatus) ?></span>
                  <?php else: ?>
                    <span style="color: #a0a0a0;">No monthly link yet. <a href="#" style="color:#ffcc00; text-decoration:none;">Subscribe Now</a></span>
                  <?php endif; ?>
                </span>
              </div>
            </div>
            
            <div class="emergency-contact">
              <h4><i class="fas fa-phone-alt"></i> Emergency Contact</h4>
              <div class="info-grid-small">
                <div class="info-item">
                  <span class="info-label">Name</span>
                  <span class="info-value"><?php echo htmlspecialchars($eNameValue, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Mobile</span>
                  <span class="info-value"><?php echo htmlspecialchars($eContactValue, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
            </div>

            <div class="emergency-contact" id="editDropdownPanel" style="margin-top:18px; display:none;">
              <h4><i class="fas fa-user-edit"></i> Profile Setup</h4>
              <div class="info-grid-small" style="margin-bottom: 12px;">
                <div class="info-item">
                  <span class="info-label">Fitness Experience</span>
                  <span class="info-value" id="profileFitnessLevel"><?php echo htmlspecialchars($fitnessLevel, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="info-item">
                  <span class="info-label">Primary Goal</span>
                  <span class="info-value" id="profileGoal"><?php echo htmlspecialchars($goal, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>

              <form id="profileOnboardingForm" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px;">
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardAge">Age (years)</label>
                  <input type="number" id="onboardAge" class="form-input" placeholder="e.g. 22" value="<?php echo htmlspecialchars($ageValue === null ? '' : (string)$ageValue, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardHeight">Height (cm)</label>
                  <input type="number" id="onboardHeight" class="form-input" placeholder="e.g. 170" step="0.1" min="1" value="<?php echo htmlspecialchars($heightValue, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardWeight">Weight (kg)</label>
                  <input type="number" id="onboardWeight" class="form-input" placeholder="e.g. 65" step="0.1" min="1" value="<?php echo htmlspecialchars($weightValue, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardContact">Contact Number</label>
                  <input type="text" id="onboardContact" class="form-input" placeholder="e.g. 09xxxxxxxxx" value="<?php echo htmlspecialchars($contactValue, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardAddress">Address</label>
                  <input type="text" id="onboardAddress" class="form-input" placeholder="e.g. Cebu City, Philippines" value="<?php echo htmlspecialchars($addressValue, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardGender">Gender</label>
                  <select id="onboardGender" class="form-input">
                    <option value="">Select gender</option>
                    <option value="Male" <?php echo $selectedGender === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $selectedGender === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Prefer not to say" <?php echo $selectedGender === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                  </select>
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardFitnessLevel">Fitness Experience</label>
                  <select id="onboardFitnessLevel" class="form-input">
                    <option value="">Select experience</option>
                    <option value="Beginner" <?php echo $selectedFitnessLevel === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?php echo $selectedFitnessLevel === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo $selectedFitnessLevel === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                  </select>
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardGoal">Primary Goal</label>
                  <select id="onboardGoal" class="form-input">
                    <option value="">Select goal</option>
                    <option value="Weight Loss" <?php echo $selectedGoal === 'Weight Loss' ? 'selected' : ''; ?>>Weight Loss</option>
                    <option value="Muscle Gain" <?php echo $selectedGoal === 'Muscle Gain' ? 'selected' : ''; ?>>Muscle Gain</option>
                    <option value="Endurance" <?php echo $selectedGoal === 'Endurance' ? 'selected' : ''; ?>>Endurance</option>
                    <option value="General Fitness" <?php echo $selectedGoal === 'General Fitness' ? 'selected' : ''; ?>>General Fitness</option>
                  </select>
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardEName">Emergency Contact Name</label>
                  <input type="text" id="onboardEName" class="form-input" placeholder="e.g. Maria Clara" value="<?php echo htmlspecialchars($eNameValue !== 'Not set' ? $eNameValue : '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field">
                  <label class="onboard-label" for="onboardEContact">Emergency Contact Number</label>
                  <input type="text" id="onboardEContact" class="form-input" placeholder="e.g. 09xxxxxxxxx" value="<?php echo htmlspecialchars($eContactValue !== 'Not set' ? $eContactValue : '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="onboard-field" style="grid-column: 1 / -1;">
                  <label class="onboard-label" for="onboardRemarks">Health Concerns (for trainer / AI)</label>
                  <textarea id="onboardRemarks" class="form-input" rows="3" placeholder="e.g. asthma, high blood pressure"><?php echo htmlspecialchars($remarksValue !== 'Not set' ? $remarksValue : '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="onboard-actions" style="grid-column: 1 / -1;">
                  <button type="submit" class="btn-action primary" style="border:none; width:100%;">Save Profile</button>
                </div>
              </form>
            </div>
          </div>

      

          <div class="profile-grid-2">
            <div class="profile-card">
              <div class="card-header-hazard">
                <h3><i class="fas fa-qrcode"></i> Attendance QR Code</h3>
              </div>
              <div class="qr-section">
                <div class="qr-box">
                  <div id="qrcode"></div>
                </div>
                <p class="qr-instruction">
                  Scan this code at the entrance for real-time attendance tracking
                </p>
                <div class="qr-actions">
                  <button class="btn-qr" onclick="downloadQR()">
                    <i class="fas fa-download"></i> Download
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="profile-card terms-card">
            <div class="card-header-hazard">
              <h3><i class="fas fa-file-contract"></i> Membership Agreement</h3>
            </div>

            <div class="terms-content">
              <div class="terms-box">
                <h4>Important Notice</h4>
                <p>
                  This is an Agreement under which you agree to become a Member
                  of F-Stop Fitness Center (Fitstop). When you sign this
                  Agreement, you are entering into a legally binding contract
                  with us. This agreement sets out your rights to use the
                  Facilities and Services and the responsibilities you have as a
                  Member, including payment of Membership Fees and gym packages.
                </p>
              </div>

              <div class="terms-box">
                <h4>Your Safety</h4>
                <p>
                  You agree to give all relevant health and fitness information
                  before or during any exercise. Each time you use the
                  Facilities and Services, you must ensure you are in good
                  physical condition and know of no medical or other reason why
                  you should not exercise. If unsure, you should seek medical
                  guidance.
                </p>
              </div>

              <div class="terms-box">
                <h4>Liability Waiver</h4>
                <p>
                  I hereby hold F-Stop Fitness Center and its associates free
                  from any liabilities in the loss of personal properties and/or
                  physical injuries, accidents arising from use of the gym's
                  facilities and its equipment.
                </p>
              </div>

              <div class="terms-box">
                <h4>Action for Risky or Inappropriate Conduct</h4>
                <p>
                  If you behave in a risky or seriously inappropriate way, for
                  example, if you threaten or harass others, damage equipment,
                  distribute or use illicit substances, or train other Members
                  without authorization, appropriate action will be taken. Your
                  membership may be immediately suspended or cancelled.
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+EQG7wp9vY1Qtu2w1P7QHCMkHPlJ8" crossorigin="anonymous"></script>
    <script src="lightmode.js"></script>
    <script>
      const qrHost = document.getElementById("qrcode");
      if (qrHost) {
        new QRCode(qrHost, {
          text: <?php echo json_encode((string)$qrPayload, JSON_UNESCAPED_UNICODE); ?>,
          width: 150,
          height: 150,
        });
      }

      document.getElementById("profileOnboardingForm").addEventListener("submit", function (event) {
        event.preventDefault();

        const payload = {
          age: document.getElementById("onboardAge").value,
          height_cm: document.getElementById("onboardHeight").value,
          weight_kg: document.getElementById("onboardWeight").value,
          contact: document.getElementById("onboardContact").value,
          address: document.getElementById("onboardAddress").value,
          gender: document.getElementById("onboardGender").value,
          fitness_level: document.getElementById("onboardFitnessLevel").value,
          goal: document.getElementById("onboardGoal").value,
          e_name: document.getElementById("onboardEName").value,
          e_contact: document.getElementById("onboardEContact").value,
          remarks: document.getElementById("onboardRemarks").value
        };

        fetch("../Database/upsert_member_profile.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        })
          .then((response) => response.json())
          .then((data) => {
            if (!data.success) {
              throw new Error(data.error || "Failed to save profile");
            }
            alert("Profile saved.");
            window.location.reload();
          })
          .catch((error) => {
            console.error(error);
            alert("Unable to save profile right now.");
          });
      });
    </script>
    <script>
      function downloadQR() {
        const canvas = document.querySelector("#qrcode canvas");
        if (!canvas) {
          alert("QR code not available. Please refresh the page.");
          return;
        }
        const link = document.createElement("a");
        link.href = canvas.toDataURL("image/png");
        link.download = "membership-qr.png";
        link.click();
      }
    </script>
    <script>
      function toggleEditPanel() {
        const panel = document.getElementById('editDropdownPanel');
        const btn = document.getElementById('editProfileToggleBtn');
        const icon = document.getElementById('editProfileIcon');
        const txt = document.getElementById('editProfileBtnText');
        const isOpen = panel.style.display === 'none' || panel.style.display === '';
        panel.style.display = isOpen ? 'block' : 'none';
        if (isOpen) {
          panel.style.animation = 'editSlideDown 0.3s ease';
          txt.textContent = 'Close Editor';
          icon.className = 'fas fa-times';
          panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
          txt.textContent = 'Edit Profile';
          icon.className = 'fas fa-edit';
        }
      }
    </script>
  </body>
</html>