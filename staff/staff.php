<?php
session_start();
require_once '../login/connection.php';
require_once '../includes/security.php';
date_default_timezone_set('Asia/Manila');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {

        if ($action === 'get_notifications') {
            $txRows = $pdo->query("
                SELECT id, receipt_number, customer_type, user_id,
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

        if ($action === 'check_monthly_dup') {
            $name  = trim($_POST['name'] ?? '');
            $today = date('Y-m-d');

            if (empty($name)) {
                echo json_encode(['duplicate' => false]);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT id, expires_in FROM monthly
                WHERE name = :name AND expires_in >= :today
                LIMIT 1
            ");
            $stmt->execute([':name' => $name, ':today' => $today]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                echo json_encode([
                    'duplicate'  => true,
                    'monthly_id' => $existing['id'],
                    'expires_in' => $existing['expires_in'],
                ]);
            } else {
                echo json_encode(['duplicate' => false]);
            }
            exit;
        }

        if ($action === 'get_members') {
            $members = $pdo->query("
                SELECT id, username, first_name, last_name, email
                FROM users
                WHERE user_type = 'user'
                ORDER BY first_name ASC, last_name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'members' => $members]);
            exit;
        }

        if ($action === 'get_active_members') {
            $members = $pdo->query("
                SELECT u.id, u.username, u.first_name, u.last_name, u.email,
                       u.points, u.created_at, u.profile_picture,
                       MAX(a.datetime) AS last_attendance
                FROM users u
                LEFT JOIN attendance a ON a.user_id = u.id
                WHERE u.user_type = 'user'
                GROUP BY u.id
                ORDER BY u.first_name ASC, u.last_name ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'members' => $members]);
            exit;
        }

        if ($action === 'save_monthly') {
            $memberId = $_POST['member_id'] ?? null;
            $name     = trim($_POST['name'] ?? '');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Name is required.']);
                exit;
            }

            $today = date('Y-m-d');

            $checkStmt = $pdo->prepare("
                SELECT id, expires_in FROM monthly
                WHERE name = :name AND expires_in >= :today
                LIMIT 1
            ");
            $checkStmt->execute([':name' => $name, ':today' => $today]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                echo json_encode([
                    'success'    => false,
                    'duplicate'  => true,
                    'message'    => $name . ' already has an active monthly subscription that expires on ' . $existing['expires_in'] . '.',
                ]);
                exit;
            }

            $expiresIn = date('Y-m-d', strtotime('+30 days'));

            $stmt = $pdo->prepare("
                INSERT INTO monthly (member, name, expires_in)
                VALUES (:member, :name, :expires_in)
            ");
            $stmt->execute([
                ':member'     => $memberId ?: null,
                ':name'       => $name,
                ':expires_in' => $expiresIn,
            ]);

            echo json_encode([
                'success'    => true,
                'expires_in' => $expiresIn,
                'insert_id'  => $pdo->lastInsertId(),
            ]);
            exit;
        }

        if ($action === 'renew_monthly') {
            $monthlyId = $_POST['monthly_id'] ?? null;

            if (!$monthlyId) {
                echo json_encode(['success' => false, 'message' => 'Subscription ID missing.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, name, expires_in FROM monthly WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $monthlyId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Subscription record not found.']);
                exit;
            }

            $currentExpiry  = new DateTime($row['expires_in']);
            $newExpiry      = (clone $currentExpiry)->modify('+30 days');
            $newExpiryStr   = $newExpiry->format('Y-m-d');
            $daysLeft       = (new DateTime())->diff($currentExpiry)->days;

            $update = $pdo->prepare("UPDATE monthly SET expires_in = :expires_in WHERE id = :id");
            $update->execute([':expires_in' => $newExpiryStr, ':id' => $monthlyId]);

            echo json_encode([
                'success'    => true,
                'new_expiry' => $newExpiryStr,
                'days_left'  => $daysLeft,
                'name'       => $row['name'],
            ]);
            exit;
        }

        if ($action === 'record_monthly_walkin_attendance') {
            $name     = trim($_POST['name'] ?? '');
            $monthId  = (int)($_POST['month_id'] ?? 0);

            if (empty($name) || !$monthId) {
                echo json_encode(['success' => false, 'message' => 'Missing data.']);
                exit;
            }

            $today = date('Y-m-d');

            $dupCheck = $pdo->prepare("
                SELECT id FROM walk_attendance
                WHERE month_id = :mid AND DATE(datetime) = :today
                LIMIT 1
            ");
            $dupCheck->execute([':mid' => $monthId, ':today' => $today]);
            if ($dupCheck->fetch()) {
                echo json_encode(['success' => true, 'duplicate' => true, 'message' => 'Already logged today.']);
                exit;
            }

            $now = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("
                INSERT INTO walk_attendance (name, month_id, datetime)
                VALUES (:name, :mid, :datetime)
            ");
            $ins->execute([':name' => $name, ':mid' => $monthId, ':datetime' => $now]);

            echo json_encode(['success' => true, 'insert_id' => $pdo->lastInsertId()]);
            exit;
        }

        if ($action === 'record_walkin_from_receipt') {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Name missing.']);
                exit;
            }
            $today = date('Y-m-d');
            $dupCheck = $pdo->prepare("
                SELECT id FROM walk_attendance
                WHERE name = :name AND DATE(datetime) = :today AND month_id IS NULL
                LIMIT 1
            ");
            $dupCheck->execute([':name' => $name, ':today' => $today]);
            if ($dupCheck->fetch()) {
                echo json_encode(['success' => true, 'duplicate' => true, 'message' => 'Already logged today.']);
                exit;
            }
            $ins = $pdo->prepare("
                INSERT INTO walk_attendance (name, month_id, datetime)
                VALUES (:name, NULL, :datetime)
            ");
            $ins->execute([':name' => $name, ':datetime' => date('Y-m-d H:i:s')]);
            echo json_encode(['success' => true, 'insert_id' => $pdo->lastInsertId()]);
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
  <meta name="csrf-token" content="<?php echo fitstop_csrf_token(); ?>">
  <title>Staff Dashboard - Fit-Stop Gym</title>
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .member-select-wrap {
      position: relative;
    }
    .member-search-input {
      width: 100%;
      padding: 11px 14px;
      border: 1px solid var(--border);
      background: var(--bg-surface);
      color: var(--text-primary);
      font-family: 'DM Sans', sans-serif;
      font-size: 13.5px;
      box-sizing: border-box;
    }
    .member-search-input:focus {
      outline: none;
      border-color: var(--hazard);
    }
    .member-dropdown-list {
      display: none;
      position: absolute;
      top: 100%; left: 0; right: 0;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-top: none;
      max-height: 200px;
      overflow-y: auto;
      z-index: 500;
    }
    .member-dropdown-list.open { display: block; }
    .member-dropdown-item {
      padding: 10px 14px;
      cursor: pointer;
      font-size: 13px;
      color: var(--text-primary);
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .member-dropdown-item:hover { background: rgba(255,204,0,0.08); color: var(--hazard); }
    .member-dropdown-item .member-id-tag {
      font-size: 10.5px;
      color: var(--text-muted);
      font-family: monospace;
      margin-left: 6px;
    }
    .member-dropdown-empty {
      padding: 12px 14px;
      font-size: 12px;
      color: var(--text-muted);
    }
    .member-clear-btn {
      padding: 9px 12px;
      background: transparent;
      border: 1px solid rgba(255,204,0,0.3);
      color: #FFCC00;
      cursor: pointer;
      font-size: 13px;
      flex-shrink: 0;
      line-height: 1;
      transition: background 0.15s;
    }
    .member-clear-btn:hover {
      background: rgba(255,204,0,0.08);
    }
  </style>
</head>
<body>

<div class="dashboard">

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
      <li id="inventoryBtn" onclick="window.location.href='inventory.php'" style="cursor:pointer;">
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
      <li id="monthlyBtn" onclick="window.location.href='monthly.php'" style="cursor:pointer;">
        <i class="bi bi-calendar-check"></i>
        <span>Monthly Access</span>
      </li>

      <li onclick="window.location.href='walkin_attendance.php'" style="cursor:pointer;">
      <i class="bi bi-person-walking"></i>
      <span>Walk-In Log</span>
      </li>
      <li id="settingsBtn" data-target="settings">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </li>
      <li onclick="document.getElementById('logoutForm').submit()" style="cursor:pointer">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </li>
      <form id="logoutForm" action="../../login/logout.php" method="POST" style="display: none;">
        <?php echo fitstop_csrf_input(); ?>
      </form>
    </ul>
  </aside>

  <main class="main-content">

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

    <div class="profile-container">
      <div class="profile-content">
        <div class="profile-text">
          <strong class="profile-name">Staff Portal</strong>
          <span class="profile-streak">🏋️ Active Staff Member</span>
        </div>
      </div>
    </div>

    <section id="dashboard">
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
          <div class="stat-icon notifications"><i class="bi bi-bell-fill"></i></div>
          <div class="stat-info">
            <span class="stat-value" id="stat-notifications">—</span>
            <span class="stat-label">Pending Notifications</span>
          </div>
        </div>
      </div>

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
              <label>Member <span style="font-size:10px;color:var(--text-muted);">(users only)</span></label>
              <div class="member-select-wrap">
                <div style="display:flex;gap:6px;align-items:center;">
                  <input type="text" id="memberSearchInput" class="member-search-input form-input"
                    placeholder="Search member name or username..." autocomplete="off"
                    oninput="filterMemberDropdown(this.value)" onfocus="openMemberDropdown()" style="flex:1;">
                  <button type="button" id="memberClearBtn" class="member-clear-btn"
                    onclick="clearMemberSelection()" style="display:none;" title="Clear">&#x2715;</button>
                </div>
                <input type="hidden" id="paymentMemberID">
                <div class="member-dropdown-list" id="memberDropdownList"></div>
              </div>
            </div>

            <div class="form-group" id="customerNameGroup" style="display:none;">
              <label>Customer Name</label>
              <input type="text" id="paymentCustomerName" class="form-input" placeholder="Enter full name">
            </div>

            <div class="form-group">
              <label>Payment Method</label>
              <select id="paymentMethod" class="form-input" onchange="toggleGcashRef(this.value)">
                <option value="">Select Method</option>
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
              </select>
            </div>
          </div>

          <div id="gcashRefGroup" style="display:none;margin-bottom:16px;padding:14px;border:1px solid rgba(255,204,0,0.25);background:rgba(255,204,0,0.04);">
            <label style="color:var(--text-muted);font-size:10.5px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;display:block;margin-bottom:8px;">
              GCash Reference No. <span style="color:var(--danger);font-size:11px;text-transform:none;letter-spacing:0;">* Required</span>
            </label>
            <input type="text" id="gcashRefNumber" class="form-input"
              placeholder="Enter 13-digit GCash reference number"
              maxlength="13"
              oninput="this.value=this.value.replace(/\D/g,'')"
              style="max-width:320px;">
            <span style="font-size:11px;color:var(--text-muted);margin-top:6px;display:block;">
              <i class="bi bi-info-circle" style="margin-right:4px;"></i>Found in your GCash confirmation SMS after payment.
            </span>
          </div>

          <div style="border-top:1px solid var(--border);padding-top:18px;margin-top:4px;">
            <label style="color:var(--text-muted);font-size:10.5px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;display:block;margin-bottom:12px;">Add Item to Cart</label>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
              <div class="form-group" style="flex:2;min-width:160px;margin:0;">
                <label>Item / Service</label>
                <select id="paymentPaidFor" class="form-input" onchange="autoFillAmount(this.value)">
                  <option value="">Select category...</option>
                  <option value="Membership">Membership / Renewal</option>
                  <option value="Monthly">Monthly</option>
                  <option value="Day Pass / Walk-In">Day Pass / Walk-In</option>
                  <option value="Special Rate">Special Rate</option>
                  <optgroup label="── Inventory Items ──">
                    <option value="Inventory:2:Sting"        data-inv-id="2"  data-price="20">Sting (Beverage)</option>
                    <option value="Inventory:3:Amino"        data-inv-id="3"  data-price="10">Amino (Supplements)</option>
                    <option value="Inventory:4:Pre-Workout"  data-inv-id="4"  data-price="35">Pre-Workout (Supplements)</option>
                    <option value="Inventory:5:Gatorade"     data-inv-id="5"  data-price="25">Gatorade (Beverage)</option>
                    <option value="Inventory:6:Creatine"     data-inv-id="6"  data-price="20">Creatine (Supplements)</option>
                    <option value="Inventory:7:Whey"         data-inv-id="7"  data-price="75">Whey (Supplements)</option>
                    <option value="Inventory:8:Protein Bar"  data-inv-id="8"  data-price="120">Protein Bar (Snacks)</option>
                  </optgroup>
                </select>
              </div>
              <div class="form-group" style="width:80px;margin:0;">
                <label>Qty</label>
                <input type="number" id="paymentQty" value="1" min="1" class="form-input" style="text-align:center;" oninput="updateUnitTotal()">
              </div>
              <div class="form-group" style="width:120px;margin:0;">
                <label>Unit Price (₱)</label>
                <input type="number" id="paymentAmount" class="form-input" step="0.01" placeholder="0.00" oninput="updateUnitTotal()">
              </div>
              <div class="form-group" style="margin:0;">
                <label style="visibility:hidden;">Add</label>
                <button type="button" class="btn-primary" onclick="addToCart()"
                  style="padding:10px 18px;font-size:12px;white-space:nowrap;">+ Add to Cart</button>
              </div>
            </div>
            <div style="margin-top:6px;text-align:right;font-size:12px;color:var(--text-muted);">
              Item subtotal: ₱<span id="unitSubtotal">0.00</span>
            </div>
          </div>

          <div style="margin-top:20px;">
            <div style="display:flex;align-items:center;margin-bottom:10px;gap:8px;">
              <span style="font-size:10.5px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;color:var(--text-muted);">Cart</span>
              <span id="cartBadge" style="background:var(--hazard);color:#000;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;font-family:'Chakra Petch',sans-serif;display:none;">0</span>
            </div>
            <div id="cartEmpty" style="border:1px dashed var(--border);padding:18px;text-align:center;color:var(--text-muted);font-size:13px;">
              No items added yet.
            </div>
            <div id="cartTableWrap" style="display:none;">
              <div style="display:grid;grid-template-columns:1fr 90px 90px 90px 34px;gap:8px;padding:8px 0;border-bottom:1px solid var(--border);font-size:10.5px;text-transform:uppercase;letter-spacing:.8px;font-weight:700;color:var(--text-muted);">
                <span>Item</span><span>Unit ₱</span><span>Qty</span><span>Subtotal</span><span></span>
              </div>
              <div id="cartRows"></div>
              <div style="display:flex;justify-content:flex-end;align-items:center;gap:16px;padding:16px 0 0;border-top:1px dashed var(--border);margin-top:8px;">
                <span style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Grand Total</span>
                <span style="font-size:22px;font-weight:700;font-family:'Chakra Petch',sans-serif;color:var(--hazard);">₱<span id="cartGrandTotal">0.00</span></span>
              </div>
            </div>
          </div>

          <div class="form-actions" style="margin-top:20px;">
            <button type="button" class="btn-secondary" onclick="clearPaymentForm()">Clear All</button>
            <button type="button" class="btn-primary" id="paymentSubmitBtn" onclick="processPayment()">Generate Receipt</button>
          </div>
        </form>
      </div>
    </section>

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

    <section class="members-section" id="memberManagement">
      <h2>Active Members</h2>
      <div id="membersGrid" class="members-grid">
        <div style="padding:24px;color:var(--text-muted);font-size:13px;">Loading members...</div>
      </div>
    </section>

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

    <section id="settings">
      <h2>Settings</h2>
      <div class="registration-card">
        <p style="color:var(--text-muted);font-size:14px;">System settings will be configured here.</p>
      </div>
    </section>
    </div>

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
document.querySelectorAll('.menu li').forEach(item => {
  item.addEventListener('click', function () {
    const targetId = this.getAttribute('data-target');
    if (targetId) {
      document.getElementById(targetId)?.scrollIntoView({ behavior: 'smooth' });
    }
    document.querySelectorAll('.menu li').forEach(li => li.classList.remove('active'));
    this.classList.add('active');
  });
});

const d = new Date();
document.getElementById('currentDate').textContent = d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });

document.getElementById('clearBtn').addEventListener('click', function() {
  document.getElementById('registrationForm').reset();
});

document.getElementById('registrationForm').addEventListener('submit', function(event) {
  event.preventDefault();
  const fullName        = document.getElementById('regFullName').value.trim();
  const email           = document.getElementById('regEmail').value.trim();
  const age             = document.getElementById('regAge').value.trim();
  const address         = document.getElementById('regAddress').value.trim();
  const heightCm        = document.getElementById('regHeight').value.trim();
  const weightKg        = document.getElementById('regWeight').value.trim();
  const fitnessLevel    = document.getElementById('regFitnessLevel').value;
  const goal            = document.getElementById('regGoal').value;
  const password        = document.getElementById('regPassword').value;
  const confirmPassword = document.getElementById('regConfirmPassword').value;

  if (!fullName || !email) { alert('Full name and email are required.'); return; }
  if (!password || !confirmPassword) { alert('Password and confirm password are required.'); return; }
  if (password.length < 8) { alert('Password must be at least 8 characters.'); return; }
  if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) { alert('Password must include at least one letter and one number.'); return; }
  if (password !== confirmPassword) { alert('Passwords do not match.'); return; }

  fetch('../Database/create_member.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ full_name: fullName, email, age, address, height_cm: heightCm, weight_kg: weightKg, fitness_level: fitnessLevel, goal, password, confirm_password: confirmPassword })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { alert(data.error || 'Failed to create member.'); return; }
    alert('Member created!\nMember ID: ' + data.member_id_display + '\nUsername: ' + data.username);
    document.getElementById('registrationForm').reset();
    loadAttendanceMembers();
    loadMembersForPayment();
  })
  .catch(() => alert('Unable to create member right now.'));
});
</script>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let activeQrScanner = null;
let scannerRunning  = false;
let allMembers      = [];

function escapeHtml(v) {
  return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function timeAgo(datetimeStr) {
  const diff = Math.floor((new Date() - new Date(datetimeStr.replace(' ','T'))) / 1000);
  if (diff < 60)    return diff + 's ago';
  if (diff < 3600)  return Math.floor(diff/60) + ' min ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return Math.floor(diff/86400) + 'd ago';
}

function loadMembersForPayment() {
  const fd = new FormData();
  fd.append('action', 'get_members');
  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success && Array.isArray(data.members)) {
        allMembers = data.members;
      }
    }).catch(()=>{});
}

function openMemberDropdown() {
  filterMemberDropdown(document.getElementById('memberSearchInput').value);
  document.getElementById('memberDropdownList').classList.add('open');
}

function filterMemberDropdown(query) {
  const list = document.getElementById('memberDropdownList');
  query = query.toLowerCase().trim();

  const filtered = allMembers.filter(m => {
    const fullName = ((m.first_name||'') + ' ' + (m.last_name||'')).toLowerCase();
    return fullName.includes(query) || (m.username||'').toLowerCase().includes(query);
  });

  if (filtered.length === 0) {
    list.innerHTML = '<div class="member-dropdown-empty">No members found.</div>';
  } else {
    list.innerHTML = filtered.map(m => {
      const fullName = [m.first_name, m.last_name].filter(Boolean).join(' ') || m.username;
      return `<div class="member-dropdown-item" onclick="selectMember(${m.id}, '${escapeHtml(fullName)}', '${escapeHtml(m.username||'')}')">
        ${escapeHtml(fullName)}
        <span class="member-id-tag">@${escapeHtml(m.username||'')} · ID:${m.id}</span>
      </div>`;
    }).join('');
  }
  list.classList.add('open');
}

function selectMember(id, fullName, username) {
  document.getElementById('paymentMemberID').value      = id;
  document.getElementById('memberSearchInput').value    = fullName + ' (@' + username + ')';
  document.getElementById('memberDropdownList').classList.remove('open');
  document.getElementById('memberClearBtn').style.display = 'inline-block';
}

function clearMemberSelection() {
  document.getElementById('paymentMemberID').value      = '';
  document.getElementById('memberSearchInput').value    = '';
  document.getElementById('memberClearBtn').style.display = 'none';
  document.getElementById('memberDropdownList').classList.remove('open');
  document.getElementById('memberSearchInput').focus();
  filterMemberDropdown('');
}

document.addEventListener('click', function(e) {
  const wrap = document.querySelector('.member-select-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('memberDropdownList').classList.remove('open');
  }
});

function toggleCustomerType(type) {
  document.getElementById('memberIdGroup').style.display     = type === 'member' ? 'block' : 'none';
  document.getElementById('customerNameGroup').style.display = type === 'member' ? 'none'  : 'block';
  if (type === 'member') {
    document.getElementById('memberSearchInput').value = '';
    document.getElementById('paymentMemberID').value   = '';
    document.getElementById('memberClearBtn').style.display = 'none';
  }
  const paidForSelect = document.getElementById('paymentPaidFor');
  if (paidForSelect) {
    autoFillAmount(paidForSelect.value);
  }
}

function toggleGcashRef(method) {
  const group = document.getElementById('gcashRefGroup');
  const input = document.getElementById('gcashRefNumber');
  if (method === 'GCash') {
    group.style.display = 'block';
    input.required = true;
  } else {
    group.style.display = 'none';
    input.required = false;
    input.value = '';
  }
}

let payCart = [];

function updateUnitTotal() {
  const price = parseFloat(document.getElementById('paymentAmount').value) || 0;
  const qty   = parseInt(document.getElementById('paymentQty').value) || 0;
  document.getElementById('unitSubtotal').textContent = (price * qty).toFixed(2);
}

function autoFillAmount(paidFor) {
  const amountInput  = document.getElementById('paymentAmount');
  const customerType = document.querySelector('input[name="customerType"]:checked')?.value || 'member';

  if (paidFor && paidFor.startsWith('Inventory:')) {
    const sel   = document.getElementById('paymentPaidFor');
    const opt   = sel ? sel.options[sel.selectedIndex] : null;
    const price = opt ? parseFloat(opt.dataset.price) : null;
    amountInput.value = (price !== null && !isNaN(price)) ? price.toFixed(2) : '';
    updateUnitTotal();
    return;
  }

  const memberPrices = {
    'Membership':         500,
    'Monthly':            650,
    'Day Pass / Walk-In': 50,
    'Special Rate':       40,
  };

  const walkInPrices = {
    'Membership':         650,
    'Monthly':            750,
    'Day Pass / Walk-In': 60,
    'Special Rate':       40,
  };

  const priceTable = customerType === 'non-member' ? walkInPrices : memberPrices;
  const price = priceTable[paidFor] ?? null;
  amountInput.value = price !== null ? price.toFixed(2) : '';
  updateUnitTotal();
}

function addToCart() {
  const paidFor = document.getElementById('paymentPaidFor').value;
  const qty     = parseInt(document.getElementById('paymentQty').value) || 1;
  const price   = parseFloat(document.getElementById('paymentAmount').value) || 0;

  if (!paidFor) { alert('Please select an item or service.'); return; }
  if (price <= 0) { alert('Please enter a valid price.'); return; }
  if (qty < 1)   { alert('Quantity must be at least 1.'); return; }

  let displayName = paidFor;
  let invItemId = null, invItemName = null;
  if (paidFor.startsWith('Inventory:')) {
    const parts = paidFor.split(':');
    invItemId   = parts[1];
    invItemName = parts[2];
    displayName = invItemName;
    const sel = document.getElementById('paymentPaidFor');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.text) displayName = opt.text.replace(/\s*\(.*\)/, '').trim();
  }

  const existing = payCart.find(i => i.paidFor === paidFor && i.price === price);
  if (existing) {
    existing.qty += qty;
  } else {
    payCart.push({ id: Date.now(), paidFor, displayName, price, qty, invItemId, invItemName });
  }

  renderCart();
  document.getElementById('paymentPaidFor').value = '';
  document.getElementById('paymentAmount').value  = '';
  document.getElementById('paymentQty').value     = 1;
  document.getElementById('unitSubtotal').textContent = '0.00';
}

function cartChangeQty(id, delta) {
  const item = payCart.find(i => i.id === id);
  if (!item) return;
  item.qty = Math.max(1, item.qty + delta);
  renderCart();
}

function cartRemove(id) {
  payCart = payCart.filter(i => i.id !== id);
  renderCart();
}

function renderCart() {
  const badge   = document.getElementById('cartBadge');
  const empty   = document.getElementById('cartEmpty');
  const wrap    = document.getElementById('cartTableWrap');
  const rows    = document.getElementById('cartRows');
  const grandEl = document.getElementById('cartGrandTotal');

  badge.textContent = payCart.length;
  badge.style.display = payCart.length > 0 ? 'inline-block' : 'none';

  if (payCart.length === 0) {
    empty.style.display = 'block';
    wrap.style.display  = 'none';
    return;
  }
  empty.style.display = 'none';
  wrap.style.display  = 'block';

  let grand = 0;
  rows.innerHTML = payCart.map(item => {
    const sub = item.price * item.qty;
    grand += sub;
    return `<div style="display:grid;grid-template-columns:1fr 90px 90px 90px 34px;gap:8px;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;">
      <div>
        <div style="font-weight:600;color:var(--text-primary);">${escapeHtml(item.displayName)}</div>
        <div style="font-size:11px;color:var(--text-muted);">₱${item.price.toFixed(2)} each</div>
      </div>
      <div style="color:#aaa;">₱${item.price.toFixed(2)}</div>
      <div style="display:flex;align-items:center;gap:4px;">
        <button type="button" onclick="cartChangeQty(${item.id},-1)"
          style="background:var(--bg-surface);border:1px solid var(--border);color:#fff;width:24px;height:24px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;">−</button>
        <span style="min-width:20px;text-align:center;">${item.qty}</span>
        <button type="button" onclick="cartChangeQty(${item.id},1)"
          style="background:var(--bg-surface);border:1px solid var(--border);color:#fff;width:24px;height:24px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;">+</button>
      </div>
      <div style="font-weight:700;color:var(--hazard);">₱${sub.toFixed(2)}</div>
      <button type="button" onclick="cartRemove(${item.id})"
        style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:18px;line-height:1;">×</button>
    </div>`;
  }).join('');

  grandEl.textContent = grand.toFixed(2);
}

function clearPaymentForm() {
  payCart = [];
  renderCart();
  document.getElementById('paymentForm').reset();
  document.getElementById('memberSearchInput').value  = '';
  document.getElementById('paymentMemberID').value    = '';
  document.getElementById('memberClearBtn').style.display = 'none';
  document.getElementById('memberDropdownList').classList.remove('open');
  document.getElementById('unitSubtotal').textContent = '0.00';
  document.getElementById('gcashRefGroup').style.display = 'none';
  document.getElementById('gcashRefNumber').value = '';
  toggleCustomerType('member');
}

function processPayment() {
  if (payCart.length === 0) { alert('Cart is empty! Add at least one item.'); return; }

  const customerType = document.querySelector('input[name="customerType"]:checked').value;
  const method       = document.getElementById('paymentMethod').value;
  const btn          = document.getElementById('paymentSubmitBtn');

  let memberId = null, customerName = null;
  if (customerType === 'member') {
    memberId = document.getElementById('paymentMemberID').value.trim();
    if (!memberId) { alert('Please select a member from the dropdown.'); return; }
  } else {
    customerName = document.getElementById('paymentCustomerName').value.trim();
    if (!customerName) { alert('Please enter customer name!'); return; }
  }
  if (!method) { alert('Please select a payment method.'); return; }

  let gcashRef = '';
  if (method === 'GCash') {
    gcashRef = document.getElementById('gcashRefNumber').value.trim();
    if (!gcashRef) { alert('Please enter the GCash reference number.'); return; }
    if (gcashRef.length < 13) { alert('GCash reference number must be 13 digits.'); return; }
  }

  const hasMonthly = payCart.some(i => i.paidFor === 'Monthly');

  if (hasMonthly) {
    let monthlyName = '';
    if (customerType === 'member' && memberId) {
      const found = allMembers.find(m => String(m.id) === String(memberId));
      monthlyName = found ? ([found.first_name, found.last_name].filter(Boolean).join(' ') || found.username) : '';
    } else {
      monthlyName = customerName || '';
    }

    const fd = new FormData();
    fd.append('action', 'check_monthly_dup');
    fd.append('name', monthlyName);

    btn.disabled = true; btn.textContent = 'Checking...';

    fetch('staff.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.duplicate) {
          btn.disabled = false; btn.textContent = 'Generate Receipt';
          showMonthlyRenewPrompt(data, monthlyName, null, customerType, memberId, customerName);
        } else {
          doSaveTransaction(customerType, memberId, customerName, method, gcashRef, btn);
        }
      })
      .catch(() => {
        btn.disabled = false; btn.textContent = 'Generate Receipt';
        alert('Could not verify monthly subscription. Please try again.');
      });

  } else {
    btn.disabled = true; btn.textContent = 'Saving...';
    doSaveTransaction(customerType, memberId, customerName, method, gcashRef, btn);
  }
}

