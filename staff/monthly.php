<?php
session_start();
require_once '../login/connection.php';
require_once '../includes/security.php';
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    error_reporting(0);
    $action = $_POST['action'];

    try {

        if ($action === 'get_monthly') {
            $filter = $_POST['filter'] ?? 'all';
            $search = trim($_POST['search'] ?? '');
            $today  = date('Y-m-d');
            $soon7  = date('Y-m-d', strtotime('+7 days'));

            $where      = [];
            $mainParams = [];

            if ($filter === 'active') {
                $where[]      = "date(m.expires_in) >= date(?)";
                $mainParams[] = $today;
            } elseif ($filter === 'expired') {
                $where[]      = "date(m.expires_in) < date(?)";
                $mainParams[] = $today;
            } elseif ($filter === 'expiring') {
                $where[]      = "date(m.expires_in) >= date(?)";
                $mainParams[] = $today;
                $where[]      = "date(m.expires_in) <= date(?)";
                $mainParams[] = $soon7;
            }

            if ($search !== '') {
                $where[]      = "m.name LIKE ?";
                $mainParams[] = '%' . $search . '%';
            }

            $mainParams[] = $today;
            $whereClause  = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "
                SELECT
                    m.id,
                    m.name,
                    m.expires_in,
                    m.member,
                    m.image,
                    COALESCE(u.username, '') AS username,
                    COALESCE(u.first_name, '') AS first_name,
                    COALESCE(u.last_name, '') AS last_name,
                    COALESCE(u.email, '') AS email,
                    CAST(julianday(date(m.expires_in)) - julianday(date(?)) AS INTEGER) AS days_left
                FROM monthly m
                LEFT JOIN users u ON u.id = m.member
                {$whereClause}
                ORDER BY m.expires_in ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($mainParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                foreach ($row as $k => $v) {
                    $row[$k] = ($v === null) ? null : (string)$v;
                }
            }
            unset($row);

            $cntSql = "
                SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN date(expires_in) >= date(?) THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN date(expires_in) <  date(?) THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN date(expires_in) >= date(?) AND date(expires_in) <= date(?) THEN 1 ELSE 0 END) AS expiring_soon
                FROM monthly
            ";
            $cntStmt = $pdo->prepare($cntSql);
            $cntStmt->execute([$today, $today, $today, $soon7]);
            $summary = $cntStmt->fetch(PDO::FETCH_ASSOC);

            $out = json_encode(['success' => true, 'records' => $rows, 'summary' => $summary]);
            echo $out === false ? json_encode(['success' => false, 'message' => 'JSON encode error']) : $out;
            exit;
        }

        // ── Get users who have a membership purchase but NO linked monthly record ──
        // Shows users from the memberships/purchases table who haven't been connected
        // to any monthly record yet (member column is NULL for them in monthly table)
        if ($action === 'get_unconverted_users') {
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.first_name, u.last_name, u.email
                FROM users u
                WHERE u.user_type = 'user'
                  AND u.id NOT IN (
                      SELECT member FROM monthly
                      WHERE member IS NOT NULL
                  )
                ORDER BY u.first_name ASC, u.last_name ASC
            ");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
        }

        // ── Convert walk-in monthly record to a registered member ──
        if ($action === 'convert_to_member') {
            $monthlyId = (int)($_POST['monthly_id'] ?? 0);
            $userId    = (int)($_POST['user_id']    ?? 0);
            if (!$monthlyId || !$userId) {
                echo json_encode(['success' => false, 'message' => 'Missing monthly_id or user_id.']);
                exit;
            }

            // Fetch user name to update the name field
            $userStmt = $pdo->prepare("SELECT first_name, last_name, username FROM users WHERE id = ? AND user_type = 'user' LIMIT 1");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found or not a regular user.']);
                exit;
            }

            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            if (!$fullName) $fullName = $user['username'];

            $upd = $pdo->prepare("UPDATE monthly SET member = ?, name = ? WHERE id = ?");
            $upd->execute([$userId, $fullName, $monthlyId]);

            echo json_encode(['success' => true, 'new_name' => $fullName, 'user_id' => $userId]);
            exit;
        }

        // ── Renew (member gets 650, walk-in gets 750) ──
        if ($action === 'renew_monthly') {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }

            $stmt = $pdo->prepare("SELECT expires_in, member FROM monthly WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Record not found.']); exit; }

            $base      = new DateTime($row['expires_in']);
            $todayDt   = new DateTime();
            $startFrom = $base < $todayDt ? $todayDt : $base;
            $newExpiry = (clone $startFrom)->modify('+30 days')->format('Y-m-d');

            $upd = $pdo->prepare("UPDATE monthly SET expires_in = ? WHERE id = ?");
            $upd->execute([$newExpiry, $id]);

            // Determine rate based on whether member is linked
            $rate = $row['member'] ? 650 : 750;

            echo json_encode(['success' => true, 'new_expiry' => $newExpiry, 'rate' => $rate, 'is_member' => !empty($row['member'])]);
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
  <title>Monthly Access — Fit-Stop Gym</title>
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .monthly-controls {
      display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 22px;
    }
    .filter-tabs {
      display: flex; gap: 0; border: 1px solid var(--border); overflow: hidden; flex-shrink: 0;
    }
    .filter-tab {
      padding: 9px 16px; background: transparent; border: none;
      color: var(--text-muted); font-family: 'Chakra Petch', sans-serif;
      font-size: 10.5px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.8px; cursor: pointer; transition: all 0.2s;
      border-right: 1px solid var(--border);
    }
    .filter-tab:last-child { border-right: none; }
    .filter-tab:hover { color: var(--text-primary); background: rgba(255,255,255,0.04); }
    .filter-tab.active { background: var(--hazard); color: #000; }
    .filter-tab .tab-count {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 18px; height: 18px; background: rgba(0,0,0,0.25);
      border-radius: 999px; font-size: 9px; margin-left: 6px;
      padding: 0 4px; font-family: 'DM Sans', sans-serif; font-weight: 700;
    }
    .filter-tab.active .tab-count { background: rgba(0,0,0,0.2); }
    .search-bar { flex: 1; min-width: 200px; position: relative; }
    .search-bar i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; pointer-events: none; }
    .search-bar input { width: 100%; padding: 10px 14px 10px 38px; background: var(--bg-surface); border: 1px solid var(--border); color: var(--text-primary); font-family: 'DM Sans', sans-serif; font-size: 13px; transition: border-color 0.2s; }
    .search-bar input:focus { outline: none; border-color: var(--hazard); }
    .search-bar input::placeholder { color: #3a3a3a; }

    .monthly-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; }
    .monthly-card { background: var(--bg-card); border: 1px solid var(--border); padding: 0; overflow: hidden; transition: all 0.25s ease; position: relative; }
    .monthly-card:hover { border-color: var(--border-accent); transform: translateY(-3px); box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
    .monthly-card .card-accent { height: 3px; width: 100%; }
    .monthly-card.status-active .card-accent   { background: var(--success); }
    .monthly-card.status-expiring .card-accent { background: var(--warning); }
    .monthly-card.status-expired .card-accent  { background: var(--danger); }
    .monthly-card .card-body { padding: 20px 20px 16px; }
    .card-avatar { width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; font-family: 'Chakra Petch', sans-serif; font-size: 18px; font-weight: 700; color: #000; flex-shrink: 0; overflow: hidden; border: 2px solid var(--border); }
    .monthly-card.status-active .card-avatar   { background: var(--success); border-color: var(--success); }
    .monthly-card.status-expiring .card-avatar { background: var(--warning); border-color: var(--warning); color: #000; }
    .monthly-card.status-expired .card-avatar  { background: #2a2a2a; border-color: var(--danger); color: var(--danger); }
    .card-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .card-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
    .card-info { flex: 1; min-width: 0; }
    .card-name { font-family: 'Chakra Petch', sans-serif; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .card-sub { font-size: 11px; color: var(--text-muted); margin-top: 3px; font-family: 'Courier New', monospace; }
    .card-status-badge { padding: 3px 10px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif; letter-spacing: 0.8px; border: 1px solid; white-space: nowrap; flex-shrink: 0; }
    .badge-active   { background: rgba(34,208,122,0.1);  color: var(--success); border-color: rgba(34,208,122,0.3); }
    .badge-expiring { background: rgba(255,159,67,0.1);  color: var(--warning); border-color: rgba(255,159,67,0.3); }
    .badge-expired  { background: rgba(255,71,87,0.1);   color: var(--danger);  border-color: rgba(255,71,87,0.3); }
    .badge-walkin   { background: rgba(99,102,241,0.1);  color: #818cf8;        border-color: rgba(99,102,241,0.3); }
    .card-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
    .date-block { background: var(--bg-surface); border: 1px solid var(--border); padding: 10px 12px; }
    .date-block .date-label { font-size: 9.5px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; font-family: 'Chakra Petch', sans-serif; display: block; margin-bottom: 5px; }
    .date-block .date-value { font-size: 13px; font-weight: 700; font-family: 'Chakra Petch', sans-serif; color: var(--text-primary); }
    .monthly-card.status-active .days-remaining   { color: var(--success); }
    .monthly-card.status-expiring .days-remaining { color: var(--warning); }
    .monthly-card.status-expired .days-remaining  { color: var(--danger); }
    .progress-bar-wrap { height: 4px; background: rgba(255,255,255,0.06); margin-bottom: 14px; position: relative; overflow: hidden; }
    .progress-bar-fill { height: 100%; transition: width 0.6s ease; }
    .monthly-card.status-active .progress-bar-fill   { background: var(--success); }
    .monthly-card.status-expiring .progress-bar-fill { background: var(--warning); }
    .monthly-card.status-expired .progress-bar-fill  { background: var(--danger); width: 0 !important; }

    /* Card action buttons */
    .card-actions { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; gap: 8px; }
    .card-btn {
      flex: 1; padding: 9px 8px; border: 1px solid var(--border);
      background: transparent; color: var(--text-muted);
      font-family: 'Chakra Petch', sans-serif; font-size: 10px;
      font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px;
      cursor: pointer; transition: all 0.2s; display: flex;
      align-items: center; justify-content: center; gap: 5px;
    }
    .card-btn:hover { border-color: var(--hazard); color: var(--hazard); background: rgba(255,204,0,0.05); }
    .card-btn.btn-convert { border-color: rgba(99,102,241,0.3); color: #818cf8; }
    .card-btn.btn-convert:hover { background: rgba(99,102,241,0.08); border-color: #818cf8; }


    /* Convert modal */
    .convert-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.88); z-index: 3000;
      align-items: center; justify-content: center;
      backdrop-filter: blur(5px);
    }
    .convert-overlay.open { display: flex; }
    .convert-box {
      background: #111; border: 1px solid #2a2a2a;
      border-top: 2px solid #818cf8;
      padding: 32px; max-width: 480px; width: 90%;
      font-family: 'DM Sans', sans-serif; color: #fff;
      box-shadow: 0 0 40px rgba(0,0,0,0.6);
    }
    .convert-box h3 {
      font-family: 'Chakra Petch', sans-serif; font-size: 15px;
      letter-spacing: 1px; text-transform: uppercase; color: #818cf8;
      margin-bottom: 6px;
    }
    .convert-box .sub { font-size: 12px; color: #555; margin-bottom: 20px; }
    .convert-search {
      width: 100%; padding: 11px 14px;
      background: #0d0d0d; border: 1px solid #2a2a2a;
      color: #fff; font-family: 'DM Sans', sans-serif; font-size: 13px;
      margin-bottom: 10px; box-sizing: border-box;
      transition: border-color 0.2s;
    }
    .convert-search:focus { outline: none; border-color: #818cf8; }
    .convert-list {
      max-height: 240px; overflow-y: auto;
      border: 1px solid #1e1e1e; margin-bottom: 20px;
    }
    .convert-item {
      padding: 12px 14px; cursor: pointer;
      border-bottom: 1px solid #1a1a1a;
      transition: background 0.15s;
      display: flex; align-items: center; gap: 12px;
    }
    .convert-item:last-child { border-bottom: none; }
    .convert-item:hover { background: rgba(99,102,241,0.08); }
    .convert-item.selected { background: rgba(99,102,241,0.15); border-left: 2px solid #818cf8; }
    .convert-item-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Chakra Petch', sans-serif; font-size: 12px;
      font-weight: 700; color: #818cf8; flex-shrink: 0;
    }
    .convert-item-name { font-weight: 600; font-size: 13.5px; color: #fff; }
    .convert-item-sub { font-size: 11px; color: #555; font-family: 'Courier New', monospace; margin-top: 2px; }
    .convert-empty { padding: 20px; text-align: center; color: #444; font-size: 13px; }
    .convert-actions { display: flex; gap: 10px; }

    /* Monthly stats */
    .monthly-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
    @media (max-width: 1100px) { .monthly-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .monthly-stats { grid-template-columns: 1fr 1fr; } .monthly-grid { grid-template-columns: 1fr; } }

    .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); grid-column: 1 / -1; }
    .empty-state i { font-size: 48px; display: block; margin-bottom: 16px; color: #2a2a2a; }
    .empty-state p { font-family: 'Chakra Petch', sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; }

    .skeleton-card { background: var(--bg-card); border: 1px solid var(--border); overflow: hidden; }
    .skeleton-bar { background: linear-gradient(90deg,#1a1a1a 25%,#222 50%,#1a1a1a 75%); background-size: 200% 100%; animation: shimmer 1.4s infinite; border-radius: 2px; }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    .toast-container { position: fixed; bottom: 28px; right: 28px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none; }
    .toast-msg { background: var(--bg-surface); border: 1px solid #2a2a2a; padding: 14px 18px; min-width: 280px; max-width: 340px; display: flex; gap: 12px; align-items: flex-start; animation: toastIn 0.25s ease; pointer-events: all; box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
    .toast-msg.success { border-left: 3px solid var(--success); }
    .toast-msg.error   { border-left: 3px solid var(--danger); }
    .toast-msg.info    { border-left: 3px solid #818cf8; }
    @keyframes toastIn { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    .toast-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .toast-msg.success .toast-icon { color: var(--success); }
    .toast-msg.error   .toast-icon { color: var(--danger); }
    .toast-msg.info    .toast-icon { color: #818cf8; }
    .toast-text p { margin: 0; font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
    .toast-text b { display: block; color: var(--text-primary); font-size: 13px; margin-bottom: 2px; font-family: 'Chakra Petch', sans-serif; text-transform: uppercase; letter-spacing: 0.3px; }
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
      <li onclick="window.location.href='staff.php'" style="cursor:pointer;"><i class="bi bi-speedometer2"></i><span>Dashboard</span></li>
      <li onclick="window.location.href='staff.php'" style="cursor:pointer;"><i class="bi bi-person-plus"></i><span>Client Registration</span></li>
      <li onclick="window.location.href='inventory.php'" style="cursor:pointer;"><i class="bi bi-box-seam"></i><span>Inventory</span></li>
      <li onclick="window.location.href='staff.php#attendance'" style="cursor:pointer;"><i class="bi bi-clipboard-check"></i><span>Attendance</span></li>
      <li onclick="window.location.href='staff.php#memberManagement'" style="cursor:pointer;"><i class="bi bi-people"></i><span>Members</span></li>
      <li class="active" style="cursor:default;"><i class="bi bi-calendar-check"></i><span>Monthly Access</span></li>
      <li onclick="window.location.href='walkin_attendance.php'" style="cursor:pointer;"><i class="bi bi-person-walking"></i><span>Walk-In Log</span></li>
      <li onclick="window.location.href='staff.php#settings'" style="cursor:pointer;"><i class="bi bi-gear"></i><span>Settings</span></li>
      <li onclick="document.getElementById('logoutForm').submit()" style="cursor:pointer"><i class="bi bi-box-arrow-right"></i><span>Logout</span></li>
      <form id="logoutForm" action="../../login/logout.php" method="POST" style="display:none;">
        <?php echo fitstop_csrf_input(); ?>
      </form>
    </ul>
  </aside>

  <main class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Monthly Access</h1>
        <p>Fit-Stop Gym — Subscription Management</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-badge"><div class="topbar-dot"></div>Active Staff Member</div>
        <div class="topbar-badge"><i class="bi bi-calendar3"></i><span id="currentDate">—</span></div>
        <button class="btn-primary" onclick="window.location.href='staff.php'" style="padding:9px 18px;font-size:11px;display:flex;align-items:center;gap:7px;">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="monthly-stats" id="statsRow">
      <div class="stat-box">
        <div class="stat-icon members"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="stat-info"><span class="stat-value" id="stat-total">—</span><span class="stat-label">Total Subscriptions</span></div>
      </div>
      <div class="stat-box">
        <div class="stat-icon registrations"><i class="bi bi-person-check-fill"></i></div>
        <div class="stat-info"><span class="stat-value" id="stat-active">—</span><span class="stat-label">Active</span></div>
      </div>
      <div class="stat-box">
        <div class="stat-icon equipment"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info"><span class="stat-value" id="stat-expiring">—</span><span class="stat-label">Expiring Soon</span></div>
      </div>
      <div class="stat-box">
        <div class="stat-icon notifications"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-info"><span class="stat-value" id="stat-expired">—</span><span class="stat-label">Expired</span></div>
      </div>
    </div>

    <!-- Controls -->
    <div class="monthly-controls">
      <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all"      onclick="setFilter('all',this)">All <span class="tab-count" id="tc-all">—</span></button>
        <button class="filter-tab"        data-filter="active"   onclick="setFilter('active',this)">Active <span class="tab-count" id="tc-active">—</span></button>
        <button class="filter-tab"        data-filter="expiring" onclick="setFilter('expiring',this)">Expiring <span class="tab-count" id="tc-expiring">—</span></button>
        <button class="filter-tab"        data-filter="expired"  onclick="setFilter('expired',this)">Expired <span class="tab-count" id="tc-expired">—</span></button>
      </div>
      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" placeholder="Search by name…" oninput="debounceSearch(this.value)">
      </div>
      <button class="btn-primary" onclick="loadMonthly()" style="padding:10px 18px;font-size:11px;display:flex;align-items:center;gap:7px;flex-shrink:0;">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>

    <!-- Cards -->
    <div class="monthly-grid" id="monthlyGrid">
      <?php for($i=0;$i<8;$i++): ?>
      <div class="skeleton-card">
        <div style="height:3px;" class="skeleton-bar"></div>
        <div style="padding:20px;">
          <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div class="skeleton-bar" style="width:52px;height:52px;flex-shrink:0;"></div>
            <div style="flex:1;"><div class="skeleton-bar" style="height:14px;margin-bottom:8px;width:70%;"></div><div class="skeleton-bar" style="height:10px;width:50%;"></div></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;"><div class="skeleton-bar" style="height:50px;"></div><div class="skeleton-bar" style="height:50px;"></div></div>
          <div class="skeleton-bar" style="height:4px;margin-bottom:10px;"></div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;"><div class="skeleton-bar" style="flex:1;height:34px;"></div><div class="skeleton-bar" style="flex:1;height:34px;"></div></div>
      </div>
      <?php endfor; ?>
    </div>
  </main>
</div>

<div class="toast-container" id="toastContainer"></div>

<!-- Convert to Member Modal -->
<div class="convert-overlay" id="convertOverlay">
  <div class="convert-box">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
      <div style="width:42px;height:42px;border-radius:50%;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-person-badge-fill" style="color:#818cf8;font-size:18px;"></i>
      </div>
      <div>
        <h3>Convert to Member</h3>
        <p class="sub">Link this Walk-In subscription to a registered account.<br>Future renewals will be charged at the <strong style="color:var(--success);">member rate (₱650)</strong>.</p>
      </div>
    </div>

    <div style="background:#0d0d0d;border:1px solid #1e1e1e;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#666;">
      <span style="color:#aaa;">Linking subscription for:</span>
      <strong style="color:#fff;margin-left:8px;font-family:'Chakra Petch',sans-serif;" id="convertWalkInName">—</strong>
    </div>

    <input type="text" class="convert-search" id="convertSearch" placeholder="Search registered users…" oninput="filterConvertList(this.value)">

    <div class="convert-list" id="convertList">
      <div class="convert-empty"><i class="bi bi-hourglass-split"></i> Loading users...</div>
    </div>

    <div style="font-size:11px;color:#444;margin-bottom:16px;padding:8px 12px;border:1px solid #1a1a1a;background:#0a0a0a;">
      <i class="bi bi-info-circle" style="color:#818cf8;margin-right:5px;"></i>
      Only showing registered users who are not yet linked to any monthly subscription.
    </div>

    <div class="convert-actions">
      <button onclick="closeConvertModal()" style="flex:1;padding:12px;background:transparent;border:1px solid #333;color:#888;font-family:'Chakra Petch',sans-serif;font-size:11px;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;">Cancel</button>
      <button id="convertConfirmBtn" onclick="confirmConvert()" style="flex:2;padding:12px;background:#818cf8;border:none;color:#fff;font-family:'Chakra Petch',sans-serif;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;cursor:pointer;opacity:0.4;pointer-events:none;" disabled>
        <i class="bi bi-person-check"></i> Confirm Conversion
      </button>
    </div>
  </div>
</div>

<script>
function esc(v) {
  return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function showToast(type, title, msg) {
  const icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-circle-fill', info: 'bi-person-badge-fill' };
  const div = document.createElement('div');
  div.className = 'toast-msg ' + type;
  div.innerHTML = `<i class="bi ${icons[type]||icons.info} toast-icon"></i><div class="toast-text"><b>${esc(title)}</b><p>${esc(msg)}</p></div>`;
  document.getElementById('toastContainer').appendChild(div);
  setTimeout(() => div.remove(), 5000);
}

let currentFilter = 'all';
let currentSearch = '';
let searchTimer   = null;

function setFilter(f, btn) {
  currentFilter = f;
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  loadMonthly();
}

function debounceSearch(val) {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentSearch = val.trim(); loadMonthly(); }, 320);
}

function skeletonHtml() {
  return Array(6).fill(0).map(() => `
    <div class="skeleton-card">
      <div style="height:3px;" class="skeleton-bar"></div>
      <div style="padding:20px;">
        <div style="display:flex;gap:12px;margin-bottom:16px;">
          <div class="skeleton-bar" style="width:52px;height:52px;flex-shrink:0;"></div>
          <div style="flex:1;"><div class="skeleton-bar" style="height:14px;margin-bottom:8px;width:70%;"></div><div class="skeleton-bar" style="height:10px;width:50%;"></div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;"><div class="skeleton-bar" style="height:50px;"></div><div class="skeleton-bar" style="height:50px;"></div></div>
        <div class="skeleton-bar" style="height:4px;margin-bottom:10px;"></div>
      </div>
      <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;"><div class="skeleton-bar" style="flex:1;height:34px;"></div><div class="skeleton-bar" style="flex:1;height:34px;"></div></div>
    </div>`).join('');
}

function loadMonthly() {
  document.getElementById('monthlyGrid').innerHTML = skeletonHtml();
  const fd = new FormData();
  fd.append('action', 'get_monthly');
  fd.append('filter', currentFilter);
  fd.append('search', currentSearch);
  fetch('monthly.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { document.getElementById('monthlyGrid').innerHTML = `<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Failed to load records.</p></div>`; return; }
      const s = data.summary || {};
      document.getElementById('stat-total').textContent    = s.total    || 0;
      document.getElementById('stat-active').textContent   = s.active   || 0;
      document.getElementById('stat-expiring').textContent = s.expiring_soon || 0;
      document.getElementById('stat-expired').textContent  = s.expired  || 0;
      document.getElementById('tc-all').textContent      = s.total    || 0;
      document.getElementById('tc-active').textContent   = s.active   || 0;
      document.getElementById('tc-expiring').textContent = s.expiring_soon || 0;
      document.getElementById('tc-expired').textContent  = s.expired  || 0;
      const records = data.records || [];
      if (records.length === 0) {
        document.getElementById('monthlyGrid').innerHTML = `<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No subscriptions found.</p></div>`;
        return;
      }
      document.getElementById('monthlyGrid').innerHTML = records.map(r => buildCard(r)).join('');
    })
    .catch(() => { document.getElementById('monthlyGrid').innerHTML = `<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Server unreachable.</p></div>`; });
}

function buildCard(r) {
  const daysLeft = parseInt(r.days_left || 0);
  const today    = new Date(); today.setHours(0,0,0,0);
  const expiry   = new Date(r.expires_in + 'T00:00:00');
  const isWalkIn = !r.member;

  let statusClass, badgeClass, badgeLabel, daysLabel;
  if (expiry < today) {
    statusClass = 'status-expired';  badgeClass = 'badge-expired';  badgeLabel = 'Expired';
    daysLabel   = `Expired ${Math.abs(daysLeft)} day${Math.abs(daysLeft)!==1?'s':''} ago`;
  } else if (daysLeft <= 7) {
    statusClass = 'status-expiring'; badgeClass = 'badge-expiring'; badgeLabel = 'Expiring Soon';
    daysLabel   = `${daysLeft} day${daysLeft!==1?'s':''} left`;
  } else {
    statusClass = 'status-active';   badgeClass = 'badge-active';   badgeLabel = 'Active';
    daysLabel   = `${daysLeft} day${daysLeft!==1?'s':''} left`;
  }

  const pct      = Math.min(100, Math.max(0, (daysLeft / 30) * 100));
  const initials = (r.name || '??').substring(0, 2).toUpperCase();
  const displayName = r.member && (r.first_name || r.last_name)
    ? ((r.first_name || '') + ' ' + (r.last_name || '')).trim()
    : r.name;
  const subLine = isWalkIn
    ? '<span style="color:#818cf8;">Walk-In</span>'
    : `@${esc(r.username || '')}`;

  const rateTag = isWalkIn
    ? '<span style="font-size:9px;background:rgba(99,102,241,0.1);color:#818cf8;border:1px solid rgba(99,102,241,0.3);padding:2px 7px;font-family:\'Chakra Petch\',sans-serif;letter-spacing:.5px;text-transform:uppercase;display:inline-block;margin-top:4px;">Walk-In Rate ₱750</span>'
    : '<span style="font-size:9px;background:rgba(34,208,122,0.1);color:var(--success);border:1px solid rgba(34,208,122,0.3);padding:2px 7px;font-family:\'Chakra Petch\',sans-serif;letter-spacing:.5px;text-transform:uppercase;display:inline-block;margin-top:4px;">Member Rate ₱650</span>';

  // Convert button only for walk-in (no linked member)
  const convertBtn = isWalkIn
    ? `<button class="card-btn btn-convert" onclick="openConvertModal('${r.id}','${esc(displayName)}')">
         <i class="bi bi-person-badge"></i> Convert
       </button>`
    : '';

  return `
    <div class="monthly-card ${statusClass}" data-id="${r.id}" id="card-${r.id}">
      <div class="card-accent"></div>
      <div class="card-body">
        <div class="card-head">
          <div class="card-avatar">${initials}</div>
          <div class="card-info">
            <div class="card-name">${esc(displayName)}</div>
            <div class="card-sub">${subLine}</div>
            ${rateTag}
          </div>
          <span class="card-status-badge ${badgeClass}">${badgeLabel}</span>
        </div>
        <div class="card-dates">
          <div class="date-block">
            <span class="date-label"><i class="bi bi-calendar3" style="margin-right:4px;"></i>Expires</span>
            <div class="date-value">${fmtDate(r.expires_in)}</div>
          </div>
          <div class="date-block">
            <span class="date-label"><i class="bi bi-hourglass-split" style="margin-right:4px;"></i>Remaining</span>
            <div class="date-value days-remaining">${daysLabel}</div>
          </div>
        </div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:${pct}%"></div></div>
      </div>
      <div class="card-actions">
        ${convertBtn}
      </div>
    </div>`;
}

// ── Convert to Member Modal ────────────────────────────────────────────
let convertMonthlyId = null;
let convertSelectedUserId = null;
let allUnconvertedUsers = [];

function openConvertModal(monthlyId, walkInName) {
  convertMonthlyId      = monthlyId;
  convertSelectedUserId = null;
  document.getElementById('convertWalkInName').textContent = walkInName;
  document.getElementById('convertSearch').value = '';
  document.getElementById('convertList').innerHTML = '<div class="convert-empty"><i class="bi bi-hourglass-split"></i> Loading registered users...</div>';
  document.getElementById('convertConfirmBtn').disabled = true;
  document.getElementById('convertConfirmBtn').style.opacity = '0.4';
  document.getElementById('convertConfirmBtn').style.pointerEvents = 'none';
  document.getElementById('convertOverlay').classList.add('open');

  const fd = new FormData();
  fd.append('action', 'get_unconverted_users');
  fetch('monthly.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { document.getElementById('convertList').innerHTML = '<div class="convert-empty">Failed to load users.</div>'; return; }
      allUnconvertedUsers = data.users || [];
      renderConvertList(allUnconvertedUsers);
    })
    .catch(() => { document.getElementById('convertList').innerHTML = '<div class="convert-empty">Server error.</div>'; });
}

function closeConvertModal() {
  document.getElementById('convertOverlay').classList.remove('open');
  convertMonthlyId      = null;
  convertSelectedUserId = null;
}

function filterConvertList(query) {
  query = query.toLowerCase().trim();
  const filtered = allUnconvertedUsers.filter(u => {
    const name = ((u.first_name||'') + ' ' + (u.last_name||'')).toLowerCase();
    return name.includes(query) || (u.username||'').toLowerCase().includes(query) || (u.email||'').toLowerCase().includes(query);
  });
  renderConvertList(filtered);
}

function renderConvertList(users) {
  const list = document.getElementById('convertList');
  if (users.length === 0) {
    list.innerHTML = '<div class="convert-empty"><i class="bi bi-inbox"></i> No eligible users found.<br><small style="color:#333;">All registered users are already linked to a monthly subscription.</small></div>';
    return;
  }
  list.innerHTML = users.map(u => {
    const fullName = [u.first_name, u.last_name].filter(Boolean).join(' ') || u.username;
    const initials = fullName.substring(0,2).toUpperCase();
    const isSelected = String(u.id) === String(convertSelectedUserId);
    return `<div class="convert-item ${isSelected?'selected':''}" onclick="selectConvertUser(${u.id},'${esc(fullName)}')">
      <div class="convert-item-avatar">${initials}</div>
      <div>
        <div class="convert-item-name">${esc(fullName)}</div>
        <div class="convert-item-sub">@${esc(u.username||'—')} · ${esc(u.email||'—')}</div>
      </div>
    </div>`;
  }).join('');
}

function selectConvertUser(userId, name) {
  convertSelectedUserId = userId;
  const btn = document.getElementById('convertConfirmBtn');
  btn.disabled = false;
  btn.style.opacity = '1';
  btn.style.pointerEvents = 'auto';
  renderConvertList(allUnconvertedUsers.filter(u => {
    const q = document.getElementById('convertSearch').value.toLowerCase().trim();
    if (!q) return true;
    const n = ((u.first_name||'') + ' ' + (u.last_name||'')).toLowerCase();
    return n.includes(q) || (u.username||'').toLowerCase().includes(q);
  }));
}

function confirmConvert() {
  if (!convertMonthlyId || !convertSelectedUserId) return;
  const btn = document.getElementById('convertConfirmBtn');
  btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Converting...';

  const fd = new FormData();
  fd.append('action',     'convert_to_member');
  fd.append('monthly_id', convertMonthlyId);
  fd.append('user_id',    convertSelectedUserId);

  fetch('monthly.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false; btn.innerHTML = '<i class="bi bi-person-check"></i> Confirm Conversion';
      if (data.success) {
        showToast('info', 'Converted!', `Subscription linked to ${data.new_name}. Future renewals at ₱650 (Member Rate).`);
        closeConvertModal();
        loadMonthly();
      } else {
        showToast('error', 'Conversion Failed', data.message || 'Unknown error.');
      }
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="bi bi-person-check"></i> Confirm Conversion';
      showToast('error', 'Error', 'Server unreachable.');
    });
}

// Close modal on outside click
document.getElementById('convertOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeConvertModal();
});

// Init
document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });
document.addEventListener('DOMContentLoaded', function() {
  loadMonthly();
  setInterval(loadMonthly, 60000);
});
</script>

</body>
</html>