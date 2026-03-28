<?php
session_start();
require_once __DIR__ . '/../includes/security.php';
require_once '../login/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
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
            if ($change < 0) {
                $qtySold   = abs($change);
                $itemName  = $row['item_name'];
                $price     = (float)$row['price'];
                $total     = $price * $qtySold;
                $staffId   = isset($_SESSION['id']) ? (int)$_SESSION['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
                $receiptNo = 'INV-' . date('Ymd') . '-' . $id . '-' . rand(100, 999);
                $pdo->prepare("
                    INSERT INTO transactions
                        (receipt_number, customer_type, customer_name, amount,
                         payment_method, staff_id, transaction_date, status, `desc`)
                    VALUES (:rn, 'inventory', 'Customer Purchase', :amount,
                            'Inventory Deduction', :staff, CURRENT_TIMESTAMP, 'completed', :desc)
                ")->execute([
                    ':rn'     => $receiptNo,
                    ':amount' => $total,
                    ':staff'  => $staffId,
                    ':desc'   => "Sold {$qtySold}x {$itemName}",
                ]);
            }
            echo json_encode(['success' => true, 'row' => $row]);
            exit;
        }

        if ($action === 'submit_sales_form') {
            $staffId   = isset($_SESSION['id']) ? (int)$_SESSION['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null);
            $saleDate  = $_POST['sale_date'] ?? date('Y-m-d');
            $lessWater = (float)($_POST['less_water'] ?? 0);

            if ($lessWater < 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid water expense']);
                exit;
            }

            $entryTotal = 0;
            $itemsTotal = 0;
            $grandTotal = 0 - $lessWater;

            if ($lessWater > 0) {
                $receiptNo = 'EXP-' . date('Ymd') . '-' . rand(1000, 9999);
                $pdo->prepare("INSERT INTO transactions
                        (receipt_number, customer_type, customer_name, amount,
                         payment_method, staff_id, transaction_date, status, `desc`)
                    VALUES (:rn, 'expense', 'Water Expense', :amt,
                            'Cash', :si, CURRENT_TIMESTAMP, 'completed', :desc)")
                ->execute([
                    ':rn'   => $receiptNo,
                    ':amt'  => $lessWater,
                    ':si'   => $staffId,
                    ':desc' => 'Less Expenses (Water) for ' . $saleDate,
                ]);
            }

            // Process sold items from the logsheet (manual add qty entries)
            $updatedRows = [];
            $soldItemsJson = $_POST['sold_items'] ?? '[]';
            $soldItems = json_decode($soldItemsJson, true);
            if (is_array($soldItems)) {
                foreach ($soldItems as $item) {
                    $itemId  = (int)($item['id']  ?? 0);
                    $itemQty = (int)($item['qty'] ?? 0);
                    if ($itemId <= 0 || $itemQty <= 0) continue;

                    $cur    = (int)$pdo->query("SELECT quantity FROM inventory WHERE id = $itemId")->fetchColumn();
                    $newQty = max(0, $cur - $itemQty);
                    $pdo->prepare("UPDATE inventory SET quantity = :qty, updated_at = CURRENT_TIMESTAMP WHERE id = :id")
                        ->execute([':qty' => $newQty, ':id' => $itemId]);

                    $updRow = $pdo->query("SELECT * FROM inventory WHERE id = $itemId")->fetch(PDO::FETCH_ASSOC);
                    $updatedRows[] = $updRow;

                    $itemPrice  = (float)($item['price'] ?? 0);
                    $itemTotal  = $itemPrice * $itemQty;
                    $itemsTotal += $itemTotal;
                    $grandTotal += $itemTotal;

                    $receiptNo = 'INV-' . date('Ymd') . '-' . $itemId . '-' . rand(100, 999);
                    $pdo->prepare("INSERT INTO transactions
                            (receipt_number, customer_type, customer_name, amount,
                             payment_method, staff_id, transaction_date, status, `desc`)
                        VALUES (:rn, 'inventory', 'Customer Purchase', :amount,
                                'Cash', :staff, CURRENT_TIMESTAMP, 'completed', :desc)")
                    ->execute([
                        ':rn'     => $receiptNo,
                        ':amount' => $itemTotal,
                        ':staff'  => $staffId,
                        ':desc'   => "Logsheet: Sold {$itemQty}x " . ($item['name'] ?? 'Item'),
                    ]);
                }
            }

            echo json_encode([
                'success'      => true,
                'entry_total'  => $entryTotal,
                'items_total'  => $itemsTotal,
                'grand_total'  => $grandTotal,
                'less_water'   => $lessWater,
                'updated_rows' => $updatedRows,
            ]);
            exit;
        }

        if ($action === 'get_sales_summary') {
    $today   = date('Y-m-d');
    $summary = [
        'non_member'    => 0,
        'member_walkin' => 0,
        'membership'    => 0,
        'special'       => 0,
        'monthly'       => 0,
        'total_revenue' => 0.0,
    ];

    // ... existing $stmt query stays the same ...

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $paidFor     = trim((string)$row['paid_for']);
        $cnt         = (int)$row['cnt'];
        $totalAmount = (float)$row['total_amount'];
        $summary['total_revenue'] += $totalAmount;

        if (strcasecmp($paidFor, 'Day Pass / Walk-In') === 0) {
            if (strtolower((string)($row['customer_type'] ?? '')) === 'non-member') {
                $summary['non_member'] += $cnt;
            } else {
                $summary['member_walkin'] += $cnt;  // member walk-in day pass
            }
        } elseif (strcasecmp($paidFor, 'Membership') === 0) {
            $summary['membership'] += $cnt;          // one-time membership fee
        } elseif (strcasecmp($paidFor, 'Special Rate') === 0) {
            $summary['special'] += $cnt;
        } elseif (strcasecmp($paidFor, 'Monthly') === 0) {
            $summary['monthly'] += $cnt;
        }
    }

    echo json_encode(['success' => true, 'summary' => $summary]);
    exit;
}
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
                FROM inventory WHERE quantity <= 10
                ORDER BY quantity ASC, updated_at DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'transactions' => $txRows, 'low_stock' => $stockRows]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
}