function doSaveTransaction(customerType, memberId, customerName, method, gcashRef, btn) {
  btn.disabled = true; btn.textContent = 'Saving...';

  const grand = payCart.reduce((s, i) => s + i.price * i.qty, 0);
  const lineItems = payCart.map(i => ({
    paid_for:    i.invItemId ? ('Inventory - ' + i.invItemName) : i.paidFor,
    amount:      i.price * i.qty,
    inv_item_id: i.invItemId || null,
    inv_qty:     i.invItemId ? i.qty : null,
  }));

  fetch('../Database/save_transaction.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      customer_type:  customerType,
      member_ref:     memberId,
      customer_name:  customerName,
      amount:         grand,
      payment_method: method,
      gcash_ref:      gcashRef,
      paid_for:       payCart.map(i => i.displayName).join(', '),
      notes:          payCart.map(i => `${i.displayName} x${i.qty}`).join(' | '),
      line_items:     lineItems,
      inv_item_id:    null,
      inv_qty:        null,
    })
  })
  .then(r => r.json())
  .then(data => {
    btn.disabled = false; btn.textContent = 'Generate Receipt';
    if (!data.success) { alert(data.error || 'Failed to save transaction.'); return; }

    payCart.forEach(item => {
      if (item.invItemId) {
        deductInventoryStock(item.invItemId, item.invItemName, item.qty);
      } else {
        recordEntryFeeToLocalStorage(item.paidFor, customerType, item.price);
        if (item.paidFor === 'Monthly') {
          if (customerType === 'non-member') {
            saveMonthlyRecord(customerType, memberId, customerName, function(newMonthId, resolvedName) {
              if (!newMonthId) return;
              const fd3 = new FormData();
              fd3.append('action',   'record_monthly_walkin_attendance');
              fd3.append('name',     resolvedName);
              fd3.append('month_id', newMonthId);
              fetch('staff.php', { method: 'POST', body: fd3 })
                .then(r => r.json())
                .then(d => {
                  if (d.success && !d.duplicate) {
                    console.log('Monthly walk-in attendance logged with month_id:', newMonthId);
                  }
                })
                .catch(err => console.warn('Monthly walk-in attendance error:', err));
            });
          } else {
            saveMonthlyRecord(customerType, memberId, customerName, null);
          }
        }
        if (item.paidFor === 'Day Pass / Walk-In' && customerType === 'non-member') {
          const walkName = customerName || 'Walk-In Guest';
          const fd2 = new FormData();
          fd2.append('action', 'record_walkin_from_receipt');
          fd2.append('name',   walkName);
          fetch('staff.php', { method: 'POST', body: fd2 })
            .then(r => r.json())
            .then(d => { if (!d.success) console.warn('Walk-in log failed:', d.message); })
            .catch(err => console.warn('Walk-in log error:', err));
        }
      }
    });

    const receiptData = {
      ...data.receipt,
      amount: grand,
      lineItems: payCart.map(i => ({ name: i.displayName, qty: i.qty, sub: i.price * i.qty })),
    };
    displayReceipt(receiptData);
    clearPaymentForm();
  })
  .catch(() => {
    btn.disabled = false; btn.textContent = 'Generate Receipt';
    alert('Unable to save transaction right now.');
  });
}

