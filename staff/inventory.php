<?php
session_start();
require_once '../login/connection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {

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

            // Record a transaction row for every stock deduction (customer purchase)
            if ($change < 0) {
                $qtySold  = abs($change);
                $itemName = $row['item_name'];
                $price    = (float)$row['price'];
                $total    = $price * $qtySold;
                $staffId  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

                // Generate receipt number: INV-YYYYMMDD-itemId-rand
                $receiptNo = 'INV-' . date('Ymd') . '-' . $id . '-' . rand(100, 999);

                $pdo->prepare("
                    INSERT INTO transactions
                        (receipt_number, customer_type, customer_name, amount,
                         payment_method, staff_id, transaction_date, status, `desc`)
                    VALUES
                        (:rn, 'inventory', :cname, :amount,
                         'Inventory Deduction', :staff, CURRENT_TIMESTAMP, 'completed', :desc)
                ")->execute([
                    ':rn'     => $receiptNo,
                    ':cname'  => 'Customer Purchase',
                    ':amount' => $total,
                    ':staff'  => $staffId,
                    ':desc'   => "Sold {$qtySold}x {$itemName}",
                ]);
            }

            echo json_encode(['success' => true, 'row' => $row]);
            exit;
        }

        // ── Fetch notification history from transactions + low-stock inventory
        if ($action === 'get_notifications') {

            // Recent transactions (latest 50)
            $txRows = $pdo->query("
                SELECT
                    id, receipt_number, customer_type, user_id,
                    customer_name, amount, payment_method, staff_id,
                    transaction_date, status, created_at, `desc`
                FROM transactions
                ORDER BY transaction_date DESC, created_at DESC
                LIMIT 50
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Low / out-of-stock items
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

// ── Load inventory rows ───────────────────────────────────────────────────────
try {
    $rows = $pdo->query("SELECT * FROM inventory ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
}

// ── Badge: count recent transactions + low-stock items ───────────────────────
try {
    $txCount    = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $stockCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 10")->fetchColumn();
    $notifCount = $txCount + $stockCount;
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
  <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ── Modals ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.7); z-index: 1000;
      justify-content: center; align-items: center;
      backdrop-filter: blur(4px);
    }
    .modal-overlay.active { display: flex; }

    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-top: 2px solid var(--hazard);
      padding: 30px;
      width: 440px; max-width: 95%;
      position: relative;
      max-height: 90vh; overflow-y: auto;
    }

    .modal-box h3 {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 16px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1px;
      color: var(--text-primary); margin-bottom: 4px;
    }

    .modal-product-id {
      font-size: 11px; color: var(--text-muted);
      margin-bottom: 20px; display: block;
      font-family: 'Courier New', monospace;
    }

    .modal-close {
      position: absolute; top: 14px; right: 16px;
      background: none; border: none;
      font-size: 18px; cursor: pointer;
      color: var(--text-muted); transition: color 0.2s;
    }
    .modal-close:hover { color: var(--hazard); }

    .modal-field { margin-bottom: 16px; }
    .modal-field label {
      display: block; font-size: 10.5px; font-weight: 700;
      color: var(--text-muted); margin-bottom: 7px;
      text-transform: uppercase; letter-spacing: 0.8px;
    }
    .modal-field input,
    .modal-field textarea {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border);
      background: var(--bg-surface);
      color: var(--text-primary);
      font-family: 'DM Sans', sans-serif; font-size: 13.5px;
      box-sizing: border-box; transition: border-color 0.2s;
    }
    .modal-field input:focus,
    .modal-field textarea:focus {
      outline: none; border-color: var(--hazard);
      box-shadow: 0 0 0 1px var(--hazard);
    }

    .current-stock-info {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-left: 3px solid var(--hazard);
      padding: 12px 16px;
      font-size: 13px; color: var(--text-muted);
      margin-bottom: 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .current-stock-info strong {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 28px; color: var(--text-primary);
    }

    .modal-meta {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      padding: 10px 14px; font-size: 11px; color: var(--text-muted);
      margin-bottom: 16px;
      display: flex; justify-content: space-between; gap: 10px;
    }
    .modal-meta span { display: flex; flex-direction: column; gap: 3px; }
    .modal-meta strong { font-size: 10.5px; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }

    .modal-save-btn {
      width: 100%; padding: 13px;
      background: var(--hazard); color: #000;
      border: none;
      font-family: 'Chakra Petch', sans-serif;
      font-size: 13px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1px;
      cursor: pointer; transition: all 0.2s; margin-top: 4px;
    }
    .modal-save-btn:hover { background: #e6b800; box-shadow: 0 0 18px rgba(255,204,0,0.4); }
    .modal-save-btn:disabled { background: #444; color: #777; cursor: not-allowed; box-shadow: none; }

    .sold-label {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,71,87,0.12);
      border: 1px solid rgba(255,71,87,0.3);
      color: var(--danger);
      font-size: 10.5px; font-weight: 700;
      padding: 4px 12px; margin-bottom: 18px;
      letter-spacing: 0.5px; text-transform: uppercase;
      font-family: 'Chakra Petch', sans-serif;
    }

    /* ── Toast ── */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: var(--bg-card);
      border: 1px solid var(--border);
      color: var(--text-primary);
      padding: 13px 22px;
      font-family: 'DM Sans', sans-serif; font-size: 13px;
      opacity: 0; transform: translateY(10px);
      transition: all 0.3s; z-index: 9999; pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { border-left: 3px solid var(--success); }
    .toast.error   { border-left: 3px solid var(--danger); }

    .add-btn { display: none !important; }
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
      <li class="active" onclick="window.location.href='inventory.php'" style="cursor:pointer;">
        <i class="bi bi-box-seam"></i><span>Inventory</span>
      </li>
      <li onclick="window.location.href='attendance.php'" style="cursor:pointer;">
        <i class="bi bi-clipboard-check"></i><span>Attendance</span>
      </li>
      <li onclick="window.location.href='members.php'" style="cursor:pointer;">
        <i class="bi bi-people"></i><span>Members</span>
      </li>
      <li onclick="window.location.href='logout.php'" style="cursor:pointer;">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </li>
    </ul>
  </aside>

  <main class="main-content">

    <!-- TOPBAR -->
    <div class="topbar">
      <div class="topbar-left">
        <h1>Inventory Module</h1>
        <p>Fit-Stop Gym — Product &amp; Stock Management</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-badge">
          <div class="topbar-dot"></div>
          Active Staff Member
        </div>

        <!-- NOTIFICATION BELL -->
        <div class="notif-wrapper" id="notifWrapper">
          <button class="notif-bell-btn" onclick="toggleNotifPanel()" title="Notification History">
            <i class="bi bi-bell-fill"></i>
            <span class="notif-badge <?= $notifCount === 0 ? 'hidden' : '' ?>" id="notifBadge">
              <?= $notifCount > 99 ? '99+' : $notifCount ?>
            </span>
          </button>
          <div class="notif-panel" id="notifPanel">
            <div class="notif-panel-header">
              <h4><i class="bi bi-bell" style="margin-right:6px;"></i>Notification History</h4>
              <span id="notifPanelCount">—</span>
            </div>
            <div class="notif-list" id="notifList">
              <div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- PROFILE CONTAINER -->
    <div class="profile-container" style="margin-bottom:28px;">
      <div class="profile-content">
        <div class="profile-text">
          <strong class="profile-name">Inventory Module</strong>
          <span class="profile-streak">🏋️ Product &amp; Stock Management</span>
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
              <td style="color:var(--text-muted);font-family:monospace;"><?= $row['id'] ?></td>
              <td style="color:var(--text-primary);font-weight:600;"><?= htmlspecialchars($row['item_name']) ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              <td class="qty-cell"><?= $qty ?></td>
              <td><?= $priceFormatted ?></td>
              <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['description'] ?? '—') ?></td>
              <td class="created-cell" style="font-size:12px;"><?= $row['created_at'] ?></td>
              <td class="updated-cell" style="font-size:12px;"><?= $row['updated_at'] ?></td>
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
      <label>Units sold <span style="color:var(--danger)">*</span></label>
      <input type="number" id="soldQtyInput" min="1" value="1" placeholder="Enter quantity sold" class="form-input">
    </div>

    <div class="modal-field">
      <label>Note <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
      <input type="text" id="soldNote" placeholder="e.g. Walk-in customer, member purchase..." class="form-input">
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
  if (!qtyInput || qtyInput < 1) { alert('Please enter a valid quantity (at least 1).'); return; }

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

        // Force reload on next panel open + bump badge
        notifLoaded = false;
        const badge = document.getElementById('notifBadge');
        const cur   = parseInt(badge.textContent) || 0;
        badge.textContent = cur + 1;
        badge.classList.remove('hidden');

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
function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

function formatTs(ts) {
  if (!ts) return '—';
  const d = new Date(ts.replace(' ', 'T'));
  if (isNaN(d)) return ts;
  return d.toLocaleString('en-PH', {
    month:'short', day:'numeric', year:'numeric',
    hour:'2-digit', minute:'2-digit', hour12:true
  });
}

function toggleNotifPanel() {
  notifPanelOpen = !notifPanelOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifPanelOpen);
  if (notifPanelOpen && !notifLoaded) loadNotifications();
}

