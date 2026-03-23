<?php
session_start();
require_once '../login/connection.php';
require_once '../includes/security.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {

        if ($action === 'get_notifications') {

            $txRows = $pdo->query("
                SELECT
                    id, receipt_number, customer_type, user_id,
                    customer_name, amount, payment_method, staff_id,
                    transaction_date, status, created_at, `desc`
                FROM transactions
                ORDER BY transaction_date DESC, created_at DESC
                LIMIT 50
            ")->fetchAll(PDO::FETCH_ASSOC);

            $stockRows = $pdo->query("
                SELECT id, item_name, category, quantity, price,
                       description, created_at, updated_at
                FROM inventory
                WHERE quantity <= 10
                ORDER BY quantity ASC, updated_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success'      => true,
                'transactions' => $txRows,
                'low_stock'    => $stockRows,
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard - Fit-Stop Gym</title>
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<div class="dashboard">

  <!-- ══════════ SIDEBAR ══════════ -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <img src="staffimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img">
      <span class="logo-text">Fit-Stop</span>
    </div>

    <ul class="menu">
      <li class="active" id="dashboardBtn" data-target="dashboard">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </li>
      <li id="clientRegBtn" data-target="clientRegistration">
        <i class="bi bi-person-plus"></i>
        <span>Client Registration</span>
      </li>
      <li id="inventoryBtn" data-target="inventory">
        <i class="bi bi-box-seam"></i>
        <span>Inventory</span>
      </li>
      <li id="attendanceBtn" data-target="attendance">
        <i class="bi bi-clipboard-check"></i>
        <span>Attendance</span>
      </li>
      <li id="memberBtn" data-target="memberManagement">
        <i class="bi bi-people"></i>
        <span>Members</span>
      </li>
      <li id="idGenBtn" data-target="idGeneration">
        <i class="bi bi-qr-code"></i>
        <span>ID Generation</span>
      </li>
      <li id="settingsBtn" data-target="settings">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </li>
    <li onclick="document.getElementById('logoutForm').submit()" style="cursor:pointer">
        <i class="bi bi-box-arrow-right"></i>
        <?php echo fitstop_csrf_input(); ?>
        <span>Logout</span>
      </li>
      <form id="logoutForm" action="../../login/logout.php" method="POST" style="display: none;">
        <?php echo fitstop_csrf_input(); ?>
        <button type="submit" class="nav-link border-0 bg-transparent" style="cursor: pointer;">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </form>
    </ul>
  </aside>

  <!-- ══════════ MAIN CONTENT ══════════ -->
  <main class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Staff Portal</h1>
        <p>Fit-Stop Gym — Management System</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-badge">
          <div class="topbar-dot"></div>
          Active Staff Member
        </div>
        <div class="topbar-badge">
          <i class="bi bi-calendar3"></i>
          <span id="currentDate">—</span>
        </div>

        <!-- NOTIFICATION BELL -->
        <div class="notif-wrapper" id="notifWrapper">
          <button class="notif-bell-btn" id="notifBellBtn" onclick="toggleNotifPanel()" title="Inventory Alerts">
            <i class="bi bi-bell-fill"></i>
            <span class="notif-badge hidden" id="notifBadge">0</span>
          </button>
          <div class="notif-panel" id="notifPanel">
            <div class="notif-panel-header">
              <h4><i class="bi bi-bell-fill" style="margin-right:6px;"></i>Notifications</h4>
              <span id="notifPanelCount">—</span>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-loader" id="notifLoader">
                <i class="bi bi-arrow-repeat"></i> Loading alerts...
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- PROFILE CONTAINER -->
    <div class="profile-container">
      <div class="profile-content">
        <div class="profile-text">
          <strong class="profile-name">Staff Portal</strong>
          <span class="profile-streak">🏋️ Active Staff Member</span>
        </div>
      </div>
    </div>

    <!-- ── DASHBOARD ── -->
    <section id="dashboard">

      <!-- Stats Row -->
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-icon members"><i class="bi bi-people-fill"></i></div>
          <div class="stat-info">
            <span class="stat-value" id="stat-checked-in">—</span>
            <span class="stat-label">Members Checked In</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon registrations"><i class="bi bi-person-check-fill"></i></div>
          <div class="stat-info">
            <span class="stat-value" id="stat-registrations">—</span>
            <span class="stat-label">New Registrations</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon equipment"><i class="bi bi-tools"></i></div>
          <div class="stat-info">
            <span class="stat-value" id="stat-equipment">—</span>
            <span class="stat-label">Equipment Issues</span>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon notifications"><i class="bi bi-bell-fill"></i></div>
          <div class="stat-info">
            <span class="stat-value" id="stat-notifications">—</span>
            <span class="stat-label">Pending Notifications</span>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <section>
        <h2>Quick Actions</h2>
        <div class="actions-grid">

          <div class="action-card">
            <div class="action-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h3>Register New Member</h3>
            <p>Fast client data capture &amp; ID generation</p>
            <button class="action-btn" onclick="document.getElementById('clientRegistration').scrollIntoView({behavior:'smooth'})">Start Registration</button>
          </div>

          <div class="action-card">
            <div class="action-icon"><i class="bi bi-qr-code-scan"></i></div>
            <h3>Scan Attendance</h3>
            <p>Track member check-ins via QR code</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
              <button class="action-btn" onclick="startScanner()">Scan QR Code</button>
              <button class="action-btn" id="stopScannerBtn" onclick="stopScanner()" style="display:none;background:#333;color:#fff;">Stop</button>
            </div>
            <div id="reader" style="width:100%;max-width:280px;"></div>
            <div style="margin-top:12px;width:100%;">
              <label style="display:block;margin-bottom:7px;color:#777;font-size:10.5px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;">Manual Attendance</label>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <select id="manualAttendanceUser" class="form-input" style="flex:1;min-width:160px;">
                  <option value="">Select a member...</option>
                </select>
                <button class="action-btn" onclick="submitManualAttendance()" style="flex-shrink:0;">Record</button>
              </div>
            </div>
          </div>

          <div class="action-card">
            <div class="action-icon"><i class="bi bi-box-seam-fill"></i></div>
            <h3>Update Inventory</h3>
            <p>Manage equipment stock &amp; status</p>
            <a href="inventory.php" class="action-btn">View Inventory</a>
          </div>

          <div class="action-card">
            <div class="action-icon"><i class="bi bi-card-checklist"></i></div>
            <h3>Generate ID</h3>
            <p>System-generated member IDs &amp; QR codes</p>
            <button class="action-btn">Create ID</button>
          </div>

        </div>
      </section>

    </section>

    <!-- ── CLIENT REGISTRATION ── -->
    <section class="registration-section" id="clientRegistration">
      <h2>Client Registration</h2>
      <div class="registration-card">
        <form class="registration-form" id="registrationForm">
          <div class="form-grid">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" id="regFullName" placeholder="Enter client name" class="form-input">
            </div>
            <div class="form-group">
              <label>Age</label>
              <input type="number" id="regAge" placeholder="Enter age" class="form-input">
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="tel" id="regContact" placeholder="09XXXXXXXXX" class="form-input">
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" id="regEmail" placeholder="email@example.com" class="form-input">
            </div>
            <div class="form-group">
              <label>Address</label>
              <input type="text" id="regAddress" placeholder="Complete address" class="form-input">
            </div>
            <div class="form-group">
              <label>Height (cm)</label>
              <input type="number" id="regHeight" placeholder="Enter height" class="form-input">
            </div>
            <div class="form-group">
              <label>Weight (kg)</label>
              <input type="number" id="regWeight" placeholder="Enter weight" class="form-input">
            </div>
            <div class="form-group">
              <label>Fitness Experience</label>
              <select class="form-input" id="regFitnessLevel">
                <option value="Beginner">Beginner</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
              </select>
            </div>
            <div class="form-group">
              <label>Primary Goal</label>
              <select class="form-input" id="regGoal">
                <option value="Weight Loss">Weight Loss</option>
                <option value="Muscle Gain">Muscle Gain</option>
                <option value="Endurance">Endurance</option>
                <option value="General Fitness">General Fitness</option>
              </select>
            </div>
            <div class="form-group">
              <label>Password</label>
              <input type="password" id="regPassword" placeholder="At least 8 chars with letters &amp; numbers" class="form-input">
            </div>
            <div class="form-group">
              <label>Confirm Password</label>
              <input type="password" id="regConfirmPassword" placeholder="Re-enter password" class="form-input">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-secondary" id="clearBtn">Clear Form</button>
            <button type="submit" class="btn-primary">Register &amp; Generate ID</button>
          </div>
        </form>
      </div>
    </section>

    <!-- ── PAYMENT PROCESSING ── -->
    <section class="registration-section">
      <h2>Payment Processing</h2>
      <div class="registration-card">
        <form class="registration-form" id="paymentForm">
          <div style="margin-bottom:20px;">
            <label style="color:var(--text-muted);font-size:10.5px;margin-bottom:10px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;display:block;">Customer Type</label>
            <div style="display:flex;gap:20px;">
              <label style="display:flex;align-items:center;cursor:pointer;color:var(--text-primary);gap:8px;font-size:13px;font-weight:600;">
                <input type="radio" name="customerType" value="member" checked onchange="toggleCustomerType('member')"> Member
              </label>
              <label style="display:flex;align-items:center;cursor:pointer;color:var(--text-primary);gap:8px;font-size:13px;font-weight:600;">
                <input type="radio" name="customerType" value="non-member" onchange="toggleCustomerType('non-member')"> Walk-In
              </label>
            </div>
          </div>
          <div class="form-grid">
            <div class="form-group" id="memberIdGroup">
              <label>Member ID</label>
              <input type="text" id="paymentMemberID" class="form-input" placeholder="#MB2024001">
            </div>
            <div class="form-group" id="customerNameGroup" style="display:none;">
              <label>Customer Name</label>
              <input type="text" id="paymentCustomerName" class="form-input" placeholder="Enter full name">
            </div>
            <div class="form-group">
              <label>Amount (₱)</label>
              <input type="number" id="paymentAmount" class="form-input" placeholder="0.00" step="0.01">
            </div>
            <div class="form-group">
              <label>Paid For</label>
              <input type="text" id="paymentPaidFor" class="form-input" placeholder="Monthly Membership / Protein Shake...">
            </div>
            <div class="form-group">
              <label>Optional Notes</label>
              <input type="text" id="paymentNotes" class="form-input" placeholder="Optional note">
            </div>
            <div class="form-group">
              <label>Payment Method</label>
              <select id="paymentMethod" class="form-input">
                <option value="">Select Method</option>
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
              </select>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-secondary" onclick="clearPaymentForm()">Clear</button>
            <button type="button" class="btn-primary" id="paymentSubmitBtn" onclick="processPayment()">Generate Receipt</button>
          </div>
        </form>
      </div>
    </section>

    <!-- ── INVENTORY ── -->
    <section class="inventory-section" id="inventory">
      <h2>Inventory Management</h2>
      <div class="inventory-header">
        <div class="search-container">
          <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search equipment...">
          </div>
          <button class="search-btn">Search</button>
        </div>

      </div>
      <div class="inventory-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Equipment Name</th>
              <th>Category</th>
              <th>Qty</th>
              <th>Status</th>
              <th>Last Maintenance</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="color:var(--text-muted);font-family:monospace;">#EQ001</td>
              <td style="color:var(--text-primary);font-weight:600;">Treadmill Pro X5</td>
              <td>Cardio</td><td>8</td>
              <td><span class="status-badge active">Active</span></td>
              <td>Jan 15, 2025</td>
              <td><button class="btn-icon"><i class="bi bi-pencil"></i></button><button class="btn-icon"><i class="bi bi-qr-code"></i></button></td>
            </tr>
            <tr>
              <td style="color:var(--text-muted);font-family:monospace;">#EQ002</td>
              <td style="color:var(--text-primary);font-weight:600;">Dumbbells Set</td>
              <td>Strength</td><td>45</td>
              <td><span class="status-badge active">Active</span></td>
              <td>Jan 10, 2025</td>
              <td><button class="btn-icon"><i class="bi bi-pencil"></i></button><button class="btn-icon"><i class="bi bi-qr-code"></i></button></td>
            </tr>
            <tr>
              <td style="color:var(--text-muted);font-family:monospace;">#EQ003</td>
              <td style="color:var(--text-primary);font-weight:600;">Stationary Bike</td>
              <td>Cardio</td><td>5</td>
              <td><span class="status-badge maintenance">Maintenance</span></td>
              <td>Dec 28, 2024</td>
              <td><button class="btn-icon"><i class="bi bi-pencil"></i></button><button class="btn-icon"><i class="bi bi-qr-code"></i></button></td>
            </tr>
            <tr>
              <td style="color:var(--text-muted);font-family:monospace;">#EQ004</td>
              <td style="color:var(--text-primary);font-weight:600;">Rowing Machine</td>
              <td>Cardio</td><td>3</td>
              <td><span class="status-badge low-stock">Low Stock</span></td>
              <td>Jan 05, 2025</td>
              <td><button class="btn-icon"><i class="bi bi-pencil"></i></button><button class="btn-icon"><i class="bi bi-qr-code"></i></button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- ── WORKOUT / ATTENDANCE ── -->
    <section class="attendance-section" id="attendance">
      <h2>Workout / Performance Log</h2>
      <div class="registration-card" style="margin-bottom:20px;">
        <div class="form-grid">
          <div class="form-group">
            <label>Member ID</label>
            <input type="text" id="perfID" class="form-input">
          </div>
          <div class="form-group">
            <label>Exercise</label>
            <input type="text" id="exercise" class="form-input" list="exerciseOptions" placeholder="Select or type exercise">
            <datalist id="exerciseOptions"></datalist>
          </div>
          <div class="form-group">
            <label id="performanceMetricLabel">Weight (kg)</label>
            <input type="number" id="performanceMetric" class="form-input" placeholder="Enter weight" step="0.1" min="0">
          </div>
          <div class="form-group">
            <label>Reps</label>
            <input type="number" id="reps" class="form-input">
          </div>
        </div>
        <div class="form-actions">
          <button class="btn-primary" onclick="logWorkout()">Save Workout</button>
        </div>
      </div>
      <div id="workoutLogs"></div>

      <h2 style="margin-top:32px;">Real-Time Attendance</h2>
      <div class="attendance-grid">
        <div class="attendance-card">
          <h3>Recent Check-Ins <span id="attendanceLiveTag" style="font-size:10px;color:var(--success);font-family:'DM Sans',sans-serif;font-weight:600;text-transform:none;letter-spacing:0;margin-left:8px;vertical-align:middle;">● Live</span></h3>
          <div class="attendance-list" id="realtimeAttendanceList">
            <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">Loading...</div>
          </div>
        </div>

        <div class="attendance-card">
          <h3>Weekly Summary</h3>
          <div class="summary-chart" id="weeklyChart">
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Mon" style="height:0%"></div></div><span>Mon</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Tue" style="height:0%"></div></div><span>Tue</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Wed" style="height:0%"></div></div><span>Wed</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Thu" style="height:0%"></div></div><span>Thu</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Fri" style="height:0%"></div></div><span>Fri</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Sat" style="height:0%"></div></div><span>Sat</span></div>
            <div class="chart-item"><div class="chart-bar-wrap"><div class="chart-bar" id="bar-Sun" style="height:0%"></div></div><span>Sun</span></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── MEMBER MANAGEMENT ── -->
    <section class="members-section" id="memberManagement">
      <h2>Active Members</h2>
      <div class="members-grid">
        <div class="member-card">
          <img src="staffimage/kevin.jpg" alt="Member" class="member-img">
          <h4>Kevin Barretto</h4>
          <p class="member-id">#MB2024001</p>
          <div class="member-details">
            <span><i class="bi bi-award"></i> Gold Plan</span>
            <span><i class="bi bi-fire"></i> 45-day streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item"><span class="label">BMI</span><span class="value">22.5</span></div>
            <div class="stat-item"><span class="label">Sessions</span><span class="value">68</span></div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>
        <div class="member-card">
          <img src="staffimage/cj.jpg" alt="Member" class="member-img">
          <h4>Charles Carillo</h4>
          <p class="member-id">#MB2024002</p>
          <div class="member-details">
            <span><i class="bi bi-award"></i> Silver Plan</span>
            <span><i class="bi bi-fire"></i> 28-day streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item"><span class="label">BMI</span><span class="value">24.1</span></div>
            <div class="stat-item"><span class="label">Sessions</span><span class="value">42</span></div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>
        <div class="member-card">
          <img src="staffimage/sha.jpg" alt="Member" class="member-img">
          <h4>Sharien Salarda</h4>
          <p class="member-id">#MB2024003</p>
          <div class="member-details">
            <span><i class="bi bi-award"></i> Gold Plan</span>
            <span><i class="bi bi-fire"></i> 92-day streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item"><span class="label">BMI</span><span class="value">21.8</span></div>
            <div class="stat-item"><span class="label">Sessions</span><span class="value">135</span></div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>
        <div class="member-card">
          <img src="staffimage/lance.jpg" alt="Member" class="member-img">
          <h4>Lance Chua</h4>
          <p class="member-id">#MB2024004</p>
          <div class="member-details">
            <span><i class="bi bi-award"></i> Bronze Plan</span>
            <span><i class="bi bi-fire"></i> 15-day streak</span>
          </div>
          <div class="member-stats">
            <div class="stat-item"><span class="label">BMI</span><span class="value">26.3</span></div>
            <div class="stat-item"><span class="label">Sessions</span><span class="value">18</span></div>
          </div>
          <button class="view-btn">View Profile</button>
        </div>
      </div>
    </section>

    <!-- ── ID GENERATION ── -->
    <section id="idGeneration">
      <h2>ID Generation</h2>
      <div class="registration-card">
        <div class="form-grid">
          <div class="form-group">
            <label>Member ID / Reference</label>
            <input type="text" id="idGenMemberRef" class="form-input" placeholder="Enter Member ID e.g. FS-2026-0001">
          </div>
        </div>
        <div class="form-actions">
          <button class="btn-primary" onclick="generateMemberID()">
            <i class="bi bi-qr-code" style="margin-right:6px;"></i>Generate ID Card
          </button>
        </div>
        <div id="idGenResult" style="margin-top:20px;"></div>
      </div>
    </section>

    <!-- ── SETTINGS ── -->
    <section id="settings">
      <h2>Settings</h2>
      <div class="registration-card">
        <p style="color:var(--text-muted);font-size:14px;">System settings will be configured here.</p>
      </div>
    </section>

    <!-- ── NOTIFICATIONS ── -->
    <section class="notifications-section">
      <h2>Pending Notifications</h2>
      <div class="notifications-list">
        <div class="notification-item priority-high">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong>Equipment Maintenance Required</strong>
            <p>Stationary Bike #EQ003 needs immediate attention</p>
            <span class="notification-time">30 mins ago</span>
          </div>
        </div>
        <div class="notification-item priority-medium">
          <i class="bi bi-calendar-event"></i>
          <div>
            <strong>Membership Renewal Reminder</strong>
            <p>5 members' subscriptions expiring in 3 days</p>
            <span class="notification-time">1 hour ago</span>
          </div>
        </div>
        <div class="notification-item priority-low">
          <i class="bi bi-box-seam"></i>
          <div>
            <strong>Inventory Low Stock Alert</strong>
            <p>Rowing Machine quantity below minimum threshold</p>
            <span class="notification-time">2 hours ago</span>
          </div>
        </div>
      </div>
    </section>

  </main>
</div>

<!-- ══════════ RECEIPT MODAL ══════════ -->
<div id="receiptModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:2000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
  <div style="background:#161616;border:1px solid #333;border-top:2px solid #FFCC00;padding:36px;max-width:480px;width:90%;color:#fff;">
    <div style="text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:1px dashed #333;">
      <h2 style="font-family:'Chakra Petch',sans-serif;text-transform:uppercase;letter-spacing:2px;color:#FFCC00;margin-bottom:4px;font-size:20px;">Receipt</h2>
      <p style="color:#666;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Payment Confirmed</p>
    </div>
    <div id="receiptContent" style="margin-bottom:24px;font-size:13.5px;line-height:1.8;"></div>
    <div style="display:flex;gap:10px;justify-content:center;">
      <button onclick="printReceipt()" style="background:#FFCC00;color:#000;border:none;padding:11px 24px;font-weight:700;cursor:pointer;font-family:'Chakra Petch',sans-serif;text-transform:uppercase;letter-spacing:.5px;font-size:12px;">Print</button>
      <button onclick="closeReceipt()" style="background:transparent;color:#FFCC00;border:1px solid rgba(255,204,0,0.4);padding:11px 24px;font-weight:700;cursor:pointer;font-family:'Chakra Petch',sans-serif;text-transform:uppercase;letter-spacing:.5px;font-size:12px;">Close</button>
    </div>
  </div>
</div>


<script>
document.querySelectorAll(".menu li").forEach(item => {
  item.addEventListener("click", function () {
    if (this.id === "logoutBtn") {
      const form = document.createElement("form");
      form.action = "../Login/logout.php";
      form.method = "POST";
      <?php echo fitstop_csrf_input(); ?> 
      document.body.appendChild(form);
      form.submit();
      return;
    }
    const targetId = this.getAttribute("data-target");
    if (targetId) {
      document.getElementById(targetId)?.scrollIntoView({ behavior: "smooth" });
    }
    document.querySelectorAll(".menu li").forEach(li => li.classList.remove("active"));
    this.classList.add("active");
  });
});

const d = new Date();
document.getElementById('currentDate').textContent = d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });

document.getElementById("clearBtn").addEventListener("click", function() {
  document.getElementById("registrationForm").reset();
});

document.getElementById("registrationForm").addEventListener("submit", function(event) {
  event.preventDefault();
  const fullName        = document.getElementById("regFullName").value.trim();
  const email           = document.getElementById("regEmail").value.trim();
  const age             = document.getElementById("regAge").value.trim();
  const heightCm        = document.getElementById("regHeight").value.trim();
  const weightKg        = document.getElementById("regWeight").value.trim();
  const fitnessLevel    = document.getElementById("regFitnessLevel").value;
  const goal            = document.getElementById("regGoal").value;
  const password        = document.getElementById("regPassword").value;
  const confirmPassword = document.getElementById("regConfirmPassword").value;

  if (!fullName || !email) { alert("Full name and email are required."); return; }
  if (!password || !confirmPassword) { alert("Password and confirm password are required."); return; }
  if (password.length < 8) { alert("Password must be at least 8 characters."); return; }
  if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) { alert("Password must include at least one letter and one number."); return; }
  if (password !== confirmPassword) { alert("Passwords do not match."); return; }

  fetch('../Database/create_member.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ full_name: fullName, email, age, height_cm: heightCm, weight_kg: weightKg, fitness_level: fitnessLevel, goal, password, confirm_password: confirmPassword })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { alert(data.error || 'Failed to create member.'); return; }
    alert('Member created!\nMember ID: ' + data.member_id_display + '\nUsername: ' + data.username);
    document.getElementById("registrationForm").reset();
    loadAttendanceMembers();
  })
  .catch(() => alert('Unable to create member right now.'));
});
</script>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let activeQrScanner = null;
let scannerRunning  = false;