function deductInventoryStock(invItemId, invItemName, qty) {
  const fd = new FormData();
  fd.append('action', 'update_stock');
  fd.append('id',     invItemId);
  fd.append('change', -qty);
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  if (csrfMeta) fd.append('csrf_token', csrfMeta.content);

  fetch('inventory.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) console.warn('Stock deduction failed:', data.message);
    })
    .catch(err => console.warn('Stock deduction error:', err));

  const TALLY_KEY = 'fitstop_inv_tally';
  const today     = new Date().toISOString().slice(0, 10);
  let tally = {};
  try { tally = JSON.parse(localStorage.getItem(TALLY_KEY) || '{}'); } catch(e) {}
  if (tally._date !== today) { tally = { _date: today }; }
  tally[invItemId] = (tally[invItemId] || 0) + qty;
  localStorage.setItem(TALLY_KEY, JSON.stringify(tally));
}

// FIX: Accept the actual item price so the amount is always correct regardless of customer type.
// Previously the tally used hardcoded 650/750 which was wrong during renewals and
// when the price had been manually changed in the cart.
function recordEntryFeeToLocalStorage(paidFor, customerType, itemPrice) {
  const ENTRY_KEY = 'fitstop_entry_tally';
  const today     = new Date().toISOString().slice(0, 10);
  let tally = {};
  try { tally = JSON.parse(localStorage.getItem(ENTRY_KEY) || '{}'); } catch(e) {}
  if (tally._date !== today) { tally = { _date: today }; }

  if (paidFor === 'Day Pass / Walk-In' && customerType === 'non-member') {
    tally.non_member = (tally.non_member || 0) + 1;
  } else if (paidFor === 'Day Pass / Walk-In' && customerType === 'member') {
    tally.member_walkin = (tally.member_walkin || 0) + 1;
  } else if (paidFor === 'Membership / Renewal' || paidFor === 'Membership') {
    tally.membership = (tally.membership || 0) + 1;
  } else if (paidFor === 'Special Rate') {
    tally.special = (tally.special || 0) + 1;
  } else if (paidFor === 'Monthly') {
    // FIX: Use the actual cart price instead of a hardcoded value.
    // This ensures renewals and walk-in monthly both record the correct amount.
    tally.monthly       = (tally.monthly || 0) + 1;
    tally.monthly_total = (tally.monthly_total || 0) + (parseFloat(itemPrice) || 0);
  }

  localStorage.setItem(ENTRY_KEY, JSON.stringify(tally));
}

