<?php
session_start();

if(empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin'){
    header('Location: ../Login/Login_Page.php');
    exit();
}
include("../login/connection.php");

// Handle Add Item
if (isset($_POST['add_item'])) {
    $item_name = $_POST['item_name'];
    $category = $_POST['category'];
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
    $category = $_POST['category'];
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
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header('Location: view_inventory.php?success=deleted');
    exit();
}

// Pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query
$where_conditions = [];
$params = [];

if ($search != '') {
    $where_conditions[] = "item_name LIKE :search";
    $params['search'] = "%$search%";
}

if ($category_filter != '') {
    $where_conditions[] = "category = :category";
    $params['category'] = $category_filter;
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records
$total_query = $pdo->prepare("SELECT COUNT(*) as total FROM inventory $where_clause");
$total_query->execute($params);
$total_records = $total_query->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get inventory items
$stmt = $pdo->prepare("SELECT * FROM inventory $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM inventory ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <link rel="stylesheet" href="styles.css">

    <link href="../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body class="bg-dark">
    <img src="../images/Fitstop.png" alt="FITSTOP LOGIN" class="img-fluid w-100 h-100"
        style="object-fit: cover; position: fixed; opacity: 10%; z-index: -1;">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand brand-front" href="Admin_Landing_Page.php">
                <i class="bi bi-lightning-fill"></i> FITSTOP - <span class="text-danger">
                    Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Admin_Landing_Page.php">
                            <i class="bi bi-graph-up"></i> Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_announcement.php">
                            <i class="bi bi-megaphone"></i> Announcements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_staff.php">
                            <i class="bi bi-person-plus"></i> Create Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="view_inventory.php">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_staff.php">
                            <i class="bi bi-people"></i> Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_members.php">
                            <i class="bi bi-person-badge"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <form action="../Login/logout.php" method="POST" class="d-inline">
                            <button type="submit" class="nav-link border-0 bg-transparent" style="cursor: pointer;">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="mb-0"><i class="bi bi-box-seam me-2"></i>Manage Inventory</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                if ($_GET['success'] == 'added')
                    echo 'Item added successfully!';
                if ($_GET['success'] == 'updated')
                    echo 'Item updated successfully!';
                if ($_GET['success'] == 'deleted')
                    echo 'Item deleted successfully!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <div class="inventory-card mb-4">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search items..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="GET">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter == $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus-circle"></i> Add New Item
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="inventory-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <tr
                                    class="<?php echo ($item['quantity'] == 0) ? 'out-of-stock' : (($item['quantity'] < 10) ? 'low-stock' : ''); ?>">
                                    <td><strong>
                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                        </strong></td>
                                    <td><span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($item['category']); ?>
                                        </span></td>
                                    <td>
                                        <?php if ($item['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($item['quantity'] < 10): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo $item['quantity']; ?> (Low)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <?php echo $item['quantity']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Php
                                        <?php echo number_format($item['price'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                            onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Inventory pagination" class="mt-3">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control"
                                placeholder="e.g., Equipment, Supplements, Apparel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_item" class="btn btn-success">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" id="edit_category" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_item" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>

</html>