function escapeHtml(v) {
  return String(v||'').replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;");
}

function timeAgo(datetimeStr) {
  const now  = new Date();
  const past = new Date(datetimeStr.replace(' ', 'T'));
  const diff = Math.floor((now - past) / 1000);
  if (diff < 60)    return diff + 's ago';
  if (diff < 3600)  return Math.floor(diff / 60) + ' min ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}

function resolveMemberRefFromQr(qrCodeMessage) {
  const raw = String(qrCodeMessage||'').trim();
  if (!raw) return '';
  if (/^FS-\d{4}-\d+$/i.test(raw)) return raw;
  let decoded = raw;
  try { decoded = decodeURIComponent(raw); } catch(e) { decoded = raw; }
  if (decoded.startsWith('{') && decoded.endsWith('}')) {
    try {
      const p    = JSON.parse(decoded);
      const pref = p.member_ref||p.member_id||p.member_id_display||p.user_id||p.id||p.username;
      return pref ? String(pref).trim() : '';
    } catch(e) { return ''; }
  }
  if (/^https?:\/\//i.test(decoded)) {
    try {
      const url = new URL(decoded);
      const fp  = url.searchParams.get('member_ref')||url.searchParams.get('member_id')||url.searchParams.get('member_id_display')||url.searchParams.get('id')||'';
      if (fp) return String(fp).trim();
    } catch(e) {}
  }
  return decoded;
}

function submitAttendance(memberRef, source) {
  return fetch('../Database/save_attendance.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ member_ref: memberRef, source })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) throw new Error(data.error || 'Unable to record attendance.');
    const label = data.member_display_name || memberRef;
    addNotification(data.point_awarded ? 'Attendance saved for ' + label + ' (+1 point today)' : 'Attendance saved for ' + label + ' (point already credited today)');
    return data;
  });
}

