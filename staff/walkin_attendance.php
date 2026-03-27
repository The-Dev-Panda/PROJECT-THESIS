<?php
session_start();
require_once '../login/connection.php';
require_once '../includes/security.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {

        // ── Search active monthly subscribers ──────────────────────────────
        if ($action === 'search_monthly') {
            $q = '%' . trim($_POST['query'] ?? '') . '%';
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("
                SELECT id, name, expires_in, member
                FROM monthly
                WHERE name LIKE :q AND expires_in >= :today
                ORDER BY name ASC
                LIMIT 20
            ");
            $stmt->execute([':q' => $q, ':today' => $today]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'results' => $rows]);
            exit;
        }

        // ── Record monthly non-member attendance ───────────────────────────
        if ($action === 'record_monthly_attendance') {
            $monthId = (int)($_POST['month_id'] ?? 0);
            $name    = trim($_POST['name'] ?? '');

            if (!$monthId || !$name) {
                echo json_encode(['success' => false, 'message' => 'Missing data.']);
                exit;
            }

            $today = date('Y-m-d');
            $check = $pdo->prepare("SELECT id, name, expires_in FROM monthly WHERE id = :id AND expires_in >= :today LIMIT 1");
            $check->execute([':id' => $monthId, ':today' => $today]);
            $monthly = $check->fetch(PDO::FETCH_ASSOC);

            if (!$monthly) {
                echo json_encode(['success' => false, 'message' => 'Monthly subscription not found or already expired.']);
                exit;
            }

            $todayCheck = $pdo->prepare("
                SELECT id FROM walk_attendance
                WHERE month_id = :mid AND DATE(datetime) = :today
                LIMIT 1
            ");
            $todayCheck->execute([':mid' => $monthId, ':today' => $today]);
            if ($todayCheck->fetch()) {
                echo json_encode([
                    'success'   => false,
                    'duplicate' => true,
                    'message'   => $name . ' has already checked in today.',
                ]);
                exit;
            }

            $now = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("INSERT INTO walk_attendance (name, month_id, datetime) VALUES (:name, :mid, :datetime)");
            $ins->execute([':name' => $name, ':mid' => $monthId, ':datetime' => $now]);

            echo json_encode([
                'success'    => true,
                'message'    => 'Attendance recorded for ' . $name . '.',
                'expires_in' => $monthly['expires_in'],
                'insert_id'  => $pdo->lastInsertId(),
            ]);
            exit;
        }

        // ── Record walk-in (non-member, per session) ───────────────────────
        if ($action === 'record_walkin') {
            $name = trim($_POST['name'] ?? '');
            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Name is required.']);
                exit;
            }

            $now = date('Y-m-d H:i:s');
            $ins = $pdo->prepare("INSERT INTO walk_attendance (name, month_id, datetime) VALUES (:name, NULL, :datetime)");
            $ins->execute([':name' => $name, ':datetime' => $now]);

            echo json_encode([
                'success'   => true,
                'message'   => 'Walk-in attendance recorded for ' . $name . '.',
                'insert_id' => $pdo->lastInsertId(),
            ]);
            exit;
        }

        // ── Get today's attendance log ─────────────────────────────────────
        if ($action === 'get_today_log') {
            $today = date('Y-m-d');
            $rows = $pdo->query("
                SELECT wa.id, wa.name, wa.month_id, wa.datetime,
                       m.expires_in,
                       CASE WHEN wa.month_id IS NULL OR wa.month_id = 0
                            THEN 'walkin'
                            ELSE 'monthly'
                       END AS attendance_type
                FROM walk_attendance wa
                LEFT JOIN monthly m ON m.id = wa.month_id
                WHERE DATE(wa.datetime) = '$today'
                ORDER BY wa.datetime DESC
                LIMIT 100
            ")->fetchAll(PDO::FETCH_ASSOC);

            $monthly_count = 0;
            $walkin_count  = 0;
            foreach ($rows as $r) {
                if ($r['attendance_type'] === 'monthly') $monthly_count++;
                else $walkin_count++;
            }

            echo json_encode([
                'success'       => true,
                'records'       => $rows,
                'monthly_count' => $monthly_count,
                'walkin_count'  => $walkin_count,
                'total'         => count($rows),
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
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
  <title>Walk-In Attendance — Fit-Stop Gym</title>
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* ── Page-specific styles not in staff.css ── */

    .stats-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 28px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      padding: 20px 22px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: relative;
      overflow: hidden;
      transition: border-color 0.2s, transform 0.2s;
    }

    .stat-card::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0;
      width: 0; height: 2px;
      background: var(--hazard);
      transition: width 0.3s ease;
    }

    .stat-card:hover { border-color: var(--border-accent); transform: translateY(-2px); }
    .stat-card:hover::after { width: 100%; }

    .stat-card .stat-icon {
      width: 54px; height: 54px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }

    .stat-card .stat-icon.yellow { background: rgba(255,204,0,0.1); color: var(--hazard); }
    .stat-card .stat-icon.green  { background: rgba(34,208,122,0.1); color: var(--success); }
    .stat-card .stat-icon.blue   { background: rgba(74,144,226,0.1); color: #4a90e2; }

    .stat-card .stat-value {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 36px;
      font-weight: 700;
      line-height: 1;
      color: var(--text-primary);
    }

    .stat-card .stat-label {
      font-size: 11px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-weight: 600;
      margin-top: 6px;
    }

    /* ── Two-column panel grid ── */
    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 28px;
    }

    .panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
    }

    .panel-header {
      padding: 16px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(0,0,0,0.3);
    }

    .panel-icon {
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px;
      flex-shrink: 0;
    }

    .panel-icon.yellow { background: rgba(255,204,0,0.1); color: var(--hazard); border: 1px solid rgba(255,204,0,0.2); }
    .panel-icon.green  { background: rgba(34,208,122,0.1); color: var(--success); border: 1px solid rgba(34,208,122,0.2); }

    .panel-title {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--text-primary);
    }

    .panel-sub { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

    .panel-body { padding: 22px; }

    /* ── Search dropdown ── */
    .search-wrap { position: relative; }

    .search-dropdown {
      display: none;
      position: absolute;
      top: 100%; left: 0; right: 0;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-top: none;
      max-height: 220px;
      overflow-y: auto;
      z-index: 500;
      box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    }

    .search-dropdown.open { display: block; }

    .search-item {
      padding: 11px 14px;
      cursor: pointer;
      font-size: 13px;
      color: var(--text-primary);
      border-bottom: 1px solid rgba(255,255,255,0.04);
      display: flex;
      flex-direction: column;
      gap: 3px;
      transition: background 0.12s;
    }

    .search-item:hover { background: var(--hazard-dim); }
    .search-item .item-name { font-weight: 600; font-family: 'Chakra Petch', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    .search-item .item-meta { font-size: 11px; color: var(--text-muted); }

    .badge-expiry {
      font-size: 10px;
      font-family: 'Chakra Petch', sans-serif;
      color: var(--success);
      border: 1px solid rgba(34,208,122,0.3);
      padding: 1px 7px;
      display: inline-block;
      margin-top: 2px;
      background: rgba(34,208,122,0.06);
    }

    .search-empty {
      padding: 16px 14px;
      font-size: 12px;
      color: var(--text-muted);
      text-align: center;
      font-family: 'Chakra Petch', sans-serif;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* ── Selected subscriber pill ── */
    .selected-pill {
      display: none;
      align-items: center;
      gap: 12px;
      padding: 12px 14px;
      background: rgba(34,208,122,0.05);
      border: 1px solid rgba(34,208,122,0.2);
      border-left: 3px solid var(--success);
      margin-top: 10px;
    }

    .selected-pill.show { display: flex; }

    .pill-avatar {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: rgba(255,204,0,0.1);
      border: 1px solid rgba(255,204,0,0.25);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      font-size: 13px;
      color: var(--hazard);
      flex-shrink: 0;
    }

    .pill-info { flex: 1; }
    .pill-name { font-weight: 700; font-size: 14px; color: var(--text-primary); font-family: 'Chakra Petch', sans-serif; text-transform: uppercase; letter-spacing: 0.5px; }
    .pill-meta { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

    .pill-clear {
      background: none; border: none;
      color: #555; font-size: 20px;
      cursor: pointer; line-height: 1; padding: 0;
      transition: color 0.15s;
    }
    .pill-clear:hover { color: var(--danger); }

    /* ── Panel action buttons ── */
    .panel-btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      background: var(--hazard);
      color: #000;
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border: none;
      cursor: pointer;
      transition: background 0.15s, box-shadow 0.15s;
      width: 100%;
      justify-content: center;
    }

    .panel-btn-primary:hover { background: #e6b800; box-shadow: 0 0 18px var(--hazard-glow); }
    .panel-btn-primary:disabled { background: #2a2a2a; color: #555; cursor: not-allowed; box-shadow: none; }

    .panel-btn-primary.green-btn { background: var(--success); }
    .panel-btn-primary.green-btn:hover { background: #1ab866; box-shadow: 0 0 18px rgba(34,208,122,0.3); }

    .panel-btn-secondary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 20px;
      background: transparent;
      color: var(--text-muted);
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: all 0.15s;
      width: 100%;
      justify-content: center;
      margin-top: 8px;
    }

    .panel-btn-secondary:hover { border-color: rgba(255,255,255,0.2); color: var(--text-primary); }

    /* ── Info box ── */
    .info-box {
      margin-bottom: 18px;
      padding: 14px;
      background: rgba(74,144,226,0.05);
      border: 1px solid rgba(74,144,226,0.15);
      border-left: 3px solid #4a90e2;
      display: flex;
      gap: 10px;
      align-items: flex-start;
    }

    .info-box i { color: #4a90e2; margin-top: 1px; flex-shrink: 0; }
    .info-box p { font-size: 12px; color: #888; line-height: 1.6; margin: 0; }
    .info-box p strong { color: var(--text-primary); }

    /* ── Log section ── */
    .log-section {
      background: var(--bg-card);
      border: 1px solid var(--border);
    }

    .log-header {
      padding: 16px 22px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(0,0,0,0.3);
    }

    .log-title {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .live-dot {
      width: 7px; height: 7px;
      border-radius: 50%;
      background: var(--success);
      display: inline-block;
      box-shadow: 0 0 6px var(--success);
      animation: pulse 1.5s infinite;
    }

    .log-filter { display: flex; gap: 6px; }

    .filter-btn {
      padding: 5px 14px;
      font-size: 10.5px;
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text-muted);
      cursor: pointer;
      transition: all 0.15s;
    }

    .filter-btn.active { border-color: var(--hazard); color: var(--hazard); background: var(--hazard-dim); }
    .filter-btn:hover  { border-color: rgba(255,255,255,0.2); color: var(--text-primary); }

    .log-list { max-height: 480px; overflow-y: auto; }
    .log-list::-webkit-scrollbar { width: 3px; }
    .log-list::-webkit-scrollbar-thumb { background: var(--hazard); }

    .log-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 13px 22px;
      border-bottom: 1px solid var(--border);
      transition: background 0.12s;
    }

    .log-item:hover { background: rgba(255,204,0,0.02); }
    .log-item:last-child { border-bottom: none; }

    .log-avatar {
      width: 40px; height: 40px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      font-size: 13px;
      flex-shrink: 0;
    }

    .log-avatar.monthly { background: rgba(255,204,0,0.1); color: var(--hazard); border: 1px solid rgba(255,204,0,0.25); }
    .log-avatar.walkin  { background: rgba(74,144,226,0.1); color: #4a90e2; border: 1px solid rgba(74,144,226,0.25); }

    .log-info { flex: 1; }
    .log-name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); font-family: 'Chakra Petch', sans-serif; text-transform: uppercase; letter-spacing: 0.3px; }
    .log-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }

    .log-badge {
      font-size: 10px;
      font-family: 'Chakra Petch', sans-serif;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 4px 10px;
      flex-shrink: 0;
    }

    .log-badge.monthly { background: rgba(255,204,0,0.1); color: var(--hazard); border: 1px solid rgba(255,204,0,0.2); }
    .log-badge.walkin  { background: rgba(74,144,226,0.1); color: #4a90e2; border: 1px solid rgba(74,144,226,0.2); }

    .log-time { font-size: 11px; color: var(--text-muted); text-align: right; flex-shrink: 0; min-width: 72px; line-height: 1.5; }

    .log-empty {
      padding: 48px 20px;
      text-align: center;
      color: var(--text-muted);
      font-size: 13px;
      font-family: 'Chakra Petch', sans-serif;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .log-empty i { font-size: 32px; display: block; margin-bottom: 12px; opacity: 0.25; }

    /* ── Toast ── */
    .toast-container {
      position: fixed;
      bottom: 28px; right: 28px;
      z-index: 4000;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .toast {
      background: var(--bg-surface);
      border: 1px solid #2a2a2a;
      padding: 16px 18px;
      max-width: 320px;
      display: flex;
      gap: 12px;
      align-items: flex-start;
      box-shadow: 0 8px 32px rgba(0,0,0,0.6);
      animation: slideInToast 0.25s ease;
    }

    .toast.success { border-left: 3px solid var(--success); }
    .toast.error   { border-left: 3px solid var(--danger); }
    .toast.warn    { border-left: 3px solid var(--warning); }

    @keyframes slideInToast {
      from { transform: translateX(40px); opacity: 0; }
      to   { transform: translateX(0);   opacity: 1; }
    }

    .toast-icon { font-size: 18px; margin-top: 1px; flex-shrink: 0; }
    .toast.success .toast-icon { color: var(--success); }
    .toast.error   .toast-icon { color: var(--danger); }
    .toast.warn    .toast-icon { color: var(--warning); }

    .toast-msg { flex: 1; font-size: 13px; color: #ccc; line-height: 1.5; }
    .toast-msg strong { color: #fff; }

    .toast-close {
      background: none; border: none;
      color: #555; font-size: 16px;
      cursor: pointer; padding: 0; line-height: 1; flex-shrink: 0;
    }

    @media (max-width: 900px) {
      .two-col   { grid-template-columns: 1fr; }
      .stats-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="dashboard">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <img src="staffimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img">
      <span class="logo-text">Fit-Stop</span>
    </div>
    <ul class="menu">
      <li onclick="window.location.href='staff.php'">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </li>
      <li onclick="window.location.href='staff.php'">
        <i class="bi bi-person-plus"></i><span>Client Registration</span>
      </li>
      <li onclick="window.location.href='inventory.php'">
        <i class="bi bi-box-seam"></i><span>Inventory</span>
      </li>
      <li onclick="window.location.href='staff.php#attendance'">
        <i class="bi bi-clipboard-check"></i><span>Attendance</span>
      </li>
      <li onclick="window.location.href='staff.php#memberManagement'">
        <i class="bi bi-people"></i><span>Members</span>
      </li>
      <li onclick="window.location.href='staff.php#idGeneration'">
        <i class="bi bi-qr-code"></i><span>ID Generation</span>
      </li>
      <li onclick="window.location.href='monthly.php'">
        <i class="bi bi-calendar-check"></i><span>Monthly Access</span>
      </li>
      <li class="active">
        <i class="bi bi-person-walking"></i><span>Walk-In Log</span>
      </li>
      <li id="settingsBtn" data-target="settings">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </li>
      <li onclick="document.getElementById('logoutForm').submit()">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </li>
      <form id="logoutForm" action="../../login/logout.php" method="POST" style="display:none;">
        <?php echo fitstop_csrf_input(); ?>
      </form>
    </ul>
  </aside>

  <!-- ── Main ── -->
  <main class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Walk-In Attendance</h1>
        <p>Monthly Subscribers &amp; Per-Session Walk-Ins</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-badge">
          <i class="bi bi-calendar3"></i>
          <span id="currentDate">—</span>
        </div>
        <div class="topbar-badge">
          <div class="topbar-dot"></div>
          Live
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-calendar-check-fill"></i></div>
        <div>
          <div class="stat-value" id="stat-monthly">—</div>
          <div class="stat-label">Monthly Check-ins Today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-person-walking"></i></div>
        <div>
          <div class="stat-value" id="stat-walkin">—</div>
          <div class="stat-label">Walk-ins Today</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-people-fill"></i></div>
        <div>
          <div class="stat-value" id="stat-total">—</div>
          <div class="stat-label">Total Visitors Today</div>
        </div>
      </div>
    </div>

    <!-- Two-column form panels -->
    <div class="two-col">

      <!-- Monthly Non-Member Panel -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-icon yellow"><i class="bi bi-calendar-check-fill"></i></div>
          <div>
            <div class="panel-title">Monthly Non-Member</div>
            <div class="panel-sub">Search &amp; log attendance for active subscribers</div>
          </div>
        </div>
        <div class="panel-body">

          <div class="form-group" style="margin-bottom:16px;">
            <label style="display:block;font-size:10.5px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:8px;">Search Subscriber Name</label>
            <div class="search-wrap">
              <input type="text" id="monthlySearch" class="form-input"
                placeholder="Type name to search…"
                autocomplete="off"
                oninput="searchMonthly(this.value)"
                onfocus="if(this.value.length>=1) searchMonthly(this.value)">
              <div class="search-dropdown" id="monthlyDropdown"></div>
            </div>

            <!-- Selected subscriber pill -->
            <div class="selected-pill" id="monthlyPill">
              <div class="pill-avatar" id="pillAvatar">—</div>
              <div class="pill-info">
                <div class="pill-name" id="pillName">—</div>
                <div class="pill-meta" id="pillMeta">—</div>
              </div>
              <button class="pill-clear" onclick="clearMonthlySelection()" title="Clear">×</button>
            </div>
          </div>

          <input type="hidden" id="selectedMonthId">
          <input type="hidden" id="selectedMonthName">

          <button class="panel-btn-primary" id="monthlyCheckInBtn" onclick="recordMonthlyAttendance()" disabled>
            <i class="bi bi-check-circle-fill"></i> Record Attendance
          </button>
          <button class="panel-btn-secondary" onclick="clearMonthlySelection()">
            <i class="bi bi-x-circle"></i> Clear
          </button>

        </div>
      </div>

      <!-- Walk-In Per Session Panel -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-icon green"><i class="bi bi-person-walking"></i></div>
          <div>
            <div class="panel-title">Non-Member Walk-In</div>
            <div class="panel-sub">Per-session entry — no active subscription required</div>
          </div>
        </div>
        <div class="panel-body">

          <div class="form-group" style="margin-bottom:16px;">
            <label style="display:block;font-size:10.5px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:8px;">Customer Name</label>
            <input type="text" id="walkinName" class="form-input"
              placeholder="Enter customer full name"
              oninput="document.getElementById('walkinBtn').disabled = !this.value.trim()">
          </div>

          <div class="info-box">
            <i class="bi bi-info-circle"></i>
            <p>This records a <strong>one-time session entry</strong> for non-members paying the day-pass / walk-in rate. No account or subscription needed.</p>
          </div>

          <button class="panel-btn-primary green-btn" id="walkinBtn" onclick="recordWalkin()" disabled>
            <i class="bi bi-door-open-fill"></i> Record Walk-In
          </button>
          <button class="panel-btn-secondary" onclick="document.getElementById('walkinName').value='';document.getElementById('walkinBtn').disabled=true;">
            <i class="bi bi-x-circle"></i> Clear
          </button>

        </div>
      </div>

    </div>

    <!-- Today's log -->
    <div class="log-section">
      <div class="log-header">
        <div class="log-title">
          <span class="live-dot"></span>
          Today's Attendance Log
        </div>
        <div class="log-filter">
          <button class="filter-btn active" onclick="setFilter('all', this)">All</button>
          <button class="filter-btn" onclick="setFilter('monthly', this)">Monthly</button>
          <button class="filter-btn" onclick="setFilter('walkin', this)">Walk-In</button>
        </div>
      </div>
      <div class="log-list" id="logList">
        <div class="log-empty"><i class="bi bi-hourglass-split"></i>Loading today's log…</div>
      </div>
    </div>

  </main>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ── State ──────────────────────────────────────────────────────────────────
let allLogs      = [];
let activeFilter = 'all';

// ── Helpers ────────────────────────────────────────────────────────────────
function escapeHtml(v) {
  return String(v || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function timeAgo(dtStr) {
  const diff = Math.floor((Date.now() - new Date(dtStr.replace(' ','T'))) / 1000);
  if (diff < 60)    return diff + 's ago';
  if (diff < 3600)  return Math.floor(diff / 60) + ' min ago';
  if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
  return Math.floor(diff / 86400) + 'd ago';
}

function fmtTime(dtStr) {
  const d = new Date(dtStr.replace(' ','T'));
  return d.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit', hour12:true });
}

function showToast(type, icon, html) {
  const container = document.getElementById('toastContainer');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `
    <i class="bi ${icon} toast-icon"></i>
    <div class="toast-msg">${html}</div>
    <button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
  container.appendChild(el);
  setTimeout(() => { if (el.parentElement) el.remove(); }, 5500);
}

// ── Date ──────────────────────────────────────────────────────────────────
document.getElementById('currentDate').textContent =
  new Date().toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });

// ── Monthly search ─────────────────────────────────────────────────────────
let searchTimer = null;

function searchMonthly(q) {
  clearTimeout(searchTimer);
  const dd = document.getElementById('monthlyDropdown');
  if (q.trim().length < 1) { dd.classList.remove('open'); return; }
  searchTimer = setTimeout(() => {
    const fd = new FormData();
    fd.append('action', 'search_monthly');
    fd.append('query',  q.trim());
    fetch('walkin_attendance.php', { method:'POST', body:fd })
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        const results = data.results || [];
        if (results.length === 0) {
          dd.innerHTML = '<div class="search-empty"><i class="bi bi-search" style="display:block;font-size:20px;margin-bottom:8px;opacity:.3;"></i>No active subscribers found.</div>';
        } else {
          dd.innerHTML = results.map(r => {
            const exp = new Date(r.expires_in).toLocaleDateString('en-US',
              { month:'short', day:'numeric', year:'numeric' });
            return `<div class="search-item" onclick="selectMonthly(${r.id}, '${escapeHtml(r.name)}', '${escapeHtml(r.expires_in)}')">
              <div class="item-name">${escapeHtml(r.name)}</div>
              <div class="item-meta">Monthly ID: <strong>#${r.id}</strong> &nbsp;·&nbsp;
                <span class="badge-expiry">Expires ${escapeHtml(exp)}</span>
              </div>
            </div>`;
          }).join('');
        }
        dd.classList.add('open');
      }).catch(() => {});
  }, 280);
}

function selectMonthly(id, name, expiresIn) {
  document.getElementById('selectedMonthId').value   = id;
  document.getElementById('selectedMonthName').value = name;
  document.getElementById('monthlySearch').value     = '';
  document.getElementById('monthlyDropdown').classList.remove('open');

  const exp      = new Date(expiresIn).toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });
  const initials = name.substring(0,2).toUpperCase();
  document.getElementById('pillAvatar').textContent = initials;
  document.getElementById('pillName').textContent   = name;
  document.getElementById('pillMeta').textContent   = 'Active · Expires ' + exp;
  document.getElementById('monthlyPill').classList.add('show');
  document.getElementById('monthlyCheckInBtn').disabled = false;
}

function clearMonthlySelection() {
  document.getElementById('selectedMonthId').value   = '';
  document.getElementById('selectedMonthName').value = '';
  document.getElementById('monthlySearch').value     = '';
  document.getElementById('monthlyDropdown').classList.remove('open');
  document.getElementById('monthlyPill').classList.remove('show');
  document.getElementById('monthlyCheckInBtn').disabled = true;
}

document.addEventListener('click', function(e) {
  const wrap = document.querySelector('.search-wrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('monthlyDropdown').classList.remove('open');
  }
});

// ── Record monthly attendance ──────────────────────────────────────────────
function recordMonthlyAttendance() {
  const monthId = document.getElementById('selectedMonthId').value;
  const name    = document.getElementById('selectedMonthName').value;
  if (!monthId || !name) { showToast('error','bi-exclamation-triangle-fill','No subscriber selected.'); return; }

  const btn = document.getElementById('monthlyCheckInBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Recording…';

  const fd = new FormData();
  fd.append('action',   'record_monthly_attendance');
  fd.append('month_id', monthId);
  fd.append('name',     name);

  fetch('walkin_attendance.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Record Attendance';
      if (data.success) {
        const exp = new Date(data.expires_in).toLocaleDateString('en-US',
          { month:'short', day:'numeric', year:'numeric' });
        showToast('success', 'bi-check-circle-fill',
          `<strong>${escapeHtml(name)}</strong> checked in successfully.<br>
           <span style="font-size:11px;color:#666;">Monthly · Expires ${escapeHtml(exp)}</span>`);
        clearMonthlySelection();
        loadTodayLog();
      } else if (data.duplicate) {
        showToast('warn', 'bi-exclamation-circle-fill', data.message);
        btn.disabled = false;
      } else {
        showToast('error', 'bi-x-circle-fill', data.message || 'Failed to record attendance.');
        btn.disabled = false;
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Record Attendance';
      showToast('error','bi-wifi-off','Unable to reach server.');
    });
}

// ── Record walk-in ─────────────────────────────────────────────────────────
function recordWalkin() {
  const name = document.getElementById('walkinName').value.trim();
  if (!name) { showToast('error','bi-exclamation-triangle-fill','Please enter a customer name.'); return; }

  const btn = document.getElementById('walkinBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Recording…';

  const fd = new FormData();
  fd.append('action', 'record_walkin');
  fd.append('name',   name);

  fetch('walkin_attendance.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      btn.innerHTML = '<i class="bi bi-door-open-fill"></i> Record Walk-In';
      if (data.success) {
        showToast('success','bi-check-circle-fill',
          `<strong>${escapeHtml(name)}</strong> walk-in recorded successfully.<br>
           <span style="font-size:11px;color:#666;">Per-session entry</span>`);
        document.getElementById('walkinName').value = '';
        btn.disabled = true;
        loadTodayLog();
      } else {
        showToast('error','bi-x-circle-fill', data.message || 'Failed to record walk-in.');
        btn.disabled = false;
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-door-open-fill"></i> Record Walk-In';
      showToast('error','bi-wifi-off','Unable to reach server.');
    });
}

// ── Today's log ────────────────────────────────────────────────────────────
function loadTodayLog() {
  const fd = new FormData();
  fd.append('action', 'get_today_log');
  fetch('walkin_attendance.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      allLogs = data.records || [];
      document.getElementById('stat-monthly').textContent = data.monthly_count;
      document.getElementById('stat-walkin').textContent  = data.walkin_count;
      document.getElementById('stat-total').textContent   = data.total;
      renderLog();
    }).catch(() => {});
}

function setFilter(f, btn) {
  activeFilter = f;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderLog();
}

function renderLog() {
  const list = document.getElementById('logList');
  const filtered = activeFilter === 'all'
    ? allLogs
    : allLogs.filter(r => r.attendance_type === activeFilter);

  if (filtered.length === 0) {
    list.innerHTML = `<div class="log-empty"><i class="bi bi-clipboard-x"></i>No records for this filter yet.</div>`;
    return;
  }

  list.innerHTML = filtered.map(r => {
    const isMonthly = r.attendance_type === 'monthly';
    const initials  = r.name.substring(0,2).toUpperCase();
    const typeClass = isMonthly ? 'monthly' : 'walkin';
    const typeLabel = isMonthly ? 'Monthly' : 'Walk-In';
    const meta      = isMonthly
      ? `Monthly Subscriber · ID #${escapeHtml(String(r.month_id))}`
      : 'Per-Session · Non-Member';
    const timeStr = fmtTime(r.datetime);
    const agoStr  = timeAgo(r.datetime);

    return `<div class="log-item">
      <div class="log-avatar ${typeClass}">${escapeHtml(initials)}</div>
      <div class="log-info">
        <div class="log-name">${escapeHtml(r.name)}</div>
        <div class="log-meta">${meta}</div>
      </div>
      <span class="log-badge ${typeClass}">${typeLabel}</span>
      <div class="log-time">${timeStr}<br><span style="font-size:10px;">${agoStr}</span></div>
    </div>`;
  }).join('');
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  loadTodayLog();
  setInterval(loadTodayLog, 15000);
});
</script>
</body>
</html>