document.addEventListener('click', function(e) {
  const wrapper = document.getElementById('notifWrapper');
  if (notifPanelOpen && wrapper && !wrapper.contains(e.target)) {
    notifPanelOpen = false;
    document.getElementById('notifPanel').classList.remove('open');
  }
});

function loadNotifications() {
  const list  = document.getElementById('notifList');
  const count = document.getElementById('notifPanelCount');
  list.innerHTML = '<div class="notif-loader"><i class="bi bi-hourglass-split"></i> Loading...</div>';

  const fd = new FormData();
  fd.append('action', 'get_notifications');

  fetch('inventory.php', { method: 'POST', body: fd })
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

      // ── Transaction rows ──────────────────────────────────────────────────
      const txHtml = transactions.length ? `
        <div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">
          Recent Transactions
        </div>` + transactions.map(t => {
          const who    = t.customer_name || (t.customer_type === 'member' ? 'Member #' + (t.user_id||'?') : 'Walk-In');
          const amount = parseFloat(t.amount || 0).toFixed(2);
          const method = t.payment_method || '—';
          const status = t.status || 'paid';
          const ts     = formatTs(t.transaction_date || t.created_at);
          const note   = t.desc || '';
          return `
            <div class="notif-item">
              <div class="notif-icon" style="background:rgba(34,208,122,0.12);border-color:rgba(34,208,122,0.3);color:var(--success);">
                <i class="bi bi-receipt"></i>
              </div>
              <div class="notif-body">
                <p class="notif-msg">
                  <strong>${escHtml(t.receipt_number || 'Receipt')}</strong><br>
                  <span class="item-highlight">₱${escHtml(amount)}</span>
                  · ${escHtml(who)}
                  · ${escHtml(method)}
                  ${note ? '· ' + escHtml(note) : ''}
                  · <span style="text-transform:capitalize;">${escHtml(status)}</span>
                </p>
                <span class="notif-time"><i class="bi bi-clock"></i> ${ts}</span>
              </div>
            </div>`;
        }).join('') : '';

      // ── Low-stock rows ────────────────────────────────────────────────────
      const stockHtml = lowStock.length ? `
        <div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">
          Inventory Alerts
        </div>` + lowStock.map(i => {
          const qty  = parseInt(i.quantity);
          const ts   = formatTs(i.updated_at);
          const iconC  = qty === 0 ? 'var(--danger)'  : 'var(--warning)';
          const bgC    = qty === 0 ? 'rgba(255,71,87,0.12)'  : 'rgba(255,159,67,0.12)';
          const bdC    = qty === 0 ? 'rgba(255,71,87,0.3)'   : 'rgba(255,159,67,0.3)';
          const icon   = qty === 0 ? 'bi-x-circle-fill'      : 'bi-exclamation-triangle-fill';
          const label  = qty === 0
            ? '<span style="color:var(--danger);font-weight:700;">OUT OF STOCK</span>'
            : '<span style="color:var(--warning);font-weight:700;">Low Stock</span>';
          return `
            <div class="notif-item">
              <div class="notif-icon" style="background:${bgC};border-color:${bdC};color:${iconC};">
                <i class="bi ${icon}"></i>
              </div>
              <div class="notif-body">
                <p class="notif-msg">
                  <strong>${escHtml(i.item_name)}</strong> — ${escHtml(i.category)}<br>
                  Stock: <span class="item-highlight">${qty} unit${qty !== 1 ? 's' : ''}</span> — ${label}
                </p>
                <span class="notif-time"><i class="bi bi-clock"></i> Updated: ${ts}</span>
              </div>
            </div>`;
        }).join('') : '';

      list.innerHTML = txHtml + stockHtml;
    })
    .catch(() => {
      list.innerHTML = '<div class="notif-empty"><i class="bi bi-wifi-off"></i>Server error.</div>';
    });
}

// ── Search ────────────────────────────────────────────────────────────────────
function searchProducts() {
  const q = document.querySelector('.search-input').value.trim().toLowerCase();
  document.querySelectorAll('#inventoryBody tr').forEach(row => {
    const match = ['data-name','data-category','data-id','data-description']
      .some(attr => (row.getAttribute(attr) || '').toLowerCase().includes(q));
    row.style.display = match ? '' : 'none';
  });
}

document.querySelector('.search-btn').addEventListener('click', searchProducts);
document.querySelector('.search-input').addEventListener('input', searchProducts);
document.querySelector('.search-input').addEventListener('keydown', e => { if (e.key === 'Enter') searchProducts(); });

document.getElementById('soldModal').addEventListener('click', function(e) {
  if (e.target === this) closeSoldModal();
});
</script>

</body>
</html>