function loadAttendanceMembers() {
  fetch('../Database/get_attendance_members.php')
    .then(r => r.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.members)) return;
      const select = document.getElementById('manualAttendanceUser');
      if (!select) return;
      select.innerHTML = '<option value="">Select a member...</option>';
      data.members.forEach(m => {
        const o = document.createElement('option');
        o.value       = m.member_ref;
        o.textContent = m.display_name;
        select.appendChild(o);
      });
    }).catch(()=>{});
}

function startScanner() {
  const readerDiv = document.getElementById("reader");
  const stopBtn   = document.getElementById("stopScannerBtn");
  if (scannerRunning) return;
  readerDiv.innerHTML = "";
  if (!activeQrScanner) activeQrScanner = new Html5Qrcode("reader");
  activeQrScanner.start(
    { facingMode: "environment" }, { fps: 10, qrbox: 250 },
    qrCodeMessage => {
      const memberRef = resolveMemberRefFromQr(qrCodeMessage);
      if (!memberRef) { alert('Invalid QR data.'); return; }
      stopScanner();
      submitAttendance(memberRef, 'qr')
        .then(data => alert(data.point_awarded ? 'Attendance recorded. +1 point credited.' : 'Attendance recorded. Point already credited today.'))
        .catch(err => alert(err.message || 'Unable to save attendance.'));
    }, () => {}
  ).then(() => {
    scannerRunning = true;
    if (stopBtn) stopBtn.style.display = 'inline-block';
  }).catch(err => {
    scannerRunning = false;
    if (stopBtn) stopBtn.style.display = 'none';
    alert("Camera Error: " + err);
  });
}

