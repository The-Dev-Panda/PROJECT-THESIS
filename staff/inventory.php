<?php
session_start();
require_once '../login/connection.php';

// Create inventory table
$pdo->exec("CREATE TABLE IF NOT EXISTS inventory (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  item_name   VARCHAR(100) NOT NULL,
  category    VARCHAR(50)  NOT NULL,
  quantity    INTEGER      NOT NULL DEFAULT 0,
  price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description TEXT,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create inventory_notifications table
$pdo->exec("CREATE TABLE IF NOT EXISTS inventory_notifications (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id     INTEGER NOT NULL,
  item_id     INTEGER NOT NULL,
  item_name   VARCHAR(100) NOT NULL,
  qty_sold    INTEGER NOT NULL,
  notif_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  $action = $_POST['action'];

  try {

    // ── Update stock + log notification ───────────────────────────────────────
    if ($action === 'update_stock') {
      $id     = (int)$_POST['id'];
      $change = (int)$_POST['change'];
      $cur    = $pdo->query("SELECT quantity FROM inventory WHERE id = $id")->fetchColumn();
      $newQty = $cur + $change;

      if ($newQty < 0) {
        echo json_encode(['success' => false, 'message' => 'Stock cannot go below 0.']);
        exit;
      }

      $pdo->prepare("UPDATE inventory SET quantity = :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
          ->execute([':qty' => $newQty, ':id' => $id]);

      $row = $pdo->query("SELECT * FROM inventory WHERE id = $id")->fetch(PDO::FETCH_ASSOC);

      // Log notification for every deduction
      if ($change < 0) {
        $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $qtySold  = abs($change);
        $itemName = $row['item_name'];
        $pdo->prepare("INSERT INTO inventory_notifications (user_id, item_id, item_name, qty_sold, notif_at)
                       VALUES (:uid, :iid, :iname, :qty, CURRENT_TIMESTAMP)")
            ->execute([
              ':uid'   => $userId,
              ':iid'   => $id,
              ':iname' => $itemName,
              ':qty'   => $qtySold,
            ]);
      }

      echo json_encode(['success' => true, 'row' => $row]);
      exit;
    }

    // ── Fetch notifications (latest 50) ───────────────────────────────────────
    if ($action === 'get_notifications') {
      $notifs = $pdo->query("
        SELECT n.id, n.item_name, n.qty_sold, n.notif_at,
               u.first_name, u.last_name, u.user_type
        FROM   inventory_notifications n
        LEFT JOIN users u ON u.id = n.user_id
        ORDER  BY n.notif_at DESC
        LIMIT  50
      ")->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['success' => true, 'notifications' => $notifs]);
      exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;

  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
  }
}

// ── Load inventory rows ───────────────────────────────────────────────────────
try {
  $rows = $pdo->query("SELECT * FROM inventory ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $rows = [];
}

// ── Total notification count for badge ───────────────────────────────────────
try {
  $notifCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory_notifications")->fetchColumn();
} catch (Exception $e) {
  $notifCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Inventory Management - Fit-Stop Gym</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="staff.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ── Modals ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.5); z-index: 1000;
      justify-content: center; align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
      background: #fff; border-radius: 12px; padding: 30px;
      width: 420px; max-width: 95%;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
      font-family: 'Poppins', sans-serif; position: relative;
      max-height: 90vh; overflow-y: auto;
    }
    .modal-box h3 { margin: 0 0 4px 0; font-size: 18px; font-weight: 600; color: #1a1a2e; }
    .modal-product-id { font-size: 12px; color: #888; margin-bottom: 18px; display: block; }
    .modal-close {
      position: absolute; top: 14px; right: 16px;
      background: none; border: none; font-size: 20px; cursor: pointer; color: #888;
    }
    .modal-close:hover { color: #333; }
    .modal-field { margin-bottom: 14px; }
    .modal-field label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 5px; }
    .modal-field input,
    .modal-field textarea {
      width: 100%; padding: 9px 12px;
      border: 1.5px solid #ddd; border-radius: 8px;
      font-family: 'Poppins', sans-serif; font-size: 14px;
      box-sizing: border-box; transition: border 0.2s;
    }
    .modal-field input:focus,
    .modal-field textarea:focus { outline: none; border-color: #e53935; }
    .current-stock-info {
      background: #f5f7fa; border-radius: 8px; padding: 10px 14px;
      font-size: 13px; color: #555; margin-bottom: 18px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .current-stock-info strong { font-size: 22px; color: #1a1a2e; }
    .modal-meta {
      background: #f5f7fa; border-radius: 8px; padding: 10px 14px;
      font-size: 12px; color: #888; margin-bottom: 14px;
      display: flex; justify-content: space-between; gap: 10px;
    }
    .modal-meta span { display: flex; flex-direction: column; gap: 2px; }
    .modal-meta strong { font-size: 12px; color: #555; }
    .modal-save-btn {
      width: 100%; padding: 11px; background: #e53935; color: #fff;
      border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 4px;
    }
    .modal-save-btn:hover { background: #c62828; }
    .modal-save-btn:disabled { background: #aaa; cursor: not-allowed; }
    .sold-label {
      display: inline-block; background: #fdecea; color: #e53935;
      font-size: 11px; font-weight: 600; padding: 2px 8px;
      border-radius: 20px; margin-bottom: 16px;
      letter-spacing: 0.5px; text-transform: uppercase;
    }
    .menu li a, .menu li span, .menu li { color: inherit !important; text-decoration: none !important; }

    /* ── Toast ── */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #1a1a2e; color: #fff;
      padding: 12px 22px; border-radius: 8px;
      font-family: 'Poppins', sans-serif; font-size: 13px;
      opacity: 0; transform: translateY(10px);
      transition: all 0.3s; z-index: 9999; pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { border-left: 4px solid #34a853; }
    .toast.error   { border-left: 4px solid #e53935; }

    .add-btn { display: none !important; }

    /* ── Bell / Notification Panel ── */
    .notif-wrapper {
      position: relative; display: inline-block; flex-shrink: 0;
    }
    .notif-bell-btn {
      background: rgba(255, 204, 0, 0.12);
      border: 1.5px solid #FFCC00;
      cursor: pointer;
      font-size: 18px; color: #FFCC00; position: relative;
      padding: 8px 12px;
      transition: background 0.2s, box-shadow 0.2s;
      display: flex; align-items: center; justify-content: center;
      clip-path: polygon(6px 0, 100% 0, 100% calc(100% - 6px), calc(100% - 6px) 100%, 0 100%, 0 6px);
    }
    .notif-bell-btn:hover {
      background: rgba(255, 204, 0, 0.25);
      box-shadow: 0 0 12px rgba(255, 204, 0, 0.4);
    }
    .notif-badge {
      position: absolute; top: -6px; right: -6px;
      background: #ff3b30; color: #fff;
      font-size: 10px; font-weight: 700;
      min-width: 18px; height: 18px;
      border-radius: 999px; display: flex;
      align-items: center; justify-content: center;
      padding: 0 4px; font-family: 'Chakra Petch', sans-serif;
      pointer-events: none;
      border: 2px solid #0a0a0a;
    }
    .notif-badge.hidden { display: none; }

    .notif-panel {
      display: none;
      position: absolute; top: calc(100% + 10px); right: 0;
      width: 400px; max-width: 95vw;
      background: #141414;
      border: 1px solid #333;
      border-top: 2px solid #FFCC00;
      z-index: 2000;
      overflow: hidden;
      clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
      box-shadow: 0 8px 40px rgba(0,0,0,0.6);
    }
    .notif-panel.open { display: block; }

    .notif-panel-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 18px 12px;
      border-bottom: 1px solid #333;
      background: #000;
    }
    .notif-panel-header h4 {
      margin: 0; font-size: 13px; font-weight: 700;
      color: #FFCC00;
      font-family: 'Chakra Petch', sans-serif;
      text-transform: uppercase; letter-spacing: 1px;
    }
    .notif-panel-header span {
      font-size: 11px; color: #666;
      font-family: 'Chakra Petch', sans-serif;
      text-transform: uppercase; letter-spacing: 0.5px;
    }

    .notif-list {
      max-height: 360px; overflow-y: auto;
    }
    .notif-list::-webkit-scrollbar { width: 4px; }
    .notif-list::-webkit-scrollbar-track { background: #0a0a0a; }
    .notif-list::-webkit-scrollbar-thumb { background: #FFCC00; }

    .notif-item {
      display: flex; gap: 12px; align-items: flex-start;
      padding: 14px 18px; border-bottom: 1px solid #222;
      transition: background 0.15s;
    }
    .notif-item:hover { background: rgba(255, 204, 0, 0.05); }
    .notif-item:last-child { border-bottom: none; }

    .notif-icon {
      width: 36px; height: 36px;
      background: rgba(255, 59, 48, 0.15);
      border: 1px solid rgba(255, 59, 48, 0.4);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 15px; color: #ff3b30;
      clip-path: polygon(4px 0, 100% 0, 100% calc(100% - 4px), calc(100% - 4px) 100%, 0 100%, 0 4px);
    }

    .notif-body { flex: 1; min-width: 0; }
    .notif-msg {
      font-size: 12.5px; color: #a0a0a0; line-height: 1.6;
      margin: 0 0 5px;
    }
    .notif-msg strong {
      color: #ffffff;
      font-family: 'Chakra Petch', sans-serif;
      text-transform: uppercase; font-size: 11.5px;
    }
    .notif-msg .item-highlight { color: #FFCC00; font-weight: 700; }
    .notif-time {
      font-size: 10.5px; color: #555;
      text-transform: uppercase; letter-spacing: 0.5px;
    }
    .notif-time i { color: #FFCC00; }

    .notif-empty {
      padding: 35px 18px; text-align: center;
      font-size: 12px; color: #555;
      text-transform: uppercase; letter-spacing: 0.5px;
      font-family: 'Chakra Petch', sans-serif;
    }
    .notif-empty i { font-size: 30px; display: block; margin-bottom: 10px; color: #333; }

    .notif-loader {
      padding: 24px; text-align: center;
      font-size: 12px; color: #555;
      text-transform: uppercase; letter-spacing: 0.5px;
      font-family: 'Chakra Petch', sans-serif;
    }
    .notif-loader i { color: #FFCC00; margin-right: 6px; }

    /* ── Profile row ── */
    .profile-container {
      display: flex; align-items: center; justify-content: space-between;
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
      <li onclick="window.location.href='staff.php'" style="cursor:pointer;">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </li>
      <li onclick="window.location.href='inventory.php'" style="cursor:pointer;">
        <i class="bi bi-box-seam"></i><span>Inventory Management</span>
      </li>
      <li onclick="window.location.href='attendance.php'" style="cursor:pointer;">
        <i class="bi bi-clipboard-check"></i><span>Attendance Tracking</span>
      </li>
      <li onclick="window.location.href='members.php'" style="cursor:pointer;">
        <i class="bi bi-people"></i><span>Member Management</span>
      </li>
      <li onclick="window.location.href='logout.php'" style="cursor:pointer;">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </li>
    </ul>
  </aside>

  <main class="main-content">
    <div class="profile-container">
      <div class="profile-content">
        <div class="profile-text">
          <strong class="profile-name">Inventory Module</strong>
          <span class="profile-streak">&#127947; Product &amp; Stock Management</span>
        </div>
      </div>

      <!-- 🔔 Notification Bell -->
      <div class="notif-wrapper" id="notifWrapper">
        <button class="notif-bell-btn" onclick="toggleNotifPanel()" title="Notification History">
          <i class="bi bi-bell-fill"></i>
          <span class="notif-badge <?= $notifCount === 0 ? 'hidden' : '' ?>" id="notifBadge">
            <?= $notifCount > 99 ? '99+' : $notifCount ?>
          </span>
        </button>
        <div class="notif-panel" id="notifPanel">
          <div class="notif-panel-header">
            <h4><i class="bi bi-bell"></i> Notification History</h4>
            <span id="notifPanelCount"></span>
          </div>
          <div class="notif-list" id="notifList">
            <div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>
          </div>
        </div>
      </div>
    </div>

    <section class="inventory-section">
      <h2>Inventory Products</h2>
      <div class="inventory-header">
        <div class="search-container">
          <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search by name, category, description...">
          </div>
          <button class="search-btn">Search</button>
        </div>
      </div>

      <div class="inventory-table">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Description</th>
              <th>Created At</th>
              <th>Updated At</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="inventoryBody">
            <?php foreach ($rows as $row):
              $qty = (int)$row['quantity'];
              if ($qty === 0)     { $statusCls = 'inactive';  $statusTxt = 'Out of Stock'; }
              elseif ($qty <= 10) { $statusCls = 'low-stock'; $statusTxt = 'Low Stock'; }
              else                { $statusCls = 'active';    $statusTxt = 'Available'; }
              $priceFormatted = '&#8369;' . number_format((float)$row['price'], 2);
            ?>
            <tr
              data-id="<?= $row['id'] ?>"
              data-name="<?= htmlspecialchars($row['item_name']) ?>"
              data-category="<?= htmlspecialchars($row['category']) ?>"
              data-qty="<?= $qty ?>"
              data-price="<?= $row['price'] ?>"
              data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
              data-created="<?= $row['created_at'] ?>"
              data-updated="<?= $row['updated_at'] ?>">
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['item_name']) ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              <td class="qty-cell"><?= $qty ?></td>
              <td><?= $priceFormatted ?></td>
              <td><?= htmlspecialchars($row['description'] ?? '—') ?></td>
              <td class="created-cell"><?= $row['created_at'] ?></td>
              <td class="updated-cell"><?= $row['updated_at'] ?></td>
              <td><span class="status-badge <?= $statusCls ?>"><?= $statusTxt ?></span></td>
              <td>
                <button class="btn-icon" title="Customer Bought" onclick="openSoldModal(this)">
                  <i class="bi bi-cart-dash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<div class="toast" id="toast"></div>

<!-- Customer Bought Modal -->
<div class="modal-overlay" id="soldModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeSoldModal()"><i class="bi bi-x-lg"></i></button>
    <h3 id="soldItemName">Item Name</h3>
    <span class="modal-product-id" id="soldItemId">ID: 0</span>
    <span class="sold-label"><i class="bi bi-cart-dash"></i> Customer Purchase</span>

    <div class="current-stock-info">
      <span>Current Stock</span>
      <strong id="soldCurrentQty">0</strong>
    </div>

    <div class="modal-field">
      <label>How many units were bought? <span style="color:red">*</span></label>
      <input type="number" id="soldQtyInput" min="1" value="1" placeholder="Enter quantity sold">
    </div>

    <div class="modal-field">
      <label>Note <span style="font-weight:400;color:#aaa;">(optional)</span></label>
      <input type="text" id="soldNote" placeholder="e.g. Walk-in customer, member purchase...">
    </div>

    <div class="modal-meta">
      <span><strong>Created At</strong><span id="soldCreated">—</span></span>
      <span><strong>Last Updated</strong><span id="soldUpdated">—</span></span>
    </div>

    <button class="modal-save-btn" id="soldSaveBtn" onclick="saveSoldChange()">
      <i class="bi bi-check-circle"></i> Confirm Sale
    </button>
  </div>
</div>

<script>
  let currentRow     = null;
  let notifPanelOpen = false;
  let notifLoaded    = false;
  let badgeCount     = <?= $notifCount ?>;

  // ── Toast ─────────────────────────────────────────────────────────────────────
  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = 'toast ' + type + ' show';
    setTimeout(() => { t.className = 'toast'; }, 3000);
  }

  // ── Status helper ─────────────────────────────────────────────────────────────
  function getStatus(qty) {
    qty = parseInt(qty);
    if (qty === 0)  return { cls: 'inactive',  txt: 'Out of Stock' };
    if (qty <= 10)  return { cls: 'low-stock', txt: 'Low Stock'    };
    return                 { cls: 'active',    txt: 'Available'    };
  }

  // ── Sold Modal ────────────────────────────────────────────────────────────────
  function openSoldModal(btn) {
    currentRow = btn.closest('tr');
    document.getElementById('soldItemName').textContent   = currentRow.getAttribute('data-name');
    document.getElementById('soldItemId').textContent     = 'ID: ' + currentRow.getAttribute('data-id');
    document.getElementById('soldCurrentQty').textContent = currentRow.querySelector('.qty-cell').textContent;
    document.getElementById('soldQtyInput').value         = 1;
    document.getElementById('soldNote').value             = '';
    document.getElementById('soldCreated').textContent    = currentRow.getAttribute('data-created') || '—';
    document.getElementById('soldUpdated').textContent    = currentRow.getAttribute('data-updated') || '—';
    document.getElementById('soldModal').classList.add('active');
  }

  function closeSoldModal() {
    document.getElementById('soldModal').classList.remove('active');
    currentRow = null;
  }

  function saveSoldChange() {
    const qtyInput = parseInt(document.getElementById('soldQtyInput').value);
    if (!qtyInput || qtyInput < 1) {
      alert('Please enter a valid quantity (at least 1).');
      return;
    }

    const id         = currentRow.getAttribute('data-id');
    const currentQty = parseInt(currentRow.querySelector('.qty-cell').textContent);

    if (currentQty - qtyInput < 0) {
      alert('Cannot sell more than current stock. Current stock: ' + currentQty);
      return;
    }

    const btn = document.getElementById('soldSaveBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

    const fd = new FormData();
    fd.append('action', 'update_stock');
    fd.append('id',     id);
    fd.append('change', -qtyInput);

    fetch('inventory.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const row    = data.row;
          const newQty = parseInt(row.quantity);
          const status = getStatus(newQty);

          currentRow.querySelector('.qty-cell').textContent     = newQty;
          currentRow.querySelector('.updated-cell').textContent = row.updated_at;
          currentRow.querySelector('.status-badge').textContent = status.txt;
          currentRow.querySelector('.status-badge').className   = 'status-badge ' + status.cls;
          currentRow.setAttribute('data-qty',     newQty);
          currentRow.setAttribute('data-updated', row.updated_at);

          // Bump badge
          badgeCount++;
          updateBadge(badgeCount);
          notifLoaded = false; // force reload on next panel open

          showToast('Sale recorded! Stock updated to ' + newQty + '.', 'success');
          closeSoldModal();
        } else {
          alert(data.message || 'Could not update stock.');
        }
      })
      .catch(() => alert('Server error. Please try again.'))
      .finally(() => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm Sale';
      });
  }

  // ── Notification Panel ────────────────────────────────────────────────────────
  function updateBadge(count) {
    const badge = document.getElementById('notifBadge');
    if (count <= 0) {
      badge.classList.add('hidden');
    } else {
      badge.classList.remove('hidden');
      badge.textContent = count > 99 ? '99+' : count;
    }
  }

  function toggleNotifPanel() {
    notifPanelOpen = !notifPanelOpen;
    document.getElementById('notifPanel').classList.toggle('open', notifPanelOpen);

    if (notifPanelOpen) {
      // Reset badge when panel opens
      badgeCount = 0;
      updateBadge(0);
      if (!notifLoaded) loadNotifications();
    }
  }

  function loadNotifications() {
    const list = document.getElementById('notifList');
    list.innerHTML = '<div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>';

    const fd = new FormData();
    fd.append('action', 'get_notifications');

    fetch('inventory.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        notifLoaded = true;
        if (!data.success) {
          list.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load notifications.</div>';
          return;
        }

        const notifs = data.notifications;
        document.getElementById('notifPanelCount').textContent =
          notifs.length + ' record' + (notifs.length !== 1 ? 's' : '');

        if (notifs.length === 0) {
          list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>No notification history yet.</div>';
          return;
        }

        list.innerHTML = notifs.map(n => {
          const firstName = n.first_name || 'Unknown';
          const lastName  = n.last_name  || '';
          const fullName  = (firstName + ' ' + lastName).trim();
          const userType  = n.user_type ? capitalize(n.user_type) : 'Staff';
          const qty       = parseInt(n.qty_sold);
          const timeStr   = formatTime(n.notif_at);

          return `
            <div class="notif-item">
              <div class="notif-icon"><i class="bi bi-cart-dash-fill"></i></div>
              <div class="notif-body">
                <p class="notif-msg">
                  <strong>${userType} ${escHtml(fullName)}</strong> reduced
                  <span class="item-highlight">${escHtml(n.item_name)}</span>
                  stock by <strong>${qty} unit${qty !== 1 ? 's' : ''}</strong>
                  because a customer bought it.
                </p>
                <span class="notif-time"><i class="bi bi-clock"></i> ${timeStr}</span>
              </div>
            </div>`;
        }).join('');
      })
      .catch(() => {
        list.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Server error.</div>';
      });
  }

  function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
  }

  function formatTime(ts) {
    if (!ts) return '—';
    const d = new Date(ts.replace(' ', 'T'));
    if (isNaN(d)) return ts;
    return d.toLocaleString('en-PH', {
      month: 'short', day: 'numeric', year: 'numeric',
      hour: '2-digit', minute: '2-digit', hour12: true
    });
  }

  // Close panel on outside click
  document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('notifWrapper');
    if (notifPanelOpen && !wrapper.contains(e.target)) {
      notifPanelOpen = false;
      document.getElementById('notifPanel').classList.remove('open');
    }
  });

  // ── Search ────────────────────────────────────────────────────────────────────
  function searchProducts() {
    const query = document.querySelector('.search-input').value.trim().toLowerCase();
    document.querySelectorAll('#inventoryBody tr').forEach(function(row) {
      const name = row.getAttribute('data-name').toLowerCase();
      const cat  = row.getAttribute('data-category').toLowerCase();
      const id   = row.getAttribute('data-id').toLowerCase();
      const desc = (row.getAttribute('data-description') || '').toLowerCase();
      row.style.display = (name.includes(query) || cat.includes(query) || id.includes(query) || desc.includes(query)) ? '' : 'none';
    });
  }

  document.querySelector('.search-btn').addEventListener('click', searchProducts);
  document.querySelector('.search-input').addEventListener('input', searchProducts);
  document.querySelector('.search-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') searchProducts();
  });

  document.getElementById('soldModal').addEventListener('click', function(e) {
    if (e.target === this) closeSoldModal();
  });
</script>
</body>
</html>