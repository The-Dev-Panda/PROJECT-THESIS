<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
include("../Login/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    fitstop_validate_csrf_or_exit($_POST['csrf_token'] ?? null);
}

// Handle Add Item
if (isset($_POST['add_item'])) {
    $item_name = $_POST['item_name'];
    $category = strtoupper($_POST['category']);
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, quantity, price, description) VALUES (:item_name, :category, :quantity, :price, :description)");
    $stmt->execute([
        'item_name' => $item_name,
        'category' => $category,
        'quantity' => $quantity,
        'price' => $price,
        'description' => $description
    ]);

    header('Location: view_inventory.php?success=added');
    exit();
}

// Handle Update Item
if (isset($_POST['update_item'])) {
    $id = $_POST['item_id'];
    $item_name = $_POST['item_name'];
    $category = strtoupper($_POST['category']);
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE inventory SET item_name = :item_name, category = :category, quantity = :quantity, price = :price, description = :description WHERE id = :id");
    $stmt->execute([
        'item_name' => $item_name,
        'category' => $category,
        'quantity' => $quantity,
        'price' => $price,
        'description' => $description,
        'id' => $id
    ]);

    header('Location: view_inventory.php?success=updated');
    exit();
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $get_item = $pdo->prepare("SELECT item_name, category, quantity FROM inventory WHERE id = :id");
    $get_item->execute(['id' => $id]);
    $item = $get_item->fetch();

    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = :id");
    $stmt->execute(['id' => $id]);

    // Log to notification history
    $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
    $notif->execute([
        'INVENTORY ITEM DELETED',
        'Item: ' . $item['item_name'] . ' (Category: ' . $item['category'] . ', Qty: ' . $item['quantity'] . ')',
        'Deleted by ' . $_SESSION['username'],
        'Inventory'
    ]);

    header('Location: view_inventory.php?success=deleted');
    exit();
}