function stopScanner() {
  const stopBtn   = document.getElementById("stopScannerBtn");
  const readerDiv = document.getElementById("reader");
  const finalize  = () => {
    scannerRunning = false;
    if (stopBtn)   stopBtn.style.display = 'none';
    if (readerDiv) readerDiv.innerHTML   = '';
  };
  if (!activeQrScanner) { finalize(); return; }
  activeQrScanner.stop().catch(()=>{}).then(() => activeQrScanner.clear()).catch(()=>{}).finally(() => { activeQrScanner = null; finalize(); });
}

function submitManualAttendance() {
  const select = document.getElementById('manualAttendanceUser');
  if (!select || !select.value) { alert('Please select a member first.'); return; }
  submitAttendance(select.value, 'manual')
    .then(data => alert(data.point_awarded ? 'Manual attendance recorded. +1 point credited.' : 'Manual attendance recorded. Point already credited today.'))
    .catch(err => alert(err.message || 'Unable to save attendance.'));
}

function processPayment() {
  const customerType     = document.querySelector('input[name="customerType"]:checked').value;
  const amount           = document.getElementById("paymentAmount").value.trim();
  const paidFor          = document.getElementById("paymentPaidFor").value.trim();
  const notes            = document.getElementById("paymentNotes").value.trim();
  const method           = document.getElementById("paymentMethod").value;
  const paymentSubmitBtn = document.getElementById("paymentSubmitBtn");
  let memberId = null, customerName = null;

  if (customerType === 'member') {
    memberId = document.getElementById("paymentMemberID").value.trim();
    if (!memberId) { alert("Please enter Member ID!"); return; }
  } else {
    customerName = document.getElementById("paymentCustomerName").value.trim();
    if (!customerName) { alert("Please enter customer name!"); return; }
  }
  if (!amount || !method || !paidFor) { alert("Please fill in all fields!"); return; }
  if (parseFloat(amount) <= 0) { alert("Amount must be greater than 0!"); return; }

  paymentSubmitBtn.disabled    = true;
  paymentSubmitBtn.textContent = 'Saving...';

  fetch('../Database/save_transaction.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ customer_type: customerType, member_ref: memberId, customer_name: customerName, amount: parseFloat(amount), payment_method: method, paid_for: paidFor, notes })
  })
  .then(r => r.json())
  .then(data => {
    paymentSubmitBtn.disabled    = false;
    paymentSubmitBtn.textContent = 'Generate Receipt';
    if (!data.success) { alert(data.error || 'Failed to save transaction.'); return; }
    displayReceipt(data.receipt);
    clearPaymentForm();
  })
  .catch(() => {
    paymentSubmitBtn.disabled    = false;
    paymentSubmitBtn.textContent = 'Generate Receipt';
    alert('Unable to save transaction right now.');
  });
}

