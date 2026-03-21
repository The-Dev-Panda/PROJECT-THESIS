<?php
require_once '../login/connection.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
  $action = $_POST['action'];

  try {

    if ($action === 'add') {
      $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, price, description, created_at, updated_at)
                            VALUES (:name, :cat, :qty, :price, :desc, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
      $stmt->execute([
        ':name'  => trim($_POST['item_name']),
        ':cat'   => trim($_POST['category']),
        ':qty'   => (int)$_POST['quantity'],
        ':price' => (float)$_POST['price'],
        ':desc'  => trim($_POST['description'] ?? ''),
      ]);
      $id  = $pdo->lastInsertId();
      $row = $pdo->query("SELECT * FROM inventory WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
      echo json_encode(['success' => true, 'row' => $row]);
      exit;
    }

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
      echo json_encode(['success' => true, 'row' => $row]);
      exit;
    }

    if ($action === 'delete') {
      $id = (int)$_POST['id'];
      $pdo->prepare("DELETE FROM inventory WHERE id = :id")->execute([':id' => $id]);
      echo json_encode(['success' => true]);
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
} catch (Exception $e) {
  $rows = [];
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
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.5); z-index: 1000;
      justify-content: center; align-items: center;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
      background: #fff; border-radius: 12px; padding: 30px;
      width: 460px; max-width: 95%;
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
    .modal-field select,
    .modal-field textarea {
      width: 100%; padding: 9px 12px;
      border: 1.5px solid #ddd; border-radius: 8px;
      font-family: 'Poppins', sans-serif; font-size: 14px;
      box-sizing: border-box; transition: border 0.2s; resize: vertical;
    }
    .modal-field input:focus,
    .modal-field select:focus,
    .modal-field textarea:focus { outline: none; border-color: #4f8ef7; }
    .modal-row-2 { display: flex; gap: 12px; }
    .modal-row-2 .modal-field { flex: 1; }
    .modal-meta {
      background: #f5f7fa; border-radius: 8px; padding: 10px 14px;
      font-size: 12px; color: #888; margin-bottom: 14px;
      display: flex; justify-content: space-between; gap: 10px;
    }
    .modal-meta span { display: flex; flex-direction: column; gap: 2px; }
    .modal-meta strong { font-size: 12px; color: #555; }
    .stock-action-row { display: flex; gap: 10px; margin-bottom: 4px; }
    .stock-action-row button {
      flex: 1; padding: 9px; border: 1.5px solid #ddd; border-radius: 8px;
      font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 600;
      cursor: pointer; transition: all 0.2s; background: #fff; color: #555;
    }
    .stock-action-row button.selected-add    { background: #e6f4ea; border-color: #34a853; color: #34a853; }
    .stock-action-row button.selected-reduce { background: #fdecea; border-color: #e53935; color: #e53935; }
    .current-stock-info {
      background: #f5f7fa; border-radius: 8px; padding: 10px 14px;
      font-size: 13px; color: #555; margin-bottom: 14px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .current-stock-info strong { font-size: 18px; color: #1a1a2e; }
    .modal-save-btn {
      width: 100%; padding: 11px; background: #4f8ef7; color: #fff;
      border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 4px;
    }
    .modal-save-btn:hover { background: #2d6fe0; }
    .modal-save-btn.green { background: #34a853; }
    .modal-save-btn.green:hover { background: #2a8944; }
    .modal-save-btn:disabled { background: #aaa; cursor: not-allowed; }
    .menu li a, .menu li span, .menu li { color: inherit !important; text-decoration: none !important; }
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
        <button class="add-btn" onclick="openAddModal()"><i class="bi bi-plus-circle"></i> Add Product</button>
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
                <button class="btn-icon" onclick="openEditModal(this)"><i class="bi bi-pencil"></i></button>
                <button class="btn-icon" onclick="deleteProduct(this)"><i class="bi bi-trash"></i></button>
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

<div class="modal-overlay" id="addModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeAddModal()"><i class="bi bi-x-lg"></i></button>
    <h3>Add New Product</h3>
    <span class="modal-product-id">Fields marked <span style="color:red">*</span> are required</span>
    <div class="modal-field">
      <label>Item Name <span style="color:red">*</span></label>
      <input type="text" id="addItemName" placeholder="e.g. Whey Protein">
    </div>
    <div class="modal-row-2">
      <div class="modal-field">
        <label>Category <span style="color:red">*</span></label>
        <select id="addCategory">
          <option value="Beverage">Beverage</option>
          <option value="Supplements">Supplements</option>
          <option value="Nutrition">Nutrition</option>
          <option value="Equipment">Equipment</option>
          <option value="Apparel">Apparel</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="modal-field">
        <label>Price (&#8369;) <span style="color:red">*</span></label>
        <input type="number" id="addPrice" min="0" step="0.01" placeholder="e.g. 150.00">
      </div>
    </div>
    <div class="modal-field">
      <label>Quantity <span style="color:red">*</span></label>
      <input type="number" id="addQty" min="0" value="1">
    </div>
    <div class="modal-field">
      <label>Description <span style="font-weight:400;color:#aaa;">(optional)</span></label>
      <textarea id="addDescription" rows="2" placeholder="e.g. Chocolate flavored whey protein..."></textarea>
    </div>
    <button class="modal-save-btn green" id="addSaveBtn" onclick="saveNewProduct()">
      <i class="bi bi-plus-circle"></i> Add Product
    </button>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeEditModal()"><i class="bi bi-x-lg"></i></button>
    <h3 id="modalItemName">Item Name</h3>
    <span class="modal-product-id" id="modalItemId">ID: 0</span>
    <div class="current-stock-info">
      <span>Current Stock</span>
      <strong id="modalCurrentQty">0</strong>
    </div>
    <div class="modal-field">
      <label>Stock Action</label>
      <div class="stock-action-row">
        <button id="btnAdd" onclick="selectAction('add')"><i class="bi bi-plus-circle"></i> Add Stock</button>
        <button id="btnReduce" onclick="selectAction('reduce')"><i class="bi bi-dash-circle"></i> Customer Bought</button>
      </div>
    </div>
    <div class="modal-field">
      <label>Quantity</label>
      <input type="number" id="modalQtyInput" min="1" value="1" placeholder="Enter quantity">
    </div>
    <div class="modal-field">
      <label>Reason / Note <span style="font-weight:400;color:#aaa;">(optional)</span></label>
      <input type="text" id="modalReason" placeholder="e.g. Customer purchase, Restock delivery...">
    </div>
    <div class="modal-meta">
      <span><strong>Created At</strong><span id="modalCreated">—</span></span>
      <span><strong>Updated At</strong><span id="modalUpdated">—</span></span>
    </div>
    <button class="modal-save-btn" id="editSaveBtn" onclick="saveStockChange()">Save Changes</button>
  </div>
</div>

<script>
  let currentRow     = null;
  let selectedAction = 'add';

  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = 'toast ' + type + ' show';
    setTimeout(() => { t.className = 'toast'; }, 3000);
  }

  function getStatus(qty) {
    qty = parseInt(qty);
    if (qty === 0)  return { cls: 'inactive',  txt: 'Out of Stock' };
    if (qty <= 10)  return { cls: 'low-stock', txt: 'Low Stock'    };
    return                 { cls: 'active',    txt: 'Available'    };
  }

  function formatPrice(num) {
    return '&#8369;' + parseFloat(num).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  }

  function openAddModal() {
    document.getElementById('addItemName').value    = '';
    document.getElementById('addCategory').value   = 'Beverage';
    document.getElementById('addPrice').value       = '';
    document.getElementById('addQty').value         = '1';
    document.getElementById('addDescription').value = '';
    document.getElementById('addModal').classList.add('active');
  }

  function closeAddModal() {
    document.getElementById('addModal').classList.remove('active');
  }

  function saveNewProduct() {
    const name  = document.getElementById('addItemName').value.trim();
    const cat   = document.getElementById('addCategory').value;
    const price = document.getElementById('addPrice').value.trim();
    const qty   = document.getElementById('addQty').value.trim();
    const desc  = document.getElementById('addDescription').value.trim();

    if (!name)                                            { alert('Please enter an item name.');     return; }
    if (!price || isNaN(price) || parseFloat(price) < 0) { alert('Please enter a valid price.');    return; }
    if (!qty   || isNaN(qty)   || parseInt(qty) < 0)     { alert('Please enter a valid quantity.'); return; }

    const btn = document.getElementById('addSaveBtn');
    btn.disabled = true; btn.textContent = 'Saving...';

    const fd = new FormData();
    fd.append('action',      'add');
    fd.append('item_name',   name);
    fd.append('category',    cat);
    fd.append('price',       price);
    fd.append('quantity',    qty);
    fd.append('description', desc);

    fetch('inventory.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          appendRow(data.row);
          closeAddModal();
          showToast('Product "' + name + '" added successfully!', 'success');
        } else {
          showToast('Error: ' + (data.message || 'Could not add product.'), 'error');
          alert('Error: ' + (data.message || 'Could not add product.'));
        }
      })
      .catch((e) => alert('Server error: ' + e.message))
      .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-plus-circle"></i> Add Product'; });
  }

  function appendRow(row) {
    const qty    = parseInt(row.quantity);
    const status = getStatus(qty);
    const tbody  = document.getElementById('inventoryBody');
    const tr     = document.createElement('tr');
    tr.setAttribute('data-id',          row.id);
    tr.setAttribute('data-name',        row.item_name);
    tr.setAttribute('data-category',    row.category);
    tr.setAttribute('data-qty',         qty);
    tr.setAttribute('data-price',       row.price);
    tr.setAttribute('data-description', row.description || '');
    tr.setAttribute('data-created',     row.created_at);
    tr.setAttribute('data-updated',     row.updated_at);
    tr.innerHTML = `
      <td>${row.id}</td>
      <td>${row.item_name}</td>
      <td>${row.category}</td>
      <td class="qty-cell">${qty}</td>
      <td>${formatPrice(row.price)}</td>
      <td>${row.description || '—'}</td>
      <td class="created-cell">${row.created_at}</td>
      <td class="updated-cell">${row.updated_at}</td>
      <td><span class="status-badge ${status.cls}">${status.txt}</span></td>
      <td>
        <button class="btn-icon" onclick="openEditModal(this)"><i class="bi bi-pencil"></i></button>
        <button class="btn-icon" onclick="deleteProduct(this)"><i class="bi bi-trash"></i></button>
      </td>`;
    tbody.appendChild(tr);
  }

  function openEditModal(btn) {
    currentRow = btn.closest('tr');
    document.getElementById('modalItemName').textContent   = currentRow.getAttribute('data-name');
    document.getElementById('modalItemId').textContent     = 'ID: ' + currentRow.getAttribute('data-id');
    document.getElementById('modalCurrentQty').textContent = currentRow.querySelector('.qty-cell').textContent;
    document.getElementById('modalQtyInput').value         = 1;
    document.getElementById('modalReason').value           = '';
    document.getElementById('modalCreated').textContent    = currentRow.getAttribute('data-created') || '—';
    document.getElementById('modalUpdated').textContent    = currentRow.getAttribute('data-updated') || '—';
    selectAction('add');
    document.getElementById('editModal').classList.add('active');
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    currentRow = null;
  }

  function selectAction(action) {
    selectedAction = action;
    document.getElementById('btnAdd').className    = action === 'add'    ? 'selected-add'    : '';
    document.getElementById('btnReduce').className = action === 'reduce' ? 'selected-reduce' : '';
  }

  function saveStockChange() {
    const qtyInput = parseInt(document.getElementById('modalQtyInput').value);
    if (!qtyInput || qtyInput < 1) { alert('Please enter a valid quantity (at least 1).'); return; }

    const id         = currentRow.getAttribute('data-id');
    const change     = selectedAction === 'add' ? qtyInput : -qtyInput;
    const currentQty = parseInt(currentRow.querySelector('.qty-cell').textContent);

    if (currentQty + change < 0) {
      alert('Cannot reduce stock below 0. Current stock: ' + currentQty);
      return;
    }

    const btn = document.getElementById('editSaveBtn');
    btn.disabled = true; btn.textContent = 'Saving...';

    const fd = new FormData();
    fd.append('action', 'update_stock');
    fd.append('id',     id);
    fd.append('change', change);

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

          document.getElementById('modalCurrentQty').textContent = newQty;
          document.getElementById('modalUpdated').textContent     = row.updated_at;

          showToast('Stock updated successfully!', 'success');
          closeEditModal();
        } else {
          alert(data.message || 'Could not update stock.');
        }
      })
      .catch(() => alert('Server error. Please try again.'))
      .finally(() => { btn.disabled = false; btn.textContent = 'Save Changes'; });
  }

  function deleteProduct(btn) {
    if (!confirm('Are you sure you want to delete this product?')) return;
    const row  = btn.closest('tr');
    const id   = row.getAttribute('data-id');
    const name = row.getAttribute('data-name');

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('inventory.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          row.remove();
          showToast('Product "' + name + '" deleted.', 'success');
        } else {
          alert('Could not delete product.');
        }
      })
      .catch(() => alert('Server error. Please try again.'));
  }

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

  document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
  document.getElementById('addModal').addEventListener('click',  function(e) { if (e.target === this) closeAddModal();  });
</script>
</body>
</html>