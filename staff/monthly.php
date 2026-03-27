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

            // SQLite uses julianday() instead of DATEDIFF()
            $mainParams[] = $today;

            $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = "
                SELECT
                    m.id,
                    m.name,
                    m.expires_in,
                    m.member,
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

            // Sanitize all values for JSON safety
            foreach ($rows as &$row) {
                foreach ($row as $k => $v) {
                    $row[$k] = ($v === null) ? null : (string)$v;
                }
            }
            unset($row);

            // Summary counts — SQLite compatible
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

            if ($out === false) {
                echo json_encode(['success' => false, 'message' => 'JSON encode error: ' . json_last_error_msg()]);
            } else {
                echo $out;
            }
            exit;
        }

        if ($action === 'delete_monthly') {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $pdo->prepare("DELETE FROM monthly WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'renew_monthly') {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
            $stmt = $pdo->prepare("SELECT expires_in FROM monthly WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['success' => false, 'message' => 'Record not found.']); exit; }

            $base      = new DateTime($row['expires_in']);
            $todayDt   = new DateTime();
            $startFrom = $base < $todayDt ? $todayDt : $base;
            $newExpiry = (clone $startFrom)->modify('+30 days')->format('Y-m-d');

            $upd = $pdo->prepare("UPDATE monthly SET expires_in = ? WHERE id = ?");
            $upd->execute([$newExpiry, $id]);
            echo json_encode(['success' => true, 'new_expiry' => $newExpiry]);
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
    /* ── Monthly-specific overrides ── */
    .monthly-controls {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 22px;
    }

    .filter-tabs {
      display: flex;
      gap: 0;
      border: 1px solid var(--border);
      overflow: hidden;
      flex-shrink: 0;
    }

    .filter-tab {
      padding: 9px 16px;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-family: 'Chakra Petch', sans-serif;
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      cursor: pointer;
      transition: all 0.2s;
      border-right: 1px solid var(--border);
      position: relative;
    }

    .filter-tab:last-child { border-right: none; }

    .filter-tab:hover { color: var(--text-primary); background: rgba(255,255,255,0.04); }

    .filter-tab.active {
      background: var(--hazard);
      color: #000;
    }

    .filter-tab .tab-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 18px;
      height: 18px;
      background: rgba(0,0,0,0.25);
      border-radius: 999px;
      font-size: 9px;
      margin-left: 6px;
      padding: 0 4px;
      font-family: 'DM Sans', sans-serif;
      font-weight: 700;
    }

    .filter-tab.active .tab-count {
      background: rgba(0,0,0,0.2);
    }

    .search-bar {
      flex: 1;
      min-width: 200px;
      position: relative;
    }

    .search-bar i {
      position: absolute;
      left: 13px; top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 14px;
      pointer-events: none;
    }

    .search-bar input {
      width: 100%;
      padding: 10px 14px 10px 38px;
      background: var(--bg-surface);
      border: 1px solid var(--border);
      color: var(--text-primary);
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      transition: border-color 0.2s;
    }

    .search-bar input:focus { outline: none; border-color: var(--hazard); }
    .search-bar input::placeholder { color: #3a3a3a; }

    /* ── Cards grid ── */
    .monthly-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 14px;
    }

    .monthly-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      padding: 0;
      overflow: hidden;
      transition: all 0.25s ease;
      position: relative;
    }

    .monthly-card:hover {
      border-color: var(--border-accent);
      transform: translateY(-3px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    }

    .monthly-card .card-accent {
      height: 3px;
      width: 100%;
    }

    .monthly-card.status-active .card-accent   { background: var(--success); }
    .monthly-card.status-expiring .card-accent { background: var(--warning); }
    .monthly-card.status-expired .card-accent  { background: var(--danger); }

    .monthly-card .card-body { padding: 20px 20px 16px; }

    .card-avatar {
      width: 52px; height: 52px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Chakra Petch', sans-serif;
      font-size: 18px; font-weight: 700;
      color: #000;
      flex-shrink: 0;
      overflow: hidden;
      border: 2px solid var(--border);
    }

    .monthly-card.status-active .card-avatar   { background: var(--success); border-color: var(--success); }
    .monthly-card.status-expiring .card-avatar { background: var(--warning); border-color: var(--warning); color: #000; }
    .monthly-card.status-expired .card-avatar  { background: #2a2a2a; border-color: var(--danger); color: var(--danger); }

    .card-avatar img {
      width: 100%; height: 100%;
      object-fit: cover;
    }

    .card-head {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 16px;
    }

    .card-info { flex: 1; min-width: 0; }

    .card-name {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-primary);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card-sub {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 3px;
      font-family: 'Courier New', monospace;
    }

    .card-status-badge {
      padding: 3px 10px;
      font-size: 9.5px;
      font-weight: 700;
      text-transform: uppercase;
      font-family: 'Chakra Petch', sans-serif;
      letter-spacing: 0.8px;
      border: 1px solid;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .badge-active   { background: rgba(34,208,122,0.1);  color: var(--success); border-color: rgba(34,208,122,0.3); }
    .badge-expiring { background: rgba(255,159,67,0.1);  color: var(--warning); border-color: rgba(255,159,67,0.3); }
    .badge-expired  { background: rgba(255,71,87,0.1);   color: var(--danger);  border-color: rgba(255,71,87,0.3); }

    .card-dates {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-bottom: 14px;
    }

    .date-block {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      padding: 10px 12px;
    }

    .date-block .date-label {
      font-size: 9.5px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      font-weight: 700;
      font-family: 'Chakra Petch', sans-serif;
      display: block;
      margin-bottom: 5px;
    }

    .date-block .date-value {
      font-size: 13px;
      font-weight: 700;
      font-family: 'Chakra Petch', sans-serif;
      color: var(--text-primary);
    }

    .monthly-card.status-active .days-remaining   { color: var(--success); }
    .monthly-card.status-expiring .days-remaining { color: var(--warning); }
    .monthly-card.status-expired .days-remaining  { color: var(--danger); }

    /* Progress bar */
    .progress-bar-wrap {
      height: 4px;
      background: rgba(255,255,255,0.06);
      margin-bottom: 14px;
      position: relative;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      transition: width 0.6s ease;
    }

    .monthly-card.status-active .progress-bar-fill   { background: var(--success); }
    .monthly-card.status-expiring .progress-bar-fill { background: var(--warning); }
    .monthly-card.status-expired .progress-bar-fill  { background: var(--danger); width: 0 !important; }

    /* ── Empty state ── */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
      grid-column: 1 / -1;
    }

    .empty-state i {
      font-size: 48px;
      display: block;
      margin-bottom: 16px;
      color: #2a2a2a;
    }

    .empty-state p {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
    }

    /* ── Skeleton loader ── */
    .skeleton-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    .skeleton-bar {
      background: linear-gradient(90deg, #1a1a1a 25%, #222 50%, #1a1a1a 75%);
      background-size: 200% 100%;
      animation: shimmer 1.4s infinite;
      border-radius: 2px;
    }

    @keyframes shimmer {
      0%   { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    /* ── Toast ── */
    .toast-container {
      position: fixed;
      bottom: 28px; right: 28px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
    }

    .toast {
      background: var(--bg-surface);
      border: 1px solid #2a2a2a;
      padding: 14px 18px;
      min-width: 280px;
      max-width: 340px;
      display: flex;
      gap: 12px;
      align-items: flex-start;
      animation: toastIn 0.25s ease;
      pointer-events: all;
      box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    }

    .toast.success { border-left: 3px solid var(--success); }
    .toast.error   { border-left: 3px solid var(--danger); }

    @keyframes toastIn {
      from { transform: translateX(40px); opacity: 0; }
      to   { transform: translateX(0);   opacity: 1; }
    }

    .toast-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .toast.success .toast-icon { color: var(--success); }
    .toast.error   .toast-icon { color: var(--danger); }

    .toast-text p    { margin: 0; font-size: 12.5px; color: var(--text-muted); line-height: 1.5; }
    .toast-text b    { display: block; color: var(--text-primary); font-size: 13px; margin-bottom: 2px; font-family: 'Chakra Petch', sans-serif; text-transform: uppercase; letter-spacing: 0.3px; }

    /* ── Confirm modal ── */
    .confirm-overlay {
      display: none;
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.85);
      z-index: 3000;
      align-items: center; justify-content: center;
      backdrop-filter: blur(4px);
    }

    .confirm-overlay.open { display: flex; }

    .confirm-box {
      background: var(--bg-surface);
      border: 1px solid #333;
      border-top: 2px solid var(--danger);
      padding: 32px;
      max-width: 380px;
      width: 90%;
    }

    .confirm-box h3 {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 15px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--danger);
      margin-bottom: 12px;
    }

    .confirm-box p { font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px; }

    .confirm-actions { display: flex; gap: 10px; }

    /* ── Stat boxes ── */
    .monthly-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 24px;
    }

    @media (max-width: 1100px) { .monthly-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .monthly-stats { grid-template-columns: 1fr 1fr; } .monthly-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>

<div class="dashboard">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <img src="staffimage/FIT-STOP LOGO.png" alt="Fit-Stop Logo" class="logo-img">
      <span class="logo-text">Fit-Stop</span>
    </div>
    <ul class="menu">
      <li onclick="window.location.href='staff.php'" style="cursor:pointer;">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </li>
      <li onclick="window.location.href='staff.php'" style="cursor:pointer;">
        <i class="bi bi-person-plus"></i>
        <span>Client Registration</span>
      </li>
      <li onclick="window.location.href='inventory.php'" style="cursor:pointer;">
        <i class="bi bi-box-seam"></i>
        <span>Inventory</span>
      </li>
      <li onclick="window.location.href='staff.php#attendance'" style="cursor:pointer;">
        <i class="bi bi-clipboard-check"></i>
        <span>Attendance</span>
      </li>
      <li onclick="window.location.href='staff.php#memberManagement'" style="cursor:pointer;">
        <i class="bi bi-people"></i>
        <span>Members</span>
      </li>
      <li onclick="window.location.href='staff.php#idGeneration'" style="cursor:pointer;">
        <i class="bi bi-qr-code"></i>
        <span>ID Generation</span>
      </li>
      <li class="active" style="cursor:default;">
        <i class="bi bi-calendar-check"></i>
        <span>Monthly Access</span>
      </li>
      <li onclick="window.location.href='staff.php#settings'" style="cursor:pointer;">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
      </li>
      <li onclick="document.getElementById('logoutForm').submit()" style="cursor:pointer">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
      </li>
      <form id="logoutForm" action="../../login/logout.php" method="POST" style="display:none;">
        <?php echo fitstop_csrf_input(); ?>
      </form>
    </ul>
  </aside>

  <!-- Main -->
  <main class="main-content">

    <div class="topbar">
      <div class="topbar-left">
        <h1>Monthly Access</h1>
        <p>Fit-Stop Gym — Subscription Management</p>
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
        <button class="btn-primary" onclick="window.location.href='staff.php'" style="padding:9px 18px;font-size:11px;display:flex;align-items:center;gap:7px;">
          <i class="bi bi-arrow-left"></i> Back to Dashboard
        </button>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="monthly-stats" id="statsRow">
      <div class="stat-box">
        <div class="stat-icon members"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="stat-info">
          <span class="stat-value" id="stat-total">—</span>
          <span class="stat-label">Total Subscriptions</span>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon registrations"><i class="bi bi-person-check-fill"></i></div>
        <div class="stat-info">
          <span class="stat-value" id="stat-active">—</span>
          <span class="stat-label">Active</span>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon equipment"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
          <span class="stat-value" id="stat-expiring">—</span>
          <span class="stat-label">Expiring Soon</span>
        </div>
      </div>
      <div class="stat-box">
        <div class="stat-icon notifications"><i class="bi bi-x-circle-fill"></i></div>
        <div class="stat-info">
          <span class="stat-value" id="stat-expired">—</span>
          <span class="stat-label">Expired</span>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="monthly-controls">
      <div class="filter-tabs" id="filterTabs">
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
      <!-- Skeleton placeholders -->
      <?php for($i=0;$i<8;$i++): ?>
      <div class="skeleton-card">
        <div style="height:3px;" class="skeleton-bar"></div>
        <div style="padding:20px;">
          <div style="display:flex;gap:12px;margin-bottom:16px;">
            <div class="skeleton-bar" style="width:52px;height:52px;flex-shrink:0;"></div>
            <div style="flex:1;">
              <div class="skeleton-bar" style="height:14px;margin-bottom:8px;width:70%;"></div>
              <div class="skeleton-bar" style="height:10px;width:50%;"></div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
            <div class="skeleton-bar" style="height:50px;"></div>
            <div class="skeleton-bar" style="height:50px;"></div>
          </div>
          <div class="skeleton-bar" style="height:4px;margin-bottom:10px;"></div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;">
          <div class="skeleton-bar" style="flex:1;height:34px;"></div>
          <div class="skeleton-bar" style="flex:1;height:34px;"></div>
        </div>
      </div>
      <?php endfor; ?>
    </div>

  </main>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ── Helpers ──────────────────────────────────────────────────────────
function esc(v) {
  return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function showToast(type, title, msg) {
  const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
  const div = document.createElement('div');
  div.className = 'toast ' + type;
  div.innerHTML = `<i class="bi ${icon} toast-icon"></i>
    <div class="toast-text"><b>${esc(title)}</b><p>${esc(msg)}</p></div>`;
  document.getElementById('toastContainer').appendChild(div);
  setTimeout(() => div.remove(), 4500);
}

// ── State ─────────────────────────────────────────────────────────────
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

// ── Load records ──────────────────────────────────────────────────────
function loadMonthly() {
  const grid = document.getElementById('monthlyGrid');
  grid.innerHTML = Array(6).fill(0).map(() => `
    <div class="skeleton-card">
      <div style="height:3px;" class="skeleton-bar"></div>
      <div style="padding:20px;">
        <div style="display:flex;gap:12px;margin-bottom:16px;">
          <div class="skeleton-bar" style="width:52px;height:52px;flex-shrink:0;"></div>
          <div style="flex:1;"><div class="skeleton-bar" style="height:14px;margin-bottom:8px;width:70%;"></div><div class="skeleton-bar" style="height:10px;width:50%;"></div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
          <div class="skeleton-bar" style="height:50px;"></div><div class="skeleton-bar" style="height:50px;"></div>
        </div>
        <div class="skeleton-bar" style="height:4px;margin-bottom:10px;"></div>
      </div>
      <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;">
        <div class="skeleton-bar" style="flex:1;height:34px;"></div><div class="skeleton-bar" style="flex:1;height:34px;"></div>
      </div>
    </div>`).join('');

  const fd = new FormData();
  fd.append('action', 'get_monthly');
  fd.append('filter', currentFilter);
  fd.append('search', currentSearch);

  fetch('monthly.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success) { grid.innerHTML = `<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Failed to load records.</p></div>`; return; }

      // Update stat counts
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
        grid.innerHTML = `<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No subscriptions found.</p></div>`;
        return;
      }

      grid.innerHTML = records.map(r => buildCard(r)).join('');
    })
    .catch(() => { grid.innerHTML = `<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Server unreachable.</p></div>`; });
}

function buildCard(r) {
  const daysLeft = parseInt(r.days_left || 0);
  const today    = new Date(); today.setHours(0,0,0,0);
  const expiry   = new Date(r.expires_in + 'T00:00:00');

  let statusClass, badgeClass, badgeLabel, daysLabel;

  if (expiry < today) {
    statusClass = 'status-expired';
    badgeClass  = 'badge-expired';
    badgeLabel  = 'Expired';
    daysLabel   = `Expired ${Math.abs(daysLeft)} day${Math.abs(daysLeft)!==1?'s':''} ago`;
  } else if (daysLeft <= 7) {
    statusClass = 'status-expiring';
    badgeClass  = 'badge-expiring';
    badgeLabel  = 'Expiring Soon';
    daysLabel   = `${daysLeft} day${daysLeft!==1?'s':''} left`;
  } else {
    statusClass = 'status-active';
    badgeClass  = 'badge-active';
    badgeLabel  = 'Active';
    daysLabel   = `${daysLeft} day${daysLeft!==1?'s':''} left`;
  }

  // Progress: 30 = full month
  const pct = Math.min(100, Math.max(0, (daysLeft / 30) * 100));

  const initials = (r.name || '??').substring(0, 2).toUpperCase();
  const username = r.username ? `@${esc(r.username)}` : (r.member ? `ID:${esc(r.member)}` : 'Walk-In');

  const avatarHtml = r.image
    ? `<img src="${esc(r.image)}" alt="${esc(r.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
       <span style="display:none;">${initials}</span>`
    : initials;

  return `
    <div class="monthly-card ${statusClass}" data-id="${r.id}">
      <div class="card-accent"></div>
      <div class="card-body">
        <div class="card-head">
          <div class="card-avatar">${avatarHtml}</div>
          <div class="card-info">
            <div class="card-name">${esc(r.name)}</div>
            <div class="card-sub">${username}</div>
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

        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:${pct}%"></div>
        </div>
      </div>
    </div>`;
}


// ── Init ──────────────────────────────────────────────────────────────
const d = new Date();
document.getElementById('currentDate').textContent = d.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' });

document.addEventListener('DOMContentLoaded', function() {
  loadMonthly();
  // Auto-refresh every 60 seconds
  setInterval(loadMonthly, 60000);
});
</script>

</body>
</html>