function saveMonthlyRecord(customerType, memberId, customerName, onSaved) {
  let name = '';
  let mId  = null;

  if (customerType === 'member' && memberId) {
    const found = allMembers.find(m => String(m.id) === String(memberId));
    if (found) {
      name = [found.first_name, found.last_name].filter(Boolean).join(' ') || found.username || '';
    }
    mId = memberId;
  } else {
    name = customerName || '';
    mId  = null;
  }

  if (!name) {
    console.warn('saveMonthlyRecord: no name resolved, aborting.');
    return;
  }

  const fd = new FormData();
  fd.append('action',    'save_monthly');
  fd.append('member_id', mId || '');
  fd.append('name',      name);

  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        console.log('Monthly record saved. Expires:', data.expires_in, 'ID:', data.insert_id);
        if (typeof onSaved === 'function') {
          onSaved(data.insert_id, name);
        }
      } else if (data.duplicate) {
        console.warn('Monthly duplicate detected after pre-check passed (race condition).');
      } else {
        console.warn('Monthly save failed:', data.message);
      }
    })
    .catch(err => console.warn('Monthly save error:', err));
}

function showMonthlyRenewPrompt(dupData, name, mId, customerType, memberId, customerName) {
  const today     = new Date();
  const expiry    = new Date(dupData.expires_in);
  const msLeft    = expiry - today;
  const daysLeft  = Math.max(0, Math.ceil(msLeft / (1000 * 60 * 60 * 24)));
  const newExpiry = new Date(expiry);
  newExpiry.setDate(newExpiry.getDate() + 30);
  const newExpiryStr = newExpiry.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  const oldExpiryStr = expiry.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

  // Determine the renewal price from the cart (the Monthly item's actual price)
  const monthlyCartItem = payCart.find(i => i.paidFor === 'Monthly');
  const renewalPrice = monthlyCartItem ? monthlyCartItem.price : (customerType === 'non-member' ? 750 : 650);

  const modalHtml = `
    <div id="renewModal" style="
      position:fixed;top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,0.88);z-index:3000;
      display:flex;align-items:center;justify-content:center;
      backdrop-filter:blur(5px);
    ">
      <div style="
        background:#111;border:1px solid #2a2a2a;
        border-top:2px solid #FFCC00;
        padding:36px;max-width:440px;width:90%;
        font-family:'DM Sans',sans-serif;color:#fff;
        box-shadow:0 0 40px rgba(0,0,0,0.6);
      ">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
          <div style="
            width:42px;height:42px;border-radius:50%;
            background:rgba(255,204,0,0.1);border:1px solid rgba(255,204,0,0.3);
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
          ">
            <i class="bi bi-arrow-repeat" style="color:#FFCC00;font-size:18px;"></i>
          </div>
          <div>
            <h3 style="margin:0;font-family:'Chakra Petch',sans-serif;
              font-size:16px;letter-spacing:1px;text-transform:uppercase;color:#FFCC00;">
              Active Subscription Found
            </h3>
            <p style="margin:3px 0 0;font-size:12px;color:#666;">Advance renewal available</p>
          </div>
        </div>

        <div style="
          background:#0d0d0d;border:1px solid #222;
          padding:16px;margin-bottom:20px;
        ">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.8px;">Subscriber</span>
            <span style="font-weight:700;font-size:14px;">${escapeHtml(name)}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.8px;">Current Expiry</span>
            <span style="color:#ff9f43;font-weight:600;font-size:13px;">${oldExpiryStr}</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <span style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.8px;">Days Remaining</span>
            <span style="
              background:rgba(255,159,67,0.12);color:#ff9f43;
              font-weight:700;font-size:13px;padding:3px 10px;
              border:1px solid rgba(255,159,67,0.3);font-family:'Chakra Petch',sans-serif;
            ">${daysLeft} day${daysLeft !== 1 ? 's' : ''}</span>
          </div>
          <div style="border-top:1px dashed #222;padding-top:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <span style="font-size:12px;color:#666;text-transform:uppercase;letter-spacing:.8px;">New Expiry After Renewal</span>
              <span style="color:#22d07a;font-weight:700;font-size:14px;font-family:'Chakra Petch',sans-serif;">${newExpiryStr}</span>
            </div>
            <div style="margin-top:8px;font-size:11px;color:#555;text-align:right;">
              ${daysLeft} remaining + 30 days = <strong style="color:#22d07a;">${daysLeft + 30} total days</strong>
            </div>
          </div>
        </div>

        <div style="
          background:rgba(34,208,122,0.05);border:1px solid rgba(34,208,122,0.15);
          padding:12px;margin-bottom:22px;display:flex;gap:10px;align-items:flex-start;
        ">
          <i class="bi bi-info-circle-fill" style="color:#22d07a;margin-top:2px;flex-shrink:0;"></i>
          <p style="margin:0;font-size:12px;color:#aaa;line-height:1.6;">
            Renewal stacks on top of the current subscription.
            The new expiry date will be extended by <strong style="color:#fff;">30 days</strong>
            from the current expiry — not from today.
          </p>
        </div>

        <div style="display:flex;gap:10px;">
          <button onclick="closeRenewModal()" style="
            flex:1;padding:13px;background:transparent;
            border:1px solid #333;color:#888;
            font-family:'Chakra Petch',sans-serif;font-size:12px;
            text-transform:uppercase;letter-spacing:.5px;cursor:pointer;
            transition:all .2s;
          " onmouseover="this.style.borderColor='#555';this.style.color='#fff'"
             onmouseout="this.style.borderColor='#333';this.style.color='#888'">
            Cancel
          </button>
          <button onclick="confirmRenewal('${escapeHtml(dupData.monthly_id || '')}', '${escapeHtml(name)}', '${escapeHtml(customerType)}', '${escapeHtml(memberId || '')}', '${escapeHtml(customerName || '')}', ${renewalPrice})" style="
            flex:2;padding:13px;background:#FFCC00;border:none;
            color:#000;font-family:'Chakra Petch',sans-serif;font-size:12px;
            font-weight:700;text-transform:uppercase;letter-spacing:1px;
            cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
            transition:background .2s;
          " onmouseover="this.style.background='#e6b800'"
             onmouseout="this.style.background='#FFCC00'">
            <i class="bi bi-arrow-repeat"></i> Renew +30 Days
          </button>
        </div>
      </div>
    </div>`;

  document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeRenewModal() {
  const m = document.getElementById('renewModal');
  if (m) m.remove();
}

// FIX: Added renewalPrice parameter so the tally records the correct amount.
// Removed the duplicate manual localStorage tally block that was here before —
// doSaveTransaction → recordEntryFeeToLocalStorage now handles it correctly,
// preventing the daily counter from being incremented twice.
function confirmRenewal(monthlyId, name, customerType, memberId, customerName, renewalPrice) {
  if (!monthlyId) {
    alert('Unable to renew: subscription ID missing. Please contact admin.');
    closeRenewModal();
    return;
  }

  const btn = document.querySelector('#renewModal button:last-child');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Renewing...'; }

  const fd = new FormData();
  fd.append('action',     'renew_monthly');
  fd.append('monthly_id', monthlyId);

  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      closeRenewModal();
      if (data.success) {
        const expStr = new Date(data.new_expiry).toLocaleDateString('en-US',
          { month: 'long', day: 'numeric', year: 'numeric' });
        showRenewSuccessToast(name, expStr, data.days_left);

        // Save transaction — this calls recordEntryFeeToLocalStorage internally
        // with the correct customerType and itemPrice (renewalPrice), so the
        // daily counter gets +1 and the amount is accurate (650 member / 750 walk-in).
        const method   = document.getElementById('paymentMethod').value;
        const gcashRef = document.getElementById('gcashRefNumber').value.trim();
        const payBtn   = document.getElementById('paymentSubmitBtn');
        doSaveTransaction(customerType, memberId, customerName, method, gcashRef, payBtn);

        // NOTE: No manual tally update here — doSaveTransaction handles it via
        // recordEntryFeeToLocalStorage using the cart item's actual price.

      } else {
        alert('Renewal failed: ' + (data.message || 'Unknown error.'));
      }
    })
    .catch(() => {
      closeRenewModal();
      alert('Unable to process renewal right now. Please try again.');
    });
}