// Get filter and sort parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$stock_filter = isset($_GET['stock']) ? trim($_GET['stock']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Allowed sort columns
$allowed_sort = ['item_name', 'category', 'quantity', 'price', 'created_at'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Validate sort order
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Build WHERE clause
$where_conditions = [];
$params = [];

// Search filter
if ($search !== '') {
    $where_conditions[] = "item_name LIKE :search";
    $params['search'] = "%$search%";
}

// Category filter
if ($category_filter !== '') {
    $where_conditions[] = "category = :category";
    $params['category'] = $category_filter;
}

// Stock level filter
if ($stock_filter === 'out') {
    $where_conditions[] = "quantity = 0";
} elseif ($stock_filter === 'low') {
    $where_conditions[] = "quantity > 0 AND quantity < 10";
} elseif ($stock_filter === 'in_stock') {
    $where_conditions[] = "quantity >= 10";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM inventory $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get inventory items
$query = "SELECT * FROM inventory $where_clause ORDER BY $sort_by $sort_order LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM inventory ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Helper function to generate sort URL
function getSortUrl($column, $current_sort, $current_order, $search, $category, $stock, $page)
{
    $new_order = 'ASC';
    if ($current_sort === $column && $current_order === 'ASC') {
        $new_order = 'DESC';
    }
    $params = "sort=$column&order=$new_order";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($category)
        $params .= "&category=" . urlencode($category);
    if ($stock)
        $params .= "&stock=$stock";
    if ($page > 1)
        $params .= "&page=$page";
    return "?$params";
}

// Helper function to get sort icon
function getSortIcon($column, $current_sort, $current_order)
{
    if ($current_sort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="opacity: 0.3; font-size: 10px;"></i>';
    }
    return $current_order === 'ASC'
        ? '<i class="bi bi-arrow-up" style="color: var(--hazard); font-size: 10px;"></i>'
        : '<i class="bi bi-arrow-down" style="color: var(--hazard); font-size: 10px;"></i>';
}

// Build pagination URL
function getPaginationUrl($page_num, $search, $category, $stock, $sort, $order)
{
    $params = "page=$page_num";
    if ($search)
        $params .= "&search=" . urlencode($search);
    if ($category)
        $params .= "&category=" . urlencode($category);
    if ($stock)
        $params .= "&stock=$stock";
    if ($sort !== 'created_at')
        $params .= "&sort=$sort";
    if ($order !== 'DESC')
        $params .= "&order=$order";
    return "?$params";
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Inventory | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <style>
        .sortable-header {
            cursor: pointer;
            user-select: none;
            transition: color 0.2s;
        }

        .sortable-header:hover {
            color: var(--hazard);
        }
    </style>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-box-seam"></i> Inventory</h1>
                <p>Manage equipment stock & status</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-1 col-xl-offset-5">
                <div class="topbar-badge">
                    <i class="bi bi-archive"></i>
                    <span><?php echo $total_records; ?> Items</span>
                </div>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i>
                <?php
                if ($_GET['success'] == 'added')
                    echo 'Item added successfully!';
                if ($_GET['success'] == 'updated')
                    echo 'Item updated successfully!';
                if ($_GET['success'] == 'deleted')
                    echo 'Item deleted successfully!';
                ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <section>
            <div class="inventory-header row">
                <div class="col-8">
                    <form method="GET">
                        <div class="row">
                            <div class="search-wrapper col-sm-12 col-xl-2" style="min-width: 200px;">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" name="search" class="search-input" maxlength="30"
                                    placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-sm-12 col-xl-3">
                                <select name="category" class="search-input">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-12 col-xl-3">
                                <select name="stock" class="search-input">
                                    <option value="">All Stock Levels</option>
                                    <option value="in_stock" <?php echo $stock_filter === 'in_stock' ? 'selected' : ''; ?>>In
                                        Stock
                                        (10+)</option>
                                    <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>Low Stock
                                        (1-9)
                                    </option>
                                    <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>Out of
                                        Stock
                                    </option>
                                </select>
                            </div>

                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                            <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                            <div class="col-sm-12 col-xl-1 p-0 m-0 d-flex justify-content-center">
                                <button type="submit" class="search-btn">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-sm-12 col-xl-3">
                    <div class="row">
                        <div class="col-sm-12 col-xl-6 p-0 m-0">
                            <?php if ($search || $category_filter || $stock_filter): ?>
                                <a href="view_inventory.php" class="btn-secondary" style=" text-decoration: none;">
                                    <i class="bi bi-x-circle"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-12 col-xl-6 d-flex justify-content-center">
                            <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="bi bi-plus-circle"></i> Add Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('item_name', $sort_by, $sort_order, $search, $category_filter, $stock_filter, $page); ?>'">
                                Item Name <?php echo getSortIcon('item_name', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('category', $sort_by, $sort_order, $search, $category_filter, $stock_filter, $page); ?>'">
                                Category <?php echo getSortIcon('category', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('quantity', $sort_by, $sort_order, $search, $category_filter, $stock_filter, $page); ?>'">
                                Quantity <?php echo getSortIcon('quantity', $sort_by, $sort_order); ?>
                            </th>
                            <th class="sortable-header"
                                onclick="window.location.href='<?php echo getSortUrl('price', $sort_by, $sort_order, $search, $category_filter, $stock_filter, $page); ?>'">
                                Price <?php echo getSortIcon('price', $sort_by, $sort_order); ?>
                            </th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                    <td>
                                        <span
                                            style="padding: 4px 11px; font-size: 10px; background: rgba(255,255,255,0.1); border: 1px solid var(--border); text-transform: uppercase;">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="status-badge inactive">Out of Stock</span>
                                        <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="status-badge low-stock"><?php echo $item['quantity']; ?> Low</span>
                                        <?php else: ?>
                                            <span class="status-badge active"><?php echo $item['quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong
                                            style="color: var(--hazard);">₱<?php echo number_format($item['price'], 2); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <button class="btn-icon"
                                            onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?php echo $item['id']; ?>" class="btn-icon"
                                            onclick="return confirm('Are you sure?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo ($search || $category_filter || $stock_filter) ? 'No items match your filters' : 'No items found'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo getPaginationUrl($page - 1, $search, $category_filter, $stock_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo getPaginationUrl($i, $search, $category_filter, $stock_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: <?php echo ($i == $page) ? 'var(--hazard)' : 'var(--bg-card)'; ?>; color: <?php echo ($i == $page) ? '#000' : 'var(--text-muted)'; ?>; border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo getPaginationUrl($page + 1, $search, $category_filter, $stock_filter, $sort_by, $sort_order); ?>"
                            style="padding: 8px 12px; margin: 0 3px; background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border); text-decoration: none; font-family: 'Chakra Petch', sans-serif; font-size: 11px;">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($search || $category_filter || $stock_filter || $sort_by !== 'created_at' || $sort_order !== 'DESC'): ?>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="view_inventory.php"
                        style="color: var(--text-muted); text-decoration: none; font-size: 11px; text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset All Filters
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Edit Item Modal -->
        <div class="modal fade" id="editItemModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border);">
                    <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                        <h5 class="modal-title"
                            style="color: var(--hazard); text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                            Edit Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            style="filter: invert(1);"></button>
                    </div>
                    <form method="POST">
                        <?php echo fitstop_csrf_input(); ?>
                        <input type="hidden" name="item_id" id="edit_item_id">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Item Name</label>
                                <input type="text" name="item_name" id="edit_item_name" maxlength="30"
                                    class="form-input" required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Category</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <select class="form-input" id="edit_category_select"
                                        onchange="handleEditCategoryChange()">
                                        <option value="">-- Select Existing --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="category" id="edit_category" maxlength="30"
                                        class="form-input" placeholder="Or type new" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" id="edit_quantity" class="form-input" min="0"
                                    required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Price</label>
                                <input type="number" name="price" id="edit_price" class="form-input" step="0.01" min="0"
                                    required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Description</label>
                                <textarea name="description" id="edit_description" class="form-input"
                                    rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid var(--border);">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_item" class="btn-primary">Update Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Item Modal -->
        <div class="modal fade" id="addItemModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border);">
                    <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                        <h5 class="modal-title"
                            style="color: var(--hazard); text-transform: uppercase; font-family: 'Chakra Petch', sans-serif;">
                            Add New Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            style="filter: invert(1);"></button>
                    </div>
                    <form method="POST">
                        <?php echo fitstop_csrf_input(); ?>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Item Name</label>
                                <input type="text" name="item_name" class="form-input" maxlength="30" required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Category</label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <select class="form-input" id="category_select" onchange="handleCategoryChange()">
                                        <option value="">-- Select Existing --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>">
                                                <?php echo htmlspecialchars($cat); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="category" id="category_input" class="form-input"
                                        maxlength="30" placeholder="Or type new" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" class="form-input" min="0" required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Price</label>
                                <input type="number" name="price" class="form-input" step="0.01" min="0" required>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label>Description</label>
                                <textarea name="description" class="form-input" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid var(--border);">
                            <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_item" class="btn-primary">Add Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function handleCategoryChange() {
                const select = document.getElementById('category_select');
                const input = document.getElementById('category_input');
                if (select.value !== '') {
                    input.value = select.value;
                }
            }

            function handleEditCategoryChange() {
                const select = document.getElementById('edit_category_select');
                const input = document.getElementById('edit_category');
                if (select.value !== '') {
                    input.value = select.value;
                }
            }

            function editItem(item) {
                document.getElementById('edit_item_id').value = item.id;
                document.getElementById('edit_item_name').value = item.item_name;
                document.getElementById('edit_category').value = item.category;
                document.getElementById('edit_quantity').value = item.quantity;
                document.getElementById('edit_price').value = item.price;
                document.getElementById('edit_description').value = item.description;
                new bootstrap.Modal(document.getElementById('editItemModal')).show();
            }
        </script>
    </div>
</body>

</html>