function displayReceipt(receipt) {
  const modal   = document.getElementById("receiptModal");
  const content = document.getElementById("receiptContent");
  const customerInfo = receipt.customerType === 'member'
    ? `<p style="margin:5px 0;"><strong>Member ID:</strong> ${receipt.memberId}</p>`
    : `<p style="margin:5px 0;"><strong>Customer:</strong> ${receipt.customerName}</p>`;
  const noteInfo = receipt.notes ? `<p style="margin:5px 0;"><strong>Notes:</strong> ${receipt.notes}</p>` : '';
  content.innerHTML = `
    <div style="margin-bottom:14px;">
      <p style="margin:5px 0;color:#999;font-size:12px;"><strong style="color:#fff;">Receipt #:</strong> ${receipt.receiptNumber}</p>
      <p style="margin:5px 0;color:#999;font-size:12px;"><strong style="color:#fff;">Date:</strong> ${receipt.date} &nbsp; <strong style="color:#fff;">Time:</strong> ${receipt.time}</p>
    </div>
    <div style="padding:14px;background:#111;border:1px solid #2a2a2a;margin:14px 0;">
      ${customerInfo}
      <p style="margin:5px 0;"><strong>Type:</strong> ${receipt.customerType === 'member' ? 'Member' : 'Walk-In'}</p>
      <p style="margin:5px 0;"><strong>Paid For:</strong> ${receipt.paidFor || '-'}</p>
      <p style="margin:5px 0;"><strong>Payment:</strong> ${receipt.method}</p>
      <p style="margin:5px 0;"><strong>Status:</strong> <span style="color:#22d07a;">&#10003; ${receipt.status}</span></p>
      ${noteInfo}
    </div>
    <div style="border-top:1px dashed #333;padding-top:14px;">
      <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:700;">
        <span>TOTAL:</span>
        <span style="color:#FFCC00;">&#8369;${receipt.amount.toFixed(2)}</span>
      </div>
    </div>`;
  window.currentReceipt = receipt;
  modal.style.display   = "flex";
}