function showRenewSuccessToast(name, newExpiry, daysWereLeft) {
  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed;bottom:28px;right:28px;z-index:4000;
    background:#111;border:1px solid #2a2a2a;border-left:3px solid #22d07a;
    padding:18px 22px;max-width:320px;
    font-family:'DM Sans',sans-serif;
    box-shadow:0 8px 32px rgba(0,0,0,0.5);
    animation:slideInToast .25s ease;
  `;
  toast.innerHTML = `
    <div style="display:flex;gap:12px;align-items:flex-start;">
      <i class="bi bi-check-circle-fill" style="color:#22d07a;font-size:20px;margin-top:1px;flex-shrink:0;"></i>
      <div>
        <p style="margin:0 0 4px;font-weight:700;color:#fff;font-size:14px;">Renewal Confirmed</p>
        <p style="margin:0;font-size:12px;color:#888;line-height:1.5;">
          <strong style="color:#ddd;">${escapeHtml(name)}</strong>'s subscription
          has been extended.<br>
          <span style="color:#22d07a;font-weight:600;">New expiry: ${escapeHtml(newExpiry)}</span>
        </p>
      </div>
      <button onclick="this.parentElement.parentElement.remove()"
        style="background:none;border:none;color:#555;font-size:18px;cursor:pointer;
               margin-left:auto;flex-shrink:0;line-height:1;padding:0;">×</button>
    </div>`;

  if (!document.getElementById('toastKf')) {
    const style = document.createElement('style');
    style.id = 'toastKf';
    style.textContent = `@keyframes slideInToast {
      from { transform: translateX(40px); opacity: 0; }
      to   { transform: translateX(0);   opacity: 1; }
    }`;
    document.head.appendChild(style);
  }

  document.body.appendChild(toast);
  setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5500);
}

function displayReceipt(receipt) {
  const content = document.getElementById('receiptContent');
  const custInfo = receipt.customerType === 'member'
    ? `<p style="margin:5px 0;"><strong>Member ID:</strong> ${receipt.memberId}</p>`
    : `<p style="margin:5px 0;"><strong>Customer:</strong> ${receipt.customerName}</p>`;

  const itemsHtml = (receipt.lineItems && receipt.lineItems.length > 0)
    ? `<div style="margin:10px 0;padding:10px;background:#111;border:1px solid #2a2a2a;">
        <div style="font-size:10.5px;color:#666;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;">Items</div>
        ${receipt.lineItems.map(li =>
          `<div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #1e1e1e;font-size:13px;">
            <span>${escapeHtml(li.name)} <span style="color:#555;">x${li.qty}</span></span>
            <span style="color:#FFCC00;">₱${li.sub.toFixed(2)}</span>
          </div>`
        ).join('')}
      </div>`
    : '';

  content.innerHTML = `
    <div style="margin-bottom:14px;">
      <p style="margin:5px 0;color:#999;font-size:12px;"><strong style="color:#fff;">Receipt #:</strong> ${receipt.receiptNumber}</p>
      <p style="margin:5px 0;color:#999;font-size:12px;"><strong style="color:#fff;">Date:</strong> ${receipt.date} &nbsp; <strong style="color:#fff;">Time:</strong> ${receipt.time}</p>
    </div>
    <div style="padding:14px;background:#111;border:1px solid #2a2a2a;margin:14px 0;">
      ${custInfo}
      <p style="margin:5px 0;"><strong>Type:</strong> ${receipt.customerType === 'member' ? 'Member' : 'Walk-In'}</p>
      <p style="margin:5px 0;"><strong>Payment:</strong> ${receipt.method}</p>
      <p style="margin:5px 0;"><strong>Status:</strong> <span style="color:#22d07a;">&#10003; ${receipt.status}</span></p>
    </div>
    ${itemsHtml}
    <div style="border-top:1px dashed #333;padding-top:14px;">
      <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:700;">
        <span>TOTAL:</span><span style="color:#FFCC00;">&#8369;${receipt.amount.toFixed(2)}</span>
      </div>
    </div>`;

  window.currentReceipt = receipt;
  document.getElementById('receiptModal').style.display = 'flex';
}

function closeReceipt() {
  document.getElementById('receiptModal').style.display = 'none';
}

function printReceipt() {
  const r        = window.currentReceipt;
  const custInfo = r.customerType === 'member' ? `Member ID: ${r.memberId}` : `Customer: ${r.customerName}`;
  const pw = window.open('','','height=500,width=700');
  pw.document.write(`<html><head><title>Receipt</title><style>body{font-family:Arial,sans-serif;padding:40px;}h2{margin:0;}.header{text-align:center;margin-bottom:30px;border-bottom:2px dashed #000;padding-bottom:20px;}.row{display:flex;justify-content:space-between;padding:8px 0;}.total{border-top:2px dashed #000;padding-top:20px;display:flex;justify-content:space-between;font-size:18px;font-weight:bold;}.footer{text-align:center;margin-top:30px;font-size:12px;color:#666;}</style></head><body><div class="header"><h2>FIT-STOP GYM</h2><p>Official Receipt</p></div><div><div class="row"><span>Receipt #:</span><span>${r.receiptNumber}</span></div><div class="row"><span>Date/Time:</span><span>${r.date} ${r.time}</span></div><div class="row"><span>${custInfo}</span></div><div class="row"><span>Paid For:</span><span>${r.paidFor||'-'}</span></div><div class="row"><span>Payment:</span><span>${r.method}</span></div>${r.notes?`<div class="row"><span>Notes:</span><span>${r.notes}</span></div>`:''}</div><div class="total"><span>TOTAL:</span><span>&#8369;${r.amount.toFixed(2)}</span></div><div class="footer"><p>Thank you!</p></div></body></html>`);
  pw.document.close();
  setTimeout(() => pw.print(), 100);
}

function resolveMemberRefFromQr(qrCodeMessage) {
  const raw = String(qrCodeMessage||'').trim();
  if (!raw) return '';
  if (/^FS-\d{4}-\d+$/i.test(raw)) return raw;
  let decoded = raw;
  try { decoded = decodeURIComponent(raw); } catch(e) {}
  if (decoded.startsWith('{') && decoded.endsWith('}')) {
    try { const p = JSON.parse(decoded); return String(p.member_ref||p.member_id||p.user_id||p.id||'').trim(); } catch(e) { return ''; }
  }
  if (/^https?:\/\//i.test(decoded)) {
    try { const url = new URL(decoded); return (url.searchParams.get('member_ref')||url.searchParams.get('member_id')||'').trim(); } catch(e) {}
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
        o.value = m.member_ref; o.textContent = m.display_name;
        select.appendChild(o);
      });
    }).catch(()=>{});
}

