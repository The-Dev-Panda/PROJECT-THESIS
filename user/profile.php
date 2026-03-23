<?php
require_once __DIR__ . '/auth_user.php';

$displayName = 'Member';
$emailAddress = 'Not set';
$memberIdDisplay = 'Not set';
$ageValue = null;
$ageDisplay = 'Not set';
$heightValue = '';
$weightValue = '';
$contactValue = '';
$genderValue = 'Not set';
$fitnessLevel = 'Not set';
$goal = 'Not set';
$attendanceStreakDays = 0;
$avatarInitials = 'M';
$selectedGender = '';
$selectedFitnessLevel = '';
$selectedGoal = '';
$qrPayload = json_encode(['member_ref' => (string)($_SESSION['id'] ?? ''), 'username' => '']);

try {
  require __DIR__ . '/../Login/connection.php';

  $userId = (int)($_SESSION['id'] ?? 0);
  if ($userId > 0) {
    $userStmt = $pdo->prepare('SELECT id, username, first_name, last_name, email FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $profileColumns = [];
    $profileColumnStmt = $pdo->query('PRAGMA table_info(member_profiles)');
    if ($profileColumnStmt) {
      $profileColumnRows = $profileColumnStmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($profileColumnRows as $profileColumnRow) {
        if (isset($profileColumnRow['name'])) {
          $profileColumns[] = (string)$profileColumnRow['name'];
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
      . (in_array('goal', $profileColumns, true) ? 'goal' : 'NULL AS goal')
      . ' FROM member_profiles WHERE user_id = :user_id LIMIT 1';

    $profileStmt = $pdo->prepare($profileSelectSql);
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

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

    if (!empty($profile['fitness_level'])) {
      $fitnessLevel = (string)$profile['fitness_level'];
      $selectedFitnessLevel = $fitnessLevel;
    }

    if (!empty($profile['goal'])) {
      $goal = (string)$profile['goal'];
      $selectedGoal = $goal;
    }

    $attendanceStmt = $pdo->prepare("SELECT DISTINCT date(datetime, 'localtime') AS attendance_day FROM attendance WHERE user_id = :user_id ORDER BY attendance_day DESC");
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
    </style>
  </head>
  <body>
    <div class="dashboard">
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- TOP PROFILE BANNER -->
        <header class="profile-banner">
          <div class="banner-bg"></div>
          <div class="profile-header-content">
            <div class="profile-avatar">
              <span class="avatar-initials"><?php echo htmlspecialchars($avatarInitials, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="profile-header-info">
              <h1 id="profileHeaderName"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="profile-subtitle">New Member • Active Since 2026</p>
              <div class="profile-stats-mini">
                <div class="stat-badge">
                  <i class="fas fa-fire"></i>
                  <span><?php echo (int)$attendanceStreakDays . ' Day' . ($attendanceStreakDays === 1 ? '' : 's') . ' Streak'; ?></span>
                </div>
                <div class="stat-badge">
                  <i class="fas fa-dumbbell"></i>
                  <span>New Member Plan</span>
                </div>
                <div class="stat-badge">
                  <i class="fas fa-check-circle"></i>
                  <span>Verified</span>
                </div>
              </div>
            </div>
          </div>
        </header>

        <!-- PROFILE SECTIONS -->
        <section class="profile-page">
          <!-- USER INFORMATION -->
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
                <span class="info-label">Birthdate</span>
                <span class="info-value">Jan 15, 2002</span>
              </div>
              <div class="info-item">
                <span class="info-label">Gender</span>
                <span class="info-value"><?php echo htmlspecialchars($genderValue, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="info-item">
                <span class="info-label">Address</span>
                <span class="info-value">Cebu City, Philippines</span>
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
            </div>

            <div class="emergency-contact" style="margin-top: 18px;">
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
                <div class="onboard-actions">
                  <button type="submit" class="btn-action primary" style="border:none; width:100%;">Save Profile</button>
                </div>
              </form>
            </div>

            <div class="emergency-contact">
              <h4><i class="fas fa-phone-alt"></i> Emergency Contact</h4>
              <div class="info-grid-small">
                <div class="info-item">
                  <span class="info-label">Name</span>
                  <span class="info-value">Maria Walker</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Relationship</span>
                  <span class="info-value">Mother</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Mobile</span>
                  <span class="info-value">+63 921 123 4567</span>
                </div>
              </div>
            </div>
          </div>

          <!-- MEMBERSHIP DETAILS -->
           <section class="profile-page">
          <!-- MEMBERSHIP DETAILS -->
          <div class="profile-card membership-card">
            <div class="card-header-hazard">
              <h3><i class="fas fa-id-card"></i> Gym Annual Membership</h3>
              <span class="membership-status active">ACTIVE</span>
            </div>
            <div class="membership-details-grid">
              <div class="membership-info-section">
                <h4>Package Details</h4>
                <div class="info-grid-small">
                  <div class="info-item">
                    <span class="info-label">Membership Type</span>
                    <span class="info-value highlight">New Member</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Duration</span>
                    <span class="info-value">1 Month</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Start Date</span>
                    <span class="info-value">Feb 10, 2026</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Expiry Date</span>
                    <span class="info-value">March 10, 2026</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Add-ons</span>
                    <span class="info-value">Personal Trainer</span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Monthly Rate</span>
                    <span class="info-value">₱1,050</span>
                  </div>
                </div>
              </div>
              <div class="membership-total">
                <div class="total-amount">
                  <span class="total-label">Total</span>
                  <span class="total-value">₱1,700</span>
                </div>
                <div class="payment-status paid">
                  <i class="fas fa-check-circle"></i>
                  Fully Paid
                </div>
              </div>
            </div>
          </div>

          <!-- ATTENDANCE QR CODE -->
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
                  Scan this code at the entrance for real-time attendance
                  tracking
                </p>
                <div class="qr-actions">
                  <button class="btn-qr" onclick="downloadQR()">
                    <i class="fas fa-download"></i> Download
                  </button>
                </div>
              </div>
            </div>
      
            <div class="profile-card">
              <div class="card-header-hazard">
                <h3><i class="fas fa-file-signature"></i> E-Signature</h3>
              </div>
              <div class="signature-section">
                <div class="signature-box">
                  <span class="signature-text">Sharien Salarda</span>
                </div>
                <p class="signature-note">
                  Digital signature authenticated on May 10, 2024
                </p>
                <button class="btn-signature">
                  <i class="fas fa-pen"></i> Update Signature
                </button>
              </div>
            </div>
          </div>

          <!-- TERMS & POLICY -->
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

              <div class="agreement-checkbox">
                <i class="fas fa-check-square"></i>
                <span
                  >I have read and agree to the Terms & Conditions and Privacy
                  Policy</span
                >
              </div>
            </div>
          </div>

          <!-- ACTION BUTTONS -->
          <div class="profile-actions">
            <button class="btn-action primary">
              <i class="fas fa-edit"></i>
              Edit Profile
            </button>
            <button class="btn-action secondary">
              <i class="fas fa-download"></i>
              Download All Documents
            </button>
            <button class="btn-action secondary">
              <i class="bi bi-arrow-repeat"></i>
              Renewal Membership
            </button>
            <button class="btn-action danger">
              <i class="fas fa-user-times"></i>
              Cancel Membership
            </button>
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
          gender: document.getElementById("onboardGender").value,
          fitness_level: document.getElementById("onboardFitnessLevel").value,
          goal: document.getElementById("onboardGoal").value
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
  </body>
</html>