function closeReceipt() {
  document.getElementById("receiptModal").style.display = "none";
  addNotification("Receipt " + window.currentReceipt.receiptNumber + " generated");
}

function printReceipt() {
  const r            = window.currentReceipt;
  const customerInfo = r.customerType === 'member' ? `Member ID: ${r.memberId}` : `Customer: ${r.customerName}`;
  const noteInfo     = r.notes ? `<div class="row"><span>Notes:</span><span>${r.notes}</span></div>` : '';
  const pw = window.open('','','height=500,width=700');
  pw.document.write(`<html><head><title>Receipt - ${r.receiptNumber}</title><style>body{font-family:Arial,sans-serif;padding:40px;}h2{margin:0;font-size:22px;}.header{text-align:center;margin-bottom:30px;border-bottom:2px dashed #000;padding-bottom:20px;}.row{display:flex;justify-content:space-between;padding:8px 0;}.total{border-top:2px dashed #000;padding-top:20px;display:flex;justify-content:space-between;font-size:18px;font-weight:bold;}.footer{text-align:center;margin-top:30px;font-size:12px;color:#666;}</style></head><body><div class="header"><h2>FIT-STOP GYM</h2><p>Official Receipt</p></div><div><div class="row"><span>Receipt #:</span><span>${r.receiptNumber}</span></div><div class="row"><span>Date/Time:</span><span>${r.date} ${r.time}</span></div><div class="row"><span>${customerInfo}</span></div><div class="row"><span>Type:</span><span>${r.customerType==='member'?'Member':'Walk-In'}</span></div><div class="row"><span>Paid For:</span><span>${r.paidFor||'-'}</span></div><div class="row"><span>Payment:</span><span>${r.method}</span></div>${noteInfo}</div><div class="total"><span>TOTAL:</span><span>&#8369;${r.amount.toFixed(2)}</span></div><div class="footer"><p>Thank you for your payment!</p></div></body></html>`);
  pw.document.close();
  setTimeout(() => pw.print(), 100);
}

function toggleCustomerType(type) {
  document.getElementById('memberIdGroup').style.display     = type === 'member' ? 'block' : 'none';
  document.getElementById('customerNameGroup').style.display = type === 'member' ? 'none'  : 'block';
}

function clearPaymentForm() {
  document.getElementById("paymentForm").reset();
  toggleCustomerType('member');
}

let exerciseNameToId   = {};
let exerciseNameToType = {};

function loadExerciseOptions() {
  fetch('../Database/get_exercises.php')
    .then(r => r.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.exercises)) return;
      const datalist = document.getElementById('exerciseOptions');
      if (!datalist) return;
      exerciseNameToId   = {};
      exerciseNameToType = {};
      data.exercises.forEach(item => {
        exerciseNameToId[item.name.toLowerCase()]   = item.exercise_id;
        exerciseNameToType[item.name.toLowerCase()] = (item.movement_type||'').toLowerCase();
      });
      datalist.innerHTML = data.exercises.map(item => `<option value="${item.name}"></option>`).join('');
    }).catch(()=>{});
}

function updatePerformanceMetricField() {
  const ex    = document.getElementById('exercise');
  const label = document.getElementById('performanceMetricLabel');
  const input = document.getElementById('performanceMetric');
  if (!ex||!label||!input) return;
  const mt = exerciseNameToType[ex.value.trim().toLowerCase()]||'';
  if (mt === 'cardio') { label.textContent = 'Minutes'; input.placeholder = 'Enter minutes'; }
  else                 { label.textContent = 'Weight (kg)'; input.placeholder = 'Enter weight'; }
}