function startScanner() {
  if (scannerRunning) return;
  document.getElementById('reader').innerHTML = '';
  if (!activeQrScanner) activeQrScanner = new Html5Qrcode('reader');
  activeQrScanner.start(
    { facingMode: 'environment' }, { fps: 10, qrbox: 250 },
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
    document.getElementById('stopScannerBtn').style.display = 'inline-block';
  }).catch(err => { scannerRunning = false; alert('Camera Error: ' + err); });
}

function stopScanner() {
  const finalize = () => {
    scannerRunning = false;
    document.getElementById('stopScannerBtn').style.display = 'none';
    document.getElementById('reader').innerHTML = '';
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

let exerciseNameToId   = {};
let exerciseNameToType = {};

function loadExerciseOptions() {
  fetch('../Database/get_exercises.php')
    .then(r => r.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.exercises)) return;
      const datalist = document.getElementById('exerciseOptions');
      exerciseNameToId = {}; exerciseNameToType = {};
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
  label.textContent = mt === 'cardio' ? 'Minutes' : 'Weight (kg)';
  input.placeholder = mt === 'cardio' ? 'Enter minutes' : 'Enter weight';
}

function logWorkout() {
  const id = document.getElementById('perfID').value.trim();
  const exercise = document.getElementById('exercise').value.trim();
  const metricValue = document.getElementById('performanceMetric').value.trim();
  const reps = document.getElementById('reps').value.trim();
  if (!id||!exercise||!metricValue||!reps) { alert('Please provide all fields.'); return; }
  const exerciseId = exerciseNameToId[exercise.toLowerCase()];
  if (!exerciseId) { alert('Please choose a valid exercise from the dropdown list.'); return; }
  const metricNum = parseFloat(metricValue);
  if (isNaN(metricNum)||metricNum<0) { alert('Please enter a valid value.'); return; }
  fetch('../Database/save_workout_log.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ member_ref: id, exercise_id: exerciseId, reps: parseInt(reps,10), weight: metricNum })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { alert(data.error||'Failed to save workout log.'); return; }
    const logs  = document.getElementById('workoutLogs');
    const entry = document.createElement('div');
    entry.classList.add('attendance-item');
    const ms = (exerciseNameToType[exercise.toLowerCase()]||'')==='cardio' ? 'min' : 'kg';
    entry.innerHTML = `<strong style="color:var(--text-primary);font-family:'Chakra Petch',sans-serif;">${id}</strong> <span style="color:var(--text-muted);font-size:13px;margin-left:10px;">${exercise} · ${metricNum} ${ms} · ${reps} reps</span>`;
    logs.prepend(entry);
  })
  .catch(() => alert('Unable to save workout log right now.'));
}