try {
    $rows = $pdo->query("SELECT * FROM inventory ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $rows = []; }

try {
    $txCount    = (int)$pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    $stockCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory WHERE quantity <= 10")->fetchColumn();
    $notifCount = $txCount + $stockCount;
} catch (Exception $e) { $notifCount = 0; }
// Pass raw counts to JS for read-tracking
$jsNotifTotal = $notifCount;
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
      padding: 30px; width: 440px; max-width: 95%;
      position: relative; max-height: 90vh; overflow-y: auto;
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
      background: none; border: none; font-size: 18px;
      cursor: pointer; color: var(--text-muted); transition: color 0.2s;
    }
    .modal-close:hover { color: var(--hazard); }
    .modal-field { margin-bottom: 16px; }
    .modal-field label {
      display: block; font-size: 10.5px; font-weight: 700;
      color: var(--text-muted); margin-bottom: 7px;
      text-transform: uppercase; letter-spacing: 0.8px;
    }
    .modal-field input, .modal-field textarea {
      width: 100%; padding: 11px 14px;
      border: 1px solid var(--border);
      background: var(--bg-surface); color: var(--text-primary);
      font-family: 'DM Sans', sans-serif; font-size: 13.5px;
      box-sizing: border-box; transition: border-color 0.2s;
    }
    .modal-field input:focus, .modal-field textarea:focus {
      outline: none; border-color: var(--hazard);
      box-shadow: 0 0 0 1px var(--hazard);
    }
    .current-stock-info {
      background: var(--bg-surface); border: 1px solid var(--border);
      border-left: 3px solid var(--hazard); padding: 12px 16px;
      font-size: 13px; color: var(--text-muted); margin-bottom: 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .current-stock-info strong {
      font-family: 'Chakra Petch', sans-serif;
      font-size: 28px; color: var(--text-primary);
    }
    .modal-meta {
      background: var(--bg-surface); border: 1px solid var(--border);
      padding: 10px 14px; font-size: 11px; color: var(--text-muted);
      margin-bottom: 16px; display: flex; justify-content: space-between; gap: 10px;
    }
    .modal-meta span { display: flex; flex-direction: column; gap: 3px; }
    .modal-meta strong { font-size: 10.5px; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }
    .modal-save-btn {
      width: 100%; padding: 13px; background: var(--hazard); color: #000;
      border: none; font-family: 'Chakra Petch', sans-serif;
      font-size: 13px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1px; cursor: pointer; transition: all 0.2s; margin-top: 4px;
    }
    .modal-save-btn:hover { background: #e6b800; box-shadow: 0 0 18px rgba(255,204,0,0.4); }
    .modal-save-btn:disabled { background: #444; color: #777; cursor: not-allowed; box-shadow: none; }
    .sold-label {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,71,87,0.12); border: 1px solid rgba(255,71,87,0.3);
      color: var(--danger); font-size: 10.5px; font-weight: 700;
      padding: 4px 12px; margin-bottom: 18px; letter-spacing: 0.5px;
      text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;
    }

    /* ── Sales Modal ── */
    .sales-modal-box {
      background: var(--bg-card); border: 1px solid var(--border);
      border-top: 2px solid var(--hazard); padding: 0;
      width: 720px; max-width: 96%; position: relative;
      max-height: 92vh; overflow-y: auto;
    }
    .sales-modal-header {
      padding: 18px 26px; border-bottom: 1px solid var(--border);
      display: flex; justify-content: space-between; align-items: center;
      position: sticky; top: 0; background: var(--bg-card); z-index: 10;
    }
    .sales-modal-header h3 {
      font-family: 'Chakra Petch', sans-serif; font-size: 14px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-primary); margin: 0;
    }
    .sales-modal-header .sub { font-size: 11px; color: var(--text-muted); font-family: 'Courier New', monospace; margin-top: 3px; }
    .sales-modal-body { padding: 22px 26px; }

    .sf-section { margin-bottom: 22px; }
    .sf-section-title {
      font-family: 'Chakra Petch', sans-serif; font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1.5px; color: var(--hazard);
      padding: 5px 0 8px; border-bottom: 1px solid var(--border);
      margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .sf-section-title.danger { color: var(--danger); }

    .sf-table { width: 100%; border-collapse: collapse; }
    .sf-table th {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.7px; color: var(--text-muted);
      padding: 6px 10px; border-bottom: 1px solid var(--border); text-align: left;
    }
    .sf-table th.r { text-align: right; }
    .sf-table td {
      padding: 7px 10px; font-size: 13px; color: var(--text-primary);
      border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle;
    }
    .sf-table tr:last-child td { border-bottom: none; }
    .sf-table .rate { color: var(--text-muted); font-family: 'Courier New', monospace; font-size: 12.5px; }
    .sf-table .tot {
      font-family: 'Chakra Petch', sans-serif; font-size: 13px;
      font-weight: 600; color: var(--hazard); text-align: right;
    }
    .sf-table .id-col { color: var(--text-muted); font-family: monospace; font-size: 11px; }

    .sf-input {
      background: var(--bg-surface); border: 1px solid var(--border);
      color: var(--text-primary); font-family: 'DM Sans', sans-serif;
      font-size: 13px; padding: 6px 10px; width: 76px; text-align: center;
      transition: border-color 0.2s;
    }
    .sf-input:focus { outline: none; border-color: var(--hazard); box-shadow: 0 0 0 1px var(--hazard); }
    .sf-input:disabled { opacity: 0.35; cursor: not-allowed; }
    .sf-input[readonly] { opacity: 0.75; cursor: default; pointer-events: none; }

    .sf-totals {
      background: var(--bg-surface); border: 1px solid var(--border);
      padding: 14px 18px; margin-bottom: 18px;
    }
    .sf-trow {
      display: flex; justify-content: space-between; align-items: center;
      padding: 5px 0; font-size: 13px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .sf-trow:last-child { border-bottom: none; }
    .sf-trow.grand {
      padding-top: 10px; margin-top: 4px;
      border-top: 1px solid var(--border); border-bottom: none;
    }
    .sf-trow .tl { color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .sf-trow .tv { font-family: 'Chakra Petch', sans-serif; font-size: 14px; font-weight: 600; color: var(--hazard); }
    .sf-trow.grand .tl { font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .sf-trow.grand .tv { font-size: 22px; }
    .sf-trow.exp .tv { color: var(--danger); }

    .sf-submit {
      width: 100%; padding: 14px; background: var(--hazard); color: #000;
      border: none; font-family: 'Chakra Petch', sans-serif;
      font-size: 13px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 1.2px; cursor: pointer; transition: all 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .sf-submit:hover { background: #e6b800; box-shadow: 0 0 20px rgba(255,204,0,0.35); }
    .sf-submit:disabled { background: #333; color: #666; cursor: not-allowed; box-shadow: none; }

    .sf-success {
      display: none; background: rgba(34,208,122,0.08);
      border: 1px solid rgba(34,208,122,0.25); border-left: 3px solid var(--success);
      padding: 13px 18px; margin-bottom: 18px;
      font-family: 'Chakra Petch', sans-serif; font-size: 13px; color: var(--success);
      align-items: center; gap: 10px;
    }
    .sf-success.show { display: flex; }

    .stock-oos { opacity: 0.45; }

    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: var(--bg-card); border: 1px solid var(--border);
      color: var(--text-primary); padding: 13px 22px;
      font-family: 'DM Sans', sans-serif; font-size: 13px;
      opacity: 0; transform: translateY(10px);
      transition: all 0.3s; z-index: 9999; pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { border-left: 3px solid var(--success); }
    .toast.error   { border-left: 3px solid var(--danger);  }

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
  <li onclick="window.location.href='staff.php#clientRegistration'" style="cursor:pointer;">
    <i class="bi bi-person-plus"></i><span>Client Registration</span>
  </li>
  <li class="active" style="cursor:pointer;">
    <i class="bi bi-box-seam"></i><span>Inventory</span>
  </li>
  <li onclick="window.location.href='staff.php#attendance'" style="cursor:pointer;">
    <i class="bi bi-clipboard-check"></i><span>Attendance</span>
  </li>
  <li onclick="window.location.href='staff.php#memberManagement'" style="cursor:pointer;">
    <i class="bi bi-people"></i><span>Members</span>
  </li>
  <li id="monthlyBtn" onclick="window.location.href='monthly.php'" style="cursor:pointer;">
        <i class="bi bi-calendar-check"></i>
        <span>Monthly Access</span>
  </li>
  <li onclick="window.location.href='walkin_attendance.php'" style="cursor:pointer;">
      <i class="bi bi-person-walking"></i>
      <span>Walk-In Log</span>
      </li>
  <li onclick="window.location.href='staff.php#settings'" style="cursor:pointer;">
    <i class="bi bi-gear"></i><span>Settings</span>
  </li>
  <li onclick="document.getElementById('logoutForm').submit()" style="cursor:pointer;">
    <i class="bi bi-box-arrow-right"></i><span>Logout</span>
  </li>
  <form id="logoutForm" action="../../login/logout.php" method="POST" style="display:none;">
    <?php echo fitstop_csrf_input(); ?>
  </form>
</ul>
  </aside>

  <main class="main-content">

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
        <button class="notif-bell-btn" onclick="openSalesModal()" title="Daily Sales / Counter Logsheet"
          style="margin-right:6px;position:relative;">
          <i class="bi bi-receipt"></i>
        </button>

        <div class="notif-wrapper" id="notifWrapper">
          <button class="notif-bell-btn" onclick="toggleNotifPanel()" title="Notification History">
            <i class="bi bi-bell-fill"></i>
          <span class="notif-badge hidden" id="notifBadge">0</span>
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
              <th>ID</th><th>Item Name</th><th>Category</th><th>Quantity</th>
              <th>Price</th><th>Description</th><th>Created At</th><th>Updated At</th>
              <th>Status</th>
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
            <tr data-id="<?= $row['id'] ?>"
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
      <input type="number" id="soldQtyInput" min="1" value="1" placeholder="Enter quantity sold">
    </div>
    <div class="modal-field">
      <label>Note <span style="font-weight:400;color:var(--text-muted);">(optional)</span></label>
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

<!-- ===== DAILY SALES / COUNTER LOGSHEET MODAL ===== -->
<div class="modal-overlay" id="salesModal">
  <div class="sales-modal-box">
    <div class="sales-modal-header">
      <div>
        <h3><i class="bi bi-receipt" style="color:var(--hazard);margin-right:8px;"></i>Daily Counter Logsheet</h3>
        <div class="sub">Items are pulled from inventory · Sales auto-recorded to transaction log</div>
      </div>
      <button onclick="closeSalesModal()"
        style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--text-muted);">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="sales-modal-body">

      <div class="sf-success" id="sfSuccess">
        <i class="bi bi-check-circle-fill"></i>
        <span id="sfSuccessMsg">Recorded!</span>
      </div>

      <!-- Reset tally button -->
      <div style="display:flex;justify-content:flex-end;margin-bottom:14px;">
        <button onclick="resetTally()"
          style="background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);
                 color:var(--danger);font-family:'Chakra Petch',sans-serif;
                 font-size:10.5px;font-weight:700;text-transform:uppercase;
                 letter-spacing:0.8px;padding:5px 14px;cursor:pointer;">
          <i class="bi bi-arrow-counterclockwise"></i> Reset Today's Tally
        </button>
      </div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <label style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:var(--text-muted);white-space:nowrap;">
          Sale Date
        </label>
        <input type="date" id="sfDate" value="<?= date('Y-m-d') ?>"
          style="background:var(--bg-surface);border:1px solid var(--border);color:var(--text-primary);
                 font-family:'DM Sans',sans-serif;font-size:13px;padding:7px 12px;">
      </div>

      <!-- ENTRY FEES (fixed) -->
      <div class="sf-section">
        <div class="sf-section-title"><i class="bi bi-person-badge"></i> Entry Fees</div>
        <table class="sf-table">
          <thead>
            <tr><th>Category</th><th>No. of Persons</th><th>Amount</th><th class="r">This Entry</th><th class="r" style="color:var(--hazard);">Today's Tally</th></tr>
          </thead>
          <tbody>
            <tr>
              <td>Non-Member</td>
              <td><input type="number" class="sf-input sf-entry" data-rate="60" id="sfNonMember" min="0" value="0" readonly></td>
              <td class="rate">&#8369;60</td>
              <td class="tot" id="sfNonMemberTot">&#8369;0.00</td>
              <td class="tot sf-entry-tally" id="sfNonMemberTally" data-tally="0">—</td>
            </tr>
            <!-- Member(Walk-in) row — unchanged -->
            <tr>
              <td>Member(Walk-in)</td>
              <td><input type="number" class="sf-input sf-entry" data-rate="50" id="sfMemberWalkin" min="0" value="0" readonly></td>
              <td class="rate">&#8369;50</td>
              <td class="tot" id="sfMemberWalkinTot">&#8369;0.00</td>
              <td class="tot sf-entry-tally" id="sfMemberWalkinTally" data-tally="0">—</td>
            </tr>

            <!-- Membership row — now has its own unique ID -->
            <tr>
              <td>Membership</td>
              <td><input type="number" class="sf-input sf-entry" data-rate="500" id="sfMembership" min="0" value="0" readonly></td>
              <td class="rate">&#8369;500</td>
              <td class="tot" id="sfMembershipTot">&#8369;0.00</td>
              <td class="tot sf-entry-tally" id="sfMembershipTally" data-tally="0">—</td>
            </tr>
            <tr>
              <td>Special Rate</td>
              <td><input type="number" class="sf-input sf-entry" data-rate="40" id="sfSpecial" min="0" value="0" readonly></td>
              <td class="rate">&#8369;40</td>
              <td class="tot" id="sfSpecialTot">&#8369;0.00</td>
              <td class="tot sf-entry-tally" id="sfSpecialTally" data-tally="0">—</td>
            </tr>
            <tr>
              <td>Monthly</td>
              <td><input type="number" class="sf-input sf-entry" data-rate="0" id="sfMonthly" min="0" value="0" readonly></td>
              <td class="rate">&#8369;650/750</td>
              <td class="tot" id="sfMonthlyTot">&#8369;0.00</td>
              <td class="tot sf-entry-tally" id="sfMonthlyTally" data-tally="0" data-total="0">—</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ITEMS (dynamic from DB) -->
      <div class="sf-section">
        <div class="sf-section-title"><i class="bi bi-box-seam"></i> Items Sold — from Inventory</div>
        <table class="sf-table">
          <thead>
            <tr><th>ID</th><th>Item</th><th>In Stock</th><th>Add Qty</th><th>Price</th><th class="r">This Entry</th><th class="r" style="color:var(--hazard);">Today's Tally</th></tr>
          </thead>
          <tbody id="sfItemsBody">
            <?php foreach ($rows as $item):
              $stock = (int)$item['quantity'];
              $oos   = $stock === 0;
              $scls  = $oos ? 'status-badge inactive' : ($stock <= 10 ? 'status-badge low-stock' : 'status-badge active');
            ?>
            <tr class="<?= $oos ? 'stock-oos' : '' ?>"
                data-inv-id="<?= $item['id'] ?>"
                data-price="<?= (float)$item['price'] ?>"
                data-stock="<?= $stock ?>"
                data-name="<?= htmlspecialchars($item['item_name']) ?>"
                data-tally="0">
              <td class="id-col"><?= $item['id'] ?></td>
              <td style="font-weight:600;color:var(--text-primary);">
                <?= htmlspecialchars($item['item_name']) ?>
                <span style="font-size:10.5px;color:var(--text-muted);font-weight:400;margin-left:4px;">
                  <?= htmlspecialchars($item['category']) ?>
                </span>
              </td>
              <td><span class="<?= $scls ?> sf-stock-badge" style="font-size:10px;padding:2px 7px;"><?= $stock ?></span></td>
              <td>
                  <input type="number" class="sf-input sf-item-qty"
                    min="0" max="<?= $stock ?>" value="0"
                    readonly
                    <?= $oos ? 'disabled' : '' ?>>
              </td>
              <td class="rate">&#8369;<?= number_format((float)$item['price'], 2) ?></td>
              <td class="tot sf-item-tot">&#8369;0.00</td>
              <td class="tot sf-item-tally" style="color:var(--hazard);font-size:13px;">—</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No inventory items.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- LESS EXPENSES -->
      <div class="sf-section">
        <div class="sf-section-title danger"><i class="bi bi-dash-circle"></i> Less Expenses</div>
        <table class="sf-table">
          <thead><tr><th>Description</th><th>Amount (&#8369;)</th><th></th><th></th><th></th><th></th><th></th></tr></thead>
          <tbody>
            <tr>
              <td>Water</td>
              <td><input type="number" class="sf-input" id="sfWater" min="0" value="0" step="0.01" style="width:100px;"></td>
              <td colspan="4"></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- TOTALS -->
      <div class="sf-totals">
        <div class="sf-trow">
          <span class="tl">Entry Fees Total</span>
          <span class="tv" id="sfEntryTot">&#8369;0.00</span>
        </div>
        <div class="sf-trow">
          <span class="tl">Items Total</span>
          <span class="tv" id="sfItemsTot">&#8369;0.00</span>
        </div>
        <div class="sf-trow exp">
          <span class="tl">Less Expenses (Water)</span>
          <span class="tv" id="sfExpTot">&#8369;0.00</span>
        </div>
        <div class="sf-trow grand">
          <span class="tl">Grand Total</span>
          <span class="tv" id="sfGrandTot">&#8369;0.00</span>
        </div>
      </div>

      <button class="sf-submit" id="sfSubmitBtn" onclick="submitSalesForm()">
        <i class="bi bi-check-circle-fill"></i> Submit & Record to Log
      </button>

    </div>
  </div>
</div>

<script>
let currentRow     = null;
let notifPanelOpen = false;
let notifLoaded    = false;
const csrfToken    = <?php echo json_encode(fitstop_csrf_token()); ?>;

function fmt(n) {
  return '&#8369;' + parseFloat(n || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = 'toast ' + type + ' show';
  setTimeout(() => { t.className = 'toast'; }, 3500);
}

function getStatus(qty) {
  qty = parseInt(qty);
  if (qty === 0)  return { cls: 'inactive',  txt: 'Out of Stock' };
  if (qty <= 10)  return { cls: 'low-stock', txt: 'Low Stock'    };
  return                 { cls: 'active',    txt: 'Available'    };
}

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
  if (currentQty - qtyInput < 0) { alert('Cannot sell more than current stock: ' + currentQty); return; }

  const btn = document.getElementById('soldSaveBtn');
  btn.disabled  = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

  const fd = new FormData();
  fd.append('action',     'update_stock');
  fd.append('id',         id);
  fd.append('change',     -qtyInput);
  fd.append('csrf_token', csrfToken);

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
        currentRow.setAttribute('data-qty', newQty);
        currentRow.setAttribute('data-updated', row.updated_at);

        const itemId  = currentRow.getAttribute('data-id');
        const sfRow   = document.querySelector(`#sfItemsBody tr[data-inv-id="${itemId}"]`);
        if (sfRow) {
          const price     = parseFloat(sfRow.dataset.price) || 0;
          const prevTally = parseInt(sfRow.dataset.tally) || 0;
          const newTally  = prevTally + qtyInput;
          sfRow.dataset.tally = newTally;
          sfRow.dataset.stock = newQty;

          const tallyCell = sfRow.querySelector('.sf-item-tally');
          if (tallyCell) {
            tallyCell.innerHTML = `<span style="font-weight:700;">${newTally}</span>
              <span style="font-size:10.5px;color:var(--text-muted);display:block;">${fmt(newTally * price)}</span>`;
          }
          const stockBadge = sfRow.querySelector('.sf-stock-badge');
          if (stockBadge) {
            stockBadge.textContent = newQty;
            stockBadge.className   = 'sf-stock-badge status-badge ' + status.cls;
            stockBadge.style.cssText = 'font-size:10px;padding:2px 7px;';
          }
          const qtyInp = sfRow.querySelector('.sf-item-qty');
          if (qtyInp) { qtyInp.max = newQty; qtyInp.disabled = newQty === 0; }
          if (newQty === 0) sfRow.classList.add('stock-oos');
          else sfRow.classList.remove('stock-oos');
        }

        syncTallyToLocalStorage();

        notifLoaded = false;
        const badge = document.getElementById('notifBadge');
        badge.textContent = (parseInt(badge.textContent) || 0) + 1;
        badge.classList.remove('hidden');
        showToast('Sale recorded! Stock updated to ' + newQty + '.', 'success');
        closeSoldModal();
      } else {
        alert(data.message || 'Could not update stock.');
      }
    })
    .catch(() => alert('Server error. Please try again.'))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm Sale'; });
}

function openSalesModal() {
  sfRecalc();
  applyLocalStorageTally();   
  document.getElementById('sfSuccess').classList.remove('show');
  document.getElementById('salesModal').classList.add('active');
}
function closeSalesModal() {
  document.getElementById('salesModal').classList.remove('active');
}

function sfRecalc() {
 let entryTotal = 0;
  document.querySelectorAll('.sf-entry').forEach(inp => {
    const count = parseInt(inp.value) || 0;
    const rate  = parseFloat(inp.dataset.rate) || 0;
    let tot;

    if (inp.id === 'sfMonthly') {
      const tallyCell = document.getElementById('sfMonthlyTally');
      tot = parseFloat(tallyCell?.dataset.total || 0);
    } else {
      tot = count * rate;
    }

    entryTotal += tot;
    const el = document.getElementById(inp.id + 'Tot');
    if (el) el.innerHTML = fmt(tot);
  });
  document.getElementById('sfEntryTot').innerHTML = fmt(entryTotal);

  let itemsTotal = 0;
  document.querySelectorAll('#sfItemsBody tr[data-inv-id]').forEach(row => {
    const inp  = row.querySelector('.sf-item-qty');
    const cell = row.querySelector('.sf-item-tot');
    if (!inp || !cell) return;
    const qty   = parseInt(inp.value) || 0;
    const price = parseFloat(row.dataset.price) || 0;
    const tot   = qty * price;
    itemsTotal += tot;
    cell.innerHTML = fmt(tot);
  });
  document.getElementById('sfItemsTot').innerHTML = fmt(itemsTotal);

  const water = parseFloat(document.getElementById('sfWater').value) || 0;
  document.getElementById('sfExpTot').innerHTML   = fmt(water);
  document.getElementById('sfGrandTot').innerHTML = fmt(entryTotal + itemsTotal - water);
}


function applyLocalStorageTally() {
  const TALLY_KEY = 'fitstop_inv_tally';
  const today     = new Date().toISOString().slice(0, 10);
  let tally = {};
  try { tally = JSON.parse(localStorage.getItem(TALLY_KEY) || '{}'); } catch(e) {}

  if (tally._date !== today) return;

  document.querySelectorAll('#sfItemsBody tr[data-inv-id]').forEach(row => {
    const invId    = row.dataset.invId;
    const tallyQty = parseInt(tally[invId]) || 0;
    if (tallyQty <= 0) return;

    const price      = parseFloat(row.dataset.price) || 0;
    const tallyCell  = row.querySelector('.sf-item-tally');
    const prevStored = parseInt(row.dataset.tally) || 0;

    if (tallyQty > prevStored) {
      row.dataset.tally = tallyQty;

      if (tallyCell) {
        tallyCell.innerHTML = `<span style="font-weight:700;">${tallyQty}</span>
          <span style="font-size:10.5px;color:var(--text-muted);display:block;">${fmt(tallyQty * price)}</span>`;
      }

      const qtyInput = row.querySelector('.sf-item-qty');
      if (qtyInput && !qtyInput.disabled) {
        const addedSinceLastOpen = tallyQty - prevStored;
        const currentVal = parseInt(qtyInput.value) || 0;
        qtyInput.value = currentVal + addedSinceLastOpen;
      }
    }
  });
 const ENTRY_KEY = 'fitstop_entry_tally';
  let entryTally = {};
  try { entryTally = JSON.parse(localStorage.getItem(ENTRY_KEY) || '{}'); } catch(e) {}

  if (entryTally._date === today) {
      const entryMap = {
      non_member    : 'sfNonMember',
      member_walkin : 'sfMemberWalkin',
      membership    : 'sfMembership',
      special       : 'sfSpecial',
      monthly       : 'sfMonthly',
    };
    Object.entries(entryMap).forEach(([key, inputId]) => {
      const inp       = document.getElementById(inputId);
      const tallyCell = document.getElementById(inputId + 'Tally');
      if (!inp) return;

      const newCount  = parseInt(entryTally[key]) || 0;
      const prevCount = parseInt(inp.dataset.lastTally || 0);

      if (newCount > prevCount) {
        inp.value             = newCount;
        inp.dataset.lastTally = newCount;

        if (tallyCell) {
          const rate    = parseFloat(inp.dataset.rate) || 0;
          const prev    = parseInt(tallyCell.dataset.tally) || 0;
          const updated = Math.max(prev, newCount);
          tallyCell.dataset.tally = updated;

          
          if (key === 'monthly') {
            const monthlyTotal = parseFloat(entryTally['monthly_total']) || 0;
            tallyCell.dataset.total = monthlyTotal;
            tallyCell.innerHTML = `<span style="font-weight:700;">${updated}</span>
              <span style="font-size:10.5px;color:var(--text-muted);display:block;">
                ${fmt(monthlyTotal)}
              </span>`;
          } else {
            tallyCell.innerHTML = `<span style="font-weight:700;">${updated}</span>
              <span style="font-size:10.5px;color:var(--text-muted);display:block;">
                ${rate > 0 ? fmt(updated * rate) : '(see rate)'}
              </span>`;
          }
        }
      }
    });
  }

  sfRecalc();
}

function syncTallyToLocalStorage() {
  const TALLY_KEY = 'fitstop_inv_tally';
  const today     = new Date().toISOString().slice(0, 10);
  const tally     = { _date: today };
  document.querySelectorAll('#sfItemsBody tr[data-inv-id]').forEach(row => {
    const qty = parseInt(row.dataset.tally) || 0;
    if (qty > 0) tally[row.dataset.invId] = qty;
  });
  localStorage.setItem(TALLY_KEY, JSON.stringify(tally));
}

function loadSalesSummary() {
  const fd = new FormData();
  fd.append('action', 'get_sales_summary');
  fd.append('csrf_token', csrfToken);

  const staffId = (window.currentStaffId && Number.isInteger(window.currentStaffId)) ? window.currentStaffId : null;
  if (staffId) fd.append('staff_id', staffId);

  fetch('inventory.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (!data.success || !data.summary) return;

      document.getElementById('sfNonMember').value    = data.summary.non_member;
      document.getElementById('sfMemberWalkin').value = data.summary.member_walkin;
      document.getElementById('sfMembership').value   = data.summary.membership;
      document.getElementById('sfSpecial').value      = data.summary.special;
      document.getElementById('sfMonthly').value      = data.summary.monthly;

      applyLocalStorageTally();

      sfRecalc();
    })
    .catch(() => {});
}

document.addEventListener('input', function(e) {
  if (e.target.classList.contains('sf-item-qty') ||
      e.target.id === 'sfWater') {
    sfRecalc();
  }
});

document.querySelectorAll('.sf-item-qty').forEach(inp => {
  inp.addEventListener('change', function() {
    const row   = this.closest('tr');
    const stock = parseInt(row.dataset.stock) || 0;
    let val = parseInt(this.value) || 0;
    if (val < 0) val = 0;
    if (val > stock) { val = stock; this.value = stock; showToast('Max stock is ' + stock + ' units.', 'error'); }
    this.value = val;
    sfRecalc();
  });
});

function submitSalesForm() {
  const soldItems = [];
  document.querySelectorAll('#sfItemsBody tr[data-inv-id]').forEach(row => {
    const qty = parseInt(row.querySelector('.sf-item-qty')?.value) || 0;
    if (qty <= 0) return;
    soldItems.push({
      id:    row.dataset.invId,
      name:  row.dataset.name,
      qty:   qty,
      price: parseFloat(row.dataset.price),
    });
  });

  const entryTot = parseFloat(document.getElementById('sfEntryTot').innerHTML.replace(/[^\d.]/g,'')) || 0;
  const itemsTot = parseFloat(document.getElementById('sfItemsTot').innerHTML.replace(/[^\d.]/g,'')) || 0;
  if (entryTot === 0 && itemsTot === 0) {
    alert('Please enter at least one entry count or item quantity sold.');
    return;
  }

  const btn = document.getElementById('sfSubmitBtn');
  btn.disabled  = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

  const fd = new FormData();
  fd.append('action',      'submit_sales_form');
  fd.append('csrf_token',  csrfToken);
  fd.append('sale_date',   document.getElementById('sfDate').value);
  fd.append('non_member',  document.getElementById('sfNonMember').value);
  fd.append('member',      document.getElementById('sfMember').value);
  fd.append('special',     document.getElementById('sfSpecial').value);
  fd.append('monthly',     document.getElementById('sfMonthly').value);
  fd.append('less_water',  document.getElementById('sfWater').value);
  fd.append('sold_items',  JSON.stringify(soldItems));

  fetch('inventory.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        
        if (Array.isArray(data.updated_rows)) {
          data.updated_rows.forEach(row => {
            const tr = document.querySelector(`#inventoryBody tr[data-id="${row.id}"]`);
            if (!tr) return;
            const newQty = parseInt(row.quantity);
            const status = getStatus(newQty);
            tr.querySelector('.qty-cell').textContent     = newQty;
            tr.querySelector('.updated-cell').textContent = row.updated_at;
            tr.querySelector('.status-badge').textContent = status.txt;
            tr.querySelector('.status-badge').className   = 'status-badge ' + status.cls;
            tr.setAttribute('data-qty',     newQty);
            tr.setAttribute('data-updated', row.updated_at);

            const sfRow = document.querySelector(`#sfItemsBody tr[data-inv-id="${row.id}"]`);
            if (sfRow) {
              sfRow.dataset.stock = newQty;
              const stockBadge = sfRow.querySelector('.sf-stock-badge');
              if (stockBadge) {
                stockBadge.textContent = newQty;
                stockBadge.className   = 'sf-stock-badge status-badge ' + status.cls;
                stockBadge.style.cssText = 'font-size:10px;padding:2px 7px;';
              }
              const qtyInp = sfRow.querySelector('.sf-item-qty');
              if (qtyInp) {
                const addedQty = parseInt(qtyInp.value) || 0;

                const prevTally = parseInt(sfRow.dataset.tally) || 0;
                const newTally  = prevTally + addedQty;
                sfRow.dataset.tally = newTally;

                const tallyCell = sfRow.querySelector('.sf-item-tally');
                if (tallyCell && newTally > 0) {
                  const price = parseFloat(sfRow.dataset.price) || 0;
                  tallyCell.innerHTML = `<span style="font-weight:700;">${newTally}</span>
                    <span style="font-size:10.5px;color:var(--text-muted);display:block;">
                      ${fmt(newTally * price)}
                    </span>`;
                }

                qtyInp.value    = 0;
                qtyInp.max      = newQty;
                qtyInp.disabled = newQty === 0;
              }
              if (newQty === 0) sfRow.classList.add('stock-oos');
              else sfRow.classList.remove('stock-oos');
            }
          });
        }

        const successBar = document.getElementById('sfSuccess');
        document.getElementById('sfSuccessMsg').innerHTML =
          'Recorded! Entry: ' + fmt(data.entry_total) +
          ' · Items: ' + fmt(data.items_total) +
          ' · <strong>Grand Total: ' + fmt(data.grand_total) + '</strong>';
        successBar.classList.add('show');
        successBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

          const entryMap = {
        sfNonMember    : { tallyId: 'sfNonMemberTally',    rate: 60  },
        sfMemberWalkin : { tallyId: 'sfMemberWalkinTally', rate: 50  },
        sfMembership   : { tallyId: 'sfMembershipTally',   rate: 500 },
        sfSpecial      : { tallyId: 'sfSpecialTally',       rate: 40  },
        sfMonthly      : { tallyId: 'sfMonthlyTally',       rate: 0   },
  };
        Object.entries(entryMap).forEach(([inputId, cfg]) => {
          const inp  = document.getElementById(inputId);
          const cell = document.getElementById(cfg.tallyId);
          if (!inp || !cell) return;
          const added     = parseInt(inp.value) || 0;
          const prevTally = parseInt(cell.dataset.tally) || 0;
          const newTally  = prevTally + added;
          cell.dataset.tally = newTally;
          if (newTally > 0) {
            cell.innerHTML = `<span style="font-weight:700;">${newTally}</span>
              <span style="font-size:10.5px;color:var(--text-muted);display:block;">
                ${cfg.rate > 0 ? fmt(newTally * cfg.rate) : '(see rate)'}
              </span>`;
          }
          inp.value = 0;
        });

        document.querySelectorAll('.sf-item-qty').forEach(i => i.value = 0);
        document.getElementById('sfWater').value = 0;
        syncTallyToLocalStorage();

        sfRecalc();

        notifLoaded = false;
        const badge = document.getElementById('notifBadge');
        badge.textContent = (parseInt(badge.textContent) || 0) + soldItems.length + 1;
        badge.classList.remove('hidden');

        showToast('Daily sales submitted! Grand Total: ' + fmt(data.grand_total), 'success');
      } else {
        alert('Error: ' + (data.message || 'Could not submit.'));
      }
    })
    .catch(e => alert('Server error: ' + e.message))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Submit & Record to Log'; });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str || ''));
  return d.innerHTML;
}

function formatTs(ts) {
  if (!ts) return '—';
  const d = new Date(ts.replace(' ', 'T'));
  if (isNaN(d)) return ts;
  return d.toLocaleString('en-PH', { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit', hour12:true });
}

function initNotifBadge() {
  const NOTIF_KEY  = 'fitstop_inv_notif_read';
  const today      = new Date().toISOString().slice(0, 10);
  const totalCount = <?= $jsNotifTotal ?? 0 ?>;
  window._totalNotifCount = totalCount;

  let lastRead = { date: '', count: 0 };
  try { lastRead = JSON.parse(localStorage.getItem(NOTIF_KEY) || '{}'); } catch(e) {}

  const lastReadCount = (lastRead.date === today) ? (parseInt(lastRead.count) || 0) : 0;
  const unread        = Math.max(0, totalCount - lastReadCount);

  const badge = document.getElementById('notifBadge');
  if (unread > 0) {
    badge.textContent = unread > 99 ? '99+' : unread;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

function toggleNotifPanel() {
  notifPanelOpen = !notifPanelOpen;
  document.getElementById('notifPanel').classList.toggle('open', notifPanelOpen);
  if (notifPanelOpen) {
    
    const NOTIF_KEY = 'fitstop_inv_notif_read';
   localStorage.setItem(NOTIF_KEY, JSON.stringify({
  date:  new Date().toISOString().slice(0, 10),
  count: window._totalNotifCount || 0   
}));
    const badge = document.getElementById('notifBadge');
    badge.textContent = '0';
    badge.classList.add('hidden');

    if (!notifLoaded) loadNotifications();
  }
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
  fd.append('csrf_token', csrfToken);
  fetch('inventory.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      notifLoaded = true;
      if (!data.success) { list.innerHTML = '<div class="notif-empty"><i class="bi bi-exclamation-circle"></i>Could not load.</div>'; return; }
      const transactions = Array.isArray(data.transactions) ? data.transactions : [];
      const lowStock     = Array.isArray(data.low_stock)    ? data.low_stock    : [];
      const total        = transactions.length + lowStock.length;
      if (count) count.textContent = total + ' record' + (total !== 1 ? 's' : '');
      if (total === 0) { list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>No notifications yet.</div>'; return; }

      const txHtml = transactions.length ? `
        <div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">Recent Transactions</div>
        ` + transactions.map(t => {
          const amount = parseFloat(t.amount || 0).toFixed(2);
          const ts     = formatTs(t.transaction_date || t.created_at);
          const note   = t.desc || '';
          return `<div class="notif-item">
            <div class="notif-icon" style="background:rgba(34,208,122,0.12);border-color:rgba(34,208,122,0.3);color:var(--success);"><i class="bi bi-receipt"></i></div>
            <div class="notif-body">
              <p class="notif-msg"><strong>${escHtml(t.receipt_number || 'Receipt')}</strong><br>
              <span class="item-highlight">&#8369;${escHtml(amount)}</span> · ${escHtml(t.customer_name || 'Customer')} · ${escHtml(t.payment_method || '—')}
              ${note ? '· ' + escHtml(note) : ''}</p>
              <span class="notif-time"><i class="bi bi-clock"></i> ${ts}</span>
            </div>
          </div>`;
        }).join('') : '';

      const stockHtml = lowStock.length ? `
        <div style="padding:8px 16px;background:#000;font-size:10px;color:#555;text-transform:uppercase;letter-spacing:1.5px;font-family:'Chakra Petch',sans-serif;">Inventory Alerts</div>
        ` + lowStock.map(i => {
          const qty  = parseInt(i.quantity);
          const ts   = formatTs(i.updated_at);
          const iconC = qty === 0 ? 'var(--danger)' : 'var(--warning)';
          const bgC   = qty === 0 ? 'rgba(255,71,87,0.12)' : 'rgba(255,159,67,0.12)';
          const bdC   = qty === 0 ? 'rgba(255,71,87,0.3)'  : 'rgba(255,159,67,0.3)';
          const icon  = qty === 0 ? 'bi-x-circle-fill' : 'bi-exclamation-triangle-fill';
          const label = qty === 0
            ? '<span style="color:var(--danger);font-weight:700;">OUT OF STOCK</span>'
            : '<span style="color:var(--warning);font-weight:700;">Low Stock</span>';
          return `<div class="notif-item">
            <div class="notif-icon" style="background:${bgC};border-color:${bdC};color:${iconC};"><i class="bi ${icon}"></i></div>
            <div class="notif-body">
              <p class="notif-msg"><strong>${escHtml(i.item_name)}</strong> — ${escHtml(i.category)}<br>
              Stock: <span class="item-highlight">${qty} unit${qty !== 1 ? 's' : ''}</span> — ${label}</p>
              <span class="notif-time"><i class="bi bi-clock"></i> Updated: ${ts}</span>
            </div>
          </div>`;
        }).join('') : '';

      list.innerHTML = txHtml + stockHtml;
    })
    .catch(() => { list.innerHTML = '<div class="notif-empty"><i class="bi bi-wifi-off"></i>Server error.</div>'; });
}

function resetTally() {
  if (!confirm('Reset today\'s tally? This only clears the display — recorded transactions are kept.')) return;
  document.querySelectorAll('#sfItemsBody tr[data-inv-id]').forEach(row => {
    row.dataset.tally = 0;
    const cell = row.querySelector('.sf-item-tally');
    if (cell) cell.innerHTML = '—';
  });

['sfNonMemberTally','sfMemberWalkinTally','sfMembershipTally','sfSpecialTally','sfMonthlyTally'].forEach(id => {
  const cell = document.getElementById(id);
  if (cell) { cell.dataset.tally = 0; cell.innerHTML = '—'; }
});

['sfNonMember','sfMemberWalkin','sfMembership','sfSpecial','sfMonthly'].forEach(id => {
  const inp = document.getElementById(id);
  if (inp) { inp.value = 0; inp.dataset.lastTally = 0; }
});

  localStorage.removeItem('fitstop_inv_tally');
  localStorage.removeItem('fitstop_entry_tally'); 
  document.getElementById('sfSuccess').classList.remove('show');
  showToast('Tally reset.', 'success');
}

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
document.getElementById('soldModal').addEventListener('click', function(e) { if (e.target === this) closeSoldModal(); });
document.getElementById('salesModal').addEventListener('click', function(e) { if (e.target === this) closeSalesModal(); });

window.addEventListener('load', function() {
  loadSalesSummary();
  sfRecalc();
  initNotifBadge();         

  setInterval(function() {
    applyLocalStorageTally();
  }, 10000);
});
</script>

</body>
</html>