function logWorkout() {
  const id          = document.getElementById("perfID").value.trim();
  const exercise    = document.getElementById("exercise").value.trim();
  const metricValue = document.getElementById("performanceMetric").value.trim();
  const reps        = document.getElementById("reps").value.trim();

  if (!id||!exercise||!metricValue||!reps) { alert("Please provide Member ID, Exercise, Weight/Minutes, and Reps."); return; }

  const exerciseId = exerciseNameToId[exercise.toLowerCase()];
  if (!exerciseId) { alert("Please choose a valid exercise from the dropdown list."); return; }

  const mt        = exerciseNameToType[exercise.toLowerCase()]||'';
  const metricNum = parseFloat(metricValue);
  if (isNaN(metricNum)||metricNum<0) { alert("Please enter a valid Weight/Minutes value."); return; }

  fetch('../Database/save_workout_log.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ member_ref: id, exercise_id: exerciseId, reps: parseInt(reps,10), weight: metricNum })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { alert(data.error||"Failed to save workout log."); return; }
    const logs  = document.getElementById("workoutLogs");
    const entry = document.createElement("div");
    entry.classList.add("attendance-item");
    const ms = mt==='cardio'?'min':'kg';
    entry.innerHTML = `<strong style="color:var(--text-primary);font-family:'Chakra Petch',sans-serif;">${id}</strong> <span style="color:var(--text-muted);font-size:13px;margin-left:10px;">${exercise} · ${metricNum} ${ms} · ${reps} reps</span>`;
    logs.prepend(entry);
    addNotification("Workout logged for " + id);
  })
  .catch(() => alert("Unable to save workout log right now."));
}

function addNotification(message) {
  const list = document.querySelector(".notifications-list");
  const item = document.createElement("div");
  item.classList.add("notification-item","priority-low");
  item.innerHTML = `<i class="bi bi-bell-fill"></i><div><strong>${message}</strong><span class="notification-time">Just now</span></div>`;
  list.prepend(item);
}

document.addEventListener('DOMContentLoaded', function() {
  loadExerciseOptions();
  loadAttendanceMembers();
  loadRealtimeAttendance();
  setInterval(loadRealtimeAttendance, 15000);

  const ex = document.getElementById('exercise');
  if (ex) {
    ex.addEventListener('input', updatePerformanceMetricField);
    ex.addEventListener('change', updatePerformanceMetricField);
  }
  updatePerformanceMetricField();
});
</script>

<script>
function loadRealtimeAttendance() {
  fetch('realtime-attendance.php')
    .then(r => r.json())
    .then(data => {
      const list = document.getElementById('realtimeAttendanceList');
      if (!list) return;

      if (!data.success) {
        list.innerHTML = `<div style="padding:16px;color:var(--danger);font-size:12px;font-family:'Chakra Petch',sans-serif;">Error: ${escapeHtml(data.error || 'Unknown error')}</div>`;
        return;
      }

      if (data.stats) {
        document.getElementById('stat-checked-in').textContent    = data.stats.members_checked_in;
        document.getElementById('stat-registrations').textContent = data.stats.new_registrations;
        document.getElementById('stat-equipment').textContent     = data.stats.equipment_issues;
        document.getElementById('stat-notifications').textContent = data.stats.pending_notifications;
      }

      const records = Array.isArray(data.records) ? data.records : [];

      if (records.length === 0) {
        list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No check-ins today yet.</div>';
      } else {
        list.innerHTML = records.map(rec => {
          const name     = rec.display_name || ('User #' + rec.user_id);
          const initials = name.substring(0, 2).toUpperCase();
          const ago      = timeAgo(rec.datetime);
          return `
            <div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">${escapeHtml(initials)}</div>
                <div>
                  <strong>${escapeHtml(name)}</strong>
                  <span class="time">${escapeHtml(ago)}</span>
                </div>
              </div>
              <span class="check-in-badge">Check-In</span>
            </div>`;
        }).join('');
      }

      if (Array.isArray(data.weekly)) {
        const days     = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const counts   = data.weekly;
        const maxVal   = Math.max(...counts, 1);
        const today    = new Date().getDay();
        const todayIdx = today === 0 ? 6 : today - 1;

        days.forEach((day, i) => {
          const bar = document.getElementById('bar-' + day);
          if (!bar) return;
          bar.style.height = Math.round((counts[i] / maxVal) * 100) + '%';
          bar.className    = 'chart-bar' + (i === todayIdx ? ' active' : '');
        });
      }
    })
    .catch(() => {
      const list = document.getElementById('realtimeAttendanceList');
      if (list) list.innerHTML = `<div style="padding:16px;color:var(--danger);font-size:12px;font-family:'Chakra Petch',sans-serif;">Failed to reach server. Check that realtime-attendance.php exists in the Staff folder.</div>`;
    });
}
</script>

<script>
let notifPanelOpen = false;
let notifLoaded    = false;

function toggleNotifPanel() {
  notifPanelOpen = !notifPanelOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifPanelOpen);
  if (notifPanelOpen && !notifLoaded) loadNotificationHistory();
}

document.addEventListener('click', function(e) {
  const wrapper = document.getElementById('notifWrapper');
  if (notifPanelOpen && wrapper && !wrapper.contains(e.target)) {
    notifPanelOpen = false;
    document.getElementById('notifPanel').classList.remove('open');
  }
});

function formatTs(ts) {
  if (!ts) return '—';
  const d = new Date(ts.replace(' ', 'T'));
  if (isNaN(d)) return ts;
  return d.toLocaleString('en-PH', {
    month:'short', day:'numeric', year:'numeric',
    hour:'2-digit', minute:'2-digit', hour12:true
  });
}