document.addEventListener('DOMContentLoaded', function() {
  loadExerciseOptions();
  loadAttendanceMembers();
  loadMembersForPayment();
  loadRealtimeAttendance();
  refreshUnreadCount();
  setInterval(() => {
    loadRealtimeAttendance();
    refreshUnreadCount();
  }, 15000);
  const ex = document.getElementById('exercise');
  if (ex) { ex.addEventListener('input', updatePerformanceMetricField); ex.addEventListener('change', updatePerformanceMetricField); }
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
      if (!data.success) { list.innerHTML = `<div style="padding:16px;color:var(--danger);font-size:12px;">Error: ${escapeHtml(data.error||'Unknown error')}</div>`; return; }
      if (data.stats) {
        document.getElementById('stat-checked-in').textContent    = data.stats.members_checked_in;
        document.getElementById('stat-registrations').textContent = data.stats.new_registrations;
      }
      const records = Array.isArray(data.records) ? data.records : [];
      list.innerHTML = records.length === 0
        ? '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">No check-ins today yet.</div>'
        : records.map(rec => {
            const name = rec.display_name || ('User #' + rec.user_id);
            return `<div class="attendance-item">
              <div class="member-info">
                <div class="member-avatar">${escapeHtml(name.substring(0,2).toUpperCase())}</div>
                <div><strong>${escapeHtml(name)}</strong><span class="time">${escapeHtml(timeAgo(rec.datetime))}</span></div>
              </div>
              <span class="check-in-badge">Check-In</span>
            </div>`;
          }).join('');
      if (Array.isArray(data.weekly)) {
        const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        const maxVal = Math.max(...data.weekly, 1);
        const todayIdx = new Date().getDay() === 0 ? 6 : new Date().getDay() - 1;
        days.forEach((day, i) => {
          const bar = document.getElementById('bar-' + day);
          if (!bar) return;
          bar.style.height = Math.round((data.weekly[i]/maxVal)*100) + '%';
          bar.className    = 'chart-bar' + (i === todayIdx ? ' active' : '');
        });
      }
    })
    .catch(() => {
      const list = document.getElementById('realtimeAttendanceList');
      if (list) list.innerHTML = `<div style="padding:16px;color:var(--danger);font-size:12px;">Failed to reach server.</div>`;
    });
}
</script>

<script>
let notifPanelOpen = false;
let notifLoaded    = false;

function toggleNotifPanel() {
  notifPanelOpen = !notifPanelOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifPanelOpen);
  if (notifPanelOpen) {
    localStorage.setItem('notif_last_read', Date.now().toString());
    const badge = document.getElementById('notifBadge');
    badge.textContent = '0';
    badge.classList.add('hidden');
    const statEl = document.getElementById('stat-notifications');
    if (statEl) statEl.textContent = '0';
    if (!notifLoaded) loadNotificationHistory();
  }
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
  const d = new Date(ts.replace(' ','T'));
  if (isNaN(d)) return ts;
  return d.toLocaleString('en-PH', { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true });
}

function loadNotificationHistory() {
  const list  = document.getElementById('notifList');
  const count = document.getElementById('notifPanelCount');
  list.innerHTML = '<div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>';
  const fd = new FormData();
  fd.append('action', 'get_notifications');
  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      notifLoaded = true;
      if (!data.success) { list.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load history.</div>'; return; }
      const transactions = Array.isArray(data.transactions) ? data.transactions : [];
      const lowStock     = Array.isArray(data.low_stock)    ? data.low_stock    : [];
      const total        = transactions.length + lowStock.length;
      if (count) count.textContent = total + ' record' + (total !== 1 ? 's' : '');
      if (total === 0) { list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>No notifications yet.</div>'; return; }
      const txHtml = transactions.map(t => {
        const who    = t.customer_name || (t.customer_type === 'member' ? 'Member #' + t.user_id : 'Walk-In');
        const amount = parseFloat(t.amount||0).toFixed(2);
        const ts     = formatTs(t.transaction_date||t.created_at);
        return `<div class="notif-item">
          <div class="notif-icon" style="background:rgba(34,208,122,0.12);border-color:rgba(34,208,122,0.3);color:var(--success);"><i class="bi bi-receipt"></i></div>
          <div class="notif-body">
            <p class="notif-msg"><strong>${escapeHtml(t.receipt_number||'Receipt')}</strong><br>
            <span class="item-highlight">&#8369;${escapeHtml(amount)}</span>
            · ${escapeHtml(who)} · ${escapeHtml(t.payment_method||'—')}
            · <span style="text-transform:capitalize;">${escapeHtml(t.status||'paid')}</span></p>
            <span class="notif-time"><i class="bi bi-clock"></i> ${ts}</span>
          </div>
        </div>`;
      }).join('');
      const stockHtml = lowStock.map(i => {
        const qty = parseInt(i.quantity);
        const danger = qty === 0;
        return `<div class="notif-item">
          <div class="notif-icon" style="background:${danger?'rgba(255,71,87,0.12)':'rgba(255,159,67,0.12)'};border-color:${danger?'rgba(255,71,87,0.3)':'rgba(255,159,67,0.3)'};color:${danger?'var(--danger)':'var(--warning)'};">
            <i class="bi ${danger?'bi-x-circle-fill':'bi-exclamation-triangle-fill'}"></i>
          </div>
          <div class="notif-body">
            <p class="notif-msg"><strong>${escapeHtml(i.item_name)}</strong> — ${escapeHtml(i.category)}<br>
            Stock: <span class="item-highlight">${qty} unit${qty!==1?'s':''}</span>
            ${danger?'— <span style="color:var(--danger);font-weight:700;">OUT OF STOCK</span>':'— Low Stock'}</p>
            <span class="notif-time"><i class="bi bi-clock"></i> Updated: ${formatTs(i.updated_at)}</span>
          </div>
        </div>`;
      }).join('');
      list.innerHTML = txHtml + (lowStock.length ? `<div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">Inventory Alerts</div>` + stockHtml : '');
    })
    .catch(() => { list.innerHTML = '<div class="notif-empty"><i class="bi bi-wifi-off"></i>Unable to load notifications.</div>'; });
}

function refreshUnreadCount() {
  const fd = new FormData();
  fd.append('action', 'get_notifications');
  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const transactions = Array.isArray(data.transactions) ? data.transactions : [];
      const lowStock     = Array.isArray(data.low_stock)    ? data.low_stock    : [];
      const lastRead     = parseInt(localStorage.getItem('notif_last_read') || '0');

      const unread = [
        ...transactions.map(t => new Date((t.transaction_date || t.created_at).replace(' ', 'T') + 'Z').getTime()),
        ...lowStock.map(i => new Date((i.updated_at).replace(' ', 'T') + 'Z').getTime())
      ].filter(ts => ts > lastRead).length;

      const badge = document.getElementById('notifBadge');
      if (unread > 0) { badge.textContent = unread > 99 ? '99+' : unread; badge.classList.remove('hidden'); }
      else badge.classList.add('hidden');

      const statEl = document.getElementById('stat-notifications');
      if (statEl) statEl.textContent = unread;
    }).catch(()=>{});
}

document.addEventListener('DOMContentLoaded', refreshUnreadCount);
</script>

<script>
function generateMemberID() {
  const memberRef = document.getElementById('idGenMemberRef').value.trim();
  if (!memberRef) { alert('Please enter a Member ID or reference.'); return; }
  const resultDiv = document.getElementById('idGenResult');
  resultDiv.innerHTML = '<p style="color:var(--text-muted);font-size:13px;">Generating ID card...</p>';
  fetch('../Database/generate_member_id.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ member_ref: memberRef })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.success) { resultDiv.innerHTML = `<p style="color:var(--danger);font-size:13px;">${escapeHtml(data.error||'Failed to generate ID.')}</p>`; return; }
    resultDiv.innerHTML = `
      <div style="border:1px solid var(--border-accent);padding:20px;display:inline-block;background:var(--bg-surface);text-align:center;">
        <p style="font-family:'Chakra Petch',sans-serif;color:var(--hazard);font-size:13px;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Member ID Card Generated</p>
        ${data.qr_image?`<img src="${data.qr_image}" alt="QR Code" style="width:160px;height:160px;display:block;margin:0 auto 12px;border:2px solid var(--hazard);">` : ''}
        <p style="color:var(--text-primary);font-weight:700;font-family:'Chakra Petch',sans-serif;font-size:15px;letter-spacing:1px;">${escapeHtml(data.member_id_display||memberRef)}</p>
        ${data.full_name?`<p style="color:var(--text-muted);font-size:12px;margin-top:4px;">${escapeHtml(data.full_name)}</p>`:''}
        ${data.download_url?`<a href="${data.download_url}" download class="action-btn" style="display:inline-block;margin-top:14px;text-decoration:none;"><i class="bi bi-download" style="margin-right:5px;"></i>Download</a>`:''}
      </div>`;
  })
  .catch(() => { resultDiv.innerHTML = '<p style="color:var(--danger);font-size:13px;">Unable to generate ID right now.</p>'; });
}
</script>

<script>
function loadActiveMembers() {
  const grid = document.getElementById('membersGrid');
  if (!grid) return;

  const fd = new FormData();
  fd.append('action', 'get_active_members');

  fetch('staff.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.members)) {
        grid.innerHTML = '<div style="padding:24px;color:var(--danger);font-size:13px;">Failed to load members.</div>';
        return;
      }
      if (data.members.length === 0) {
        grid.innerHTML = '<div style="padding:24px;color:var(--text-muted);font-size:13px;">No active members found.</div>';
        return;
      }
      grid.innerHTML = data.members.map(m => {
        const fullName = [m.first_name, m.last_name].filter(Boolean).join(' ') || m.username;
        const initials = fullName.substring(0, 2).toUpperCase();
        const points   = parseInt(m.points || 0);
        const joined   = m.created_at
          ? new Date(m.created_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
          : '—';
        const lastSeen = m.last_attendance ? timeAgo(m.last_attendance) : 'No visits yet';

        const avatarHtml = m.profile_picture
          ? `<img src="${escapeHtml(m.profile_picture)}" alt="${escapeHtml(fullName)}" class="member-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
             <div class="member-img" style="display:none;align-items:center;justify-content:center;background:var(--bg-surface);color:var(--hazard);font-family:'Chakra Petch',sans-serif;font-weight:700;font-size:20px;">${initials}</div>`
          : `<div class="member-img" style="display:flex;align-items:center;justify-content:center;background:var(--bg-surface);color:var(--hazard);font-family:'Chakra Petch',sans-serif;font-weight:700;font-size:20px;">${initials}</div>`;

        return `
          <div class="member-card">
            ${avatarHtml}
            <h4>${escapeHtml(fullName)}</h4>
            <p class="member-id">@${escapeHtml(m.username || '—')}</p>
            <div class="member-details">
              <span><i class="bi bi-star-fill" style="color:var(--hazard);"></i> ${points} pts</span>
            </div>
            <div class="member-stats">
              <div class="stat-item"><span class="label">Joined</span><span class="value" style="font-size:11px;">${joined}</span></div>
              <div class="stat-item"><span class="label">Last Seen</span><span class="value" style="font-size:11px;">${lastSeen}</span></div>
            </div>
            <button class="view-btn" onclick="alert('Member: ${escapeHtml(fullName)}\\nEmail: ${escapeHtml(m.email || '—')}\\nPoints: ${points}')">View Profile</button>
          </div>`;
      }).join('');
    })
    .catch(() => {
      grid.innerHTML = '<div style="padding:24px;color:var(--danger);font-size:13px;">Unable to reach server.</div>';
    });
}

document.addEventListener('DOMContentLoaded', function() {
  loadActiveMembers();
});
</script>

</body>
</html>