function loadNotificationHistory() {
  const list  = document.getElementById('notifList');
  const count = document.getElementById('notifPanelCount');
  list.innerHTML = '<div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>';

  fetch('staff.php', { method: 'POST', body: (() => { const f = new FormData(); f.append('action','get_notifications'); return f; })() })
    .then(r => r.json())
    .then(data => {
      notifLoaded = true;
      if (!data.success) {
        list.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load history.</div>';
        return;
      }

      const transactions = Array.isArray(data.transactions) ? data.transactions : [];
      const lowStock     = Array.isArray(data.low_stock)    ? data.low_stock    : [];
      const total        = transactions.length + lowStock.length;

      if (count) count.textContent = total + ' record' + (total !== 1 ? 's' : '');

      if (total === 0) {
        list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>No notifications yet.</div>';
        return;
      }

      const txHtml = transactions.map(t => {
        const who    = t.customer_name || (t.customer_type === 'member' ? 'Member #' + t.user_id : 'Walk-In');
        const amount = parseFloat(t.amount || 0).toFixed(2);
        const method = t.payment_method || '—';
        const status = t.status || 'paid';
        const ts     = formatTs(t.transaction_date || t.created_at);
        return `
          <div class="notif-item">
            <div class="notif-icon" style="background:rgba(34,208,122,0.12);border-color:rgba(34,208,122,0.3);color:var(--success);">
              <i class="bi bi-receipt"></i>
            </div>
            <div class="notif-body">
              <p class="notif-msg">
                <strong>${escapeHtml(t.receipt_number || 'Receipt')}</strong><br>
                <span class="item-highlight">&#8369;${escapeHtml(amount)}</span>
                · ${escapeHtml(who)} · ${escapeHtml(method)}
                · <span style="text-transform:capitalize;">${escapeHtml(status)}</span>
              </p>
              <span class="notif-time"><i class="bi bi-clock"></i> ${ts}</span>
            </div>
          </div>`;
      }).join('');

      const stockHtml = lowStock.map(i => {
        const qty       = parseInt(i.quantity);
        const ts        = formatTs(i.updated_at);
        const iconColor = qty === 0 ? 'var(--danger)'        : 'var(--warning)';
        const bgColor   = qty === 0 ? 'rgba(255,71,87,0.12)' : 'rgba(255,159,67,0.12)';
        const bdColor   = qty === 0 ? 'rgba(255,71,87,0.3)'  : 'rgba(255,159,67,0.3)';
        const icon      = qty === 0 ? 'bi-x-circle-fill'     : 'bi-exclamation-triangle-fill';
        return `
          <div class="notif-item">
            <div class="notif-icon" style="background:${bgColor};border-color:${bdColor};color:${iconColor};">
              <i class="bi ${icon}"></i>
            </div>
            <div class="notif-body">
              <p class="notif-msg">
                <strong>${escapeHtml(i.item_name)}</strong> — ${escapeHtml(i.category)}<br>
                Stock: <span class="item-highlight">${qty} unit${qty !== 1 ? 's' : ''}</span>
                ${qty === 0 ? '— <span style="color:var(--danger);font-weight:700;">OUT OF STOCK</span>' : '— Low Stock'}
              </p>
              <span class="notif-time"><i class="bi bi-clock"></i> Updated: ${ts}</span>
            </div>
          </div>`;
      }).join('');

      list.innerHTML = txHtml + (lowStock.length ? `
        <div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">
          Inventory Alerts
        </div>` + stockHtml : '');
    })
    .catch(() => {
      list.innerHTML = '<div class="notif-empty"><i class="bi bi-wifi-off"></i>Unable to load notifications.</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
  fetch('staff.php', { method: 'POST', body: (() => { const f = new FormData(); f.append('action','get_notifications'); return f; })() })
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const txCount    = Array.isArray(data.transactions) ? data.transactions.length : 0;
      const stockCount = Array.isArray(data.low_stock)    ? data.low_stock.length    : 0;
      const total      = txCount + stockCount;
      const badge      = document.getElementById('notifBadge');
      if (total > 0) {
        badge.textContent = total > 99 ? '99+' : total;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    }).catch(() => {});
});
</script>

<script>
function generateMemberID() {
  const memberRef = document.getElementById('idGenMemberRef').value.trim();
  if (!memberRef) { alert('Please enter a Member ID or reference.'); return; }

  const resultDiv     = document.getElementById('idGenResult');
  resultDiv.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">Generating ID card...</p>';

  fetch('../Database/generate_member_id.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ member_ref: memberRef })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) {
      resultDiv.innerHTML = `<p style="color:var(--danger);font-size:13px;">${escapeHtml(data.error || 'Failed to generate ID.')}</p>`;
      return;
    }
    resultDiv.innerHTML = `
      <div style="border:1px solid var(--border-accent);padding:20px;display:inline-block;background:var(--bg-surface);text-align:center;">
        <p style="font-family:'Chakra Petch',sans-serif;color:var(--hazard);font-size:13px;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Member ID Card Generated</p>
        ${data.qr_image ? `<img src="${data.qr_image}" alt="QR Code" style="width:160px;height:160px;display:block;margin:0 auto 12px;border:2px solid var(--hazard);">` : ''}
        <p style="color:var(--text-primary);font-weight:700;font-family:'Chakra Petch',sans-serif;font-size:15px;letter-spacing:1px;">${escapeHtml(data.member_id_display || memberRef)}</p>
        ${data.full_name ? `<p style="color:var(--text-muted);font-size:12px;margin-top:4px;">${escapeHtml(data.full_name)}</p>` : ''}
        ${data.download_url ? `<a href="${data.download_url}" download class="action-btn" style="display:inline-block;margin-top:14px;text-decoration:none;"><i class="bi bi-download" style="margin-right:5px;"></i>Download</a>` : ''}
      </div>`;
  })
  .catch(() => {
    resultDiv.innerHTML = '<p style="color:var(--danger);font-size:13px;">Unable to generate ID right now.</p>';
  });
}
</script>

</body>
</html>