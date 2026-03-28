<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
require_once __DIR__ . '/../includes/security.php';
include("../Login/connection.php");

// Handle Archive/Activate
if (isset($_GET['archive'])) {
    $id = (int) $_GET['archive'];
    $stmt = $pdo->prepare("UPDATE api_table SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE api_id = :id");
    $stmt->execute(['id' => $id]);

    // Log notification
    $stmt = $pdo->prepare("SELECT api_name FROM api_table WHERE api_id = :id");
    $stmt->execute(['id' => $id]);
    $api = $stmt->fetch();

    $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
    $notif->execute([
        'API ARCHIVED',
        "API '{$api['api_name']}' has been deactivated",
        "Archived by " . $_SESSION['username'],
        'System'
    ]);

    header('Location: api_manage.php?success=archived');
    exit();
}

if (isset($_GET['activate'])) {
    $id = (int) $_GET['activate'];
    $stmt = $pdo->prepare("UPDATE api_table SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE api_id = :id");
    $stmt->execute(['id' => $id]);

    // Log notification
    $stmt = $pdo->prepare("SELECT api_name FROM api_table WHERE api_id = :id");
    $stmt->execute(['id' => $id]);
    $api = $stmt->fetch();

    $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
    $notif->execute([
        'API ACTIVATED',
        "API '{$api['api_name']}' has been reactivated",
        "Activated by " . $_SESSION['username'],
        'System'
    ]);

    header('Location: api_manage.php?success=activated');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter !== '') {
    $where_conditions[] = "status = :status";
    $params['status'] = $status_filter;
}

if ($search !== '') {
    $where_conditions[] = "(api_name LIKE :search OR api_url LIKE :search)";
    $params['search'] = "%$search%";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all APIs
$query = "SELECT * FROM api_table $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$apis = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM api_table");
$total_apis = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM api_table WHERE status = 'active'");
$active_apis = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM api_table WHERE status = 'inactive'");
$inactive_apis = $stmt->fetch()['total'];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>API Management - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .api-key-display {
            font-family: 'Courier New', monospace;
            background: var(--bg-surface);
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 2px;
            font-size: 11px;
            color: var(--hazard);
            word-break: break-all;
        }

        .copy-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 11px;
            margin-left: 8px;
        }

        .copy-btn:hover {
            background: var(--hazard);
            color: #000;
            border-color: var(--hazard);
        }

        .masked-key {
            filter: blur(4px);
            user-select: none;
        }

        .reveal-btn {
            cursor: pointer;
            color: var(--hazard);
            font-size: 11px;
            text-decoration: underline;
            margin-left: 8px;
        }

        .reveal-btn:hover {
            color: #ffd700;
        }
    </style>
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h1><i class="bi bi-key-fill"></i> API Management</h1>
                <p>Manage API keys and integrations</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="bi bi-shield-check"></i>
                    <span><?php echo $active_apis; ?> Active APIs</span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i>
                <?php
                if ($_GET['success'] === 'added')
                    echo 'API key added successfully';
                elseif ($_GET['success'] === 'updated')
                    echo 'API key updated successfully';
                elseif ($_GET['success'] === 'archived')
                    echo 'API key archived successfully';
                elseif ($_GET['success'] === 'activated')
                    echo 'API key activated successfully';
                ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="stat-box h-100">
                    <div class="stat-icon equipment"><i class="bi bi-collection"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $total_apis; ?></div>
                        <div class="stat-label">Total API Keys</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="stat-box h-100">
                    <div class="stat-icon members"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-value"><?php echo $active_apis; ?></div>
                        <div class="stat-label">Active APIs</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="stat-box h-100">
                    <div class="stat-icon" style="background: rgba(255, 71, 87, 0.1); color: var(--danger);"><i
                            class="bi bi-archive"></i></div>
                    <div>
                        <div class="stat-value" style="color: var(--danger);"><?php echo $inactive_apis; ?></div>
                        <div class="stat-label">Archived APIs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New API -->
        <section>
            <h2><i class="bi bi-plus-circle"></i> Add New API Key</h2>
            <div class="registration-card">
                <form method="POST" action="process_api.php">
                    <?php echo fitstop_csrf_input(); ?>
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label class="form-label">API Name</label>
                                <input type="text" name="api_name" class="form-input"
                                    placeholder="e.g., Payment Gateway" maxlength="100" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label class="form-label">API URL</label>
                                <input type="url" name="api_url" class="form-input"
                                    placeholder="https://api.example.com" maxlength="255" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-3">
                            <div class="form-group">
                                <label class="form-label">API Key</label>
                                <input type="text" name="api_key" class="form-input" placeholder="Enter API key"
                                    maxlength="255" required>
                            </div>
                        </div>
                        <div class="col-12 col-lg-1">
                            <div class="form-group" style="display: flex; align-items: flex-end; height: 100%;">
                                <button type="submit" class="btn-primary" style="width: 100%;">
                                    <i class="bi bi-plus-circle"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Search & Filter -->
        <section>
            <div class="inventory-header">
                <form method="GET" class="search-container">
                    <?php echo fitstop_csrf_input(); ?>
                    <div class="search-wrapper" style="flex: 2;">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search APIs..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <select name="status" class="search-input">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive
                        </option>
                    </select>

                    <button type="submit" class="search-btn">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </form>

                <?php if ($search || $status_filter): ?>
                    <a href="api_manage.php" class="btn-secondary" style="padding: 11px 22px; text-decoration: none;">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                <?php endif; ?>
            </div>

            <!-- API Keys Table -->
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>API Name</th>
                            <th>API URL</th>
                            <th>API Key</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($apis) > 0): ?>
                            <?php foreach ($apis as $api): ?>
                                <tr>
                                    <td><?php echo $api['api_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($api['api_name']); ?></strong></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($api['api_url']); ?>" target="_blank"
                                            style="color: var(--hazard); text-decoration: none; font-size: 11px;">
                                            <?php echo htmlspecialchars($api['api_url']); ?> <i
                                                class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <code class="api-key-display masked-key" id="key-<?php echo $api['api_id']; ?>">
                                                        <?php echo htmlspecialchars($api['api_key']); ?>
                                                    </code>
                                            <span class="reveal-btn" onclick="toggleKey(<?php echo $api['api_id']; ?>)">
                                                <i class="bi bi-eye" id="icon-<?php echo $api['api_id']; ?>"></i>
                                            </span>
                                            <button class="copy-btn" id="copy_btn"
                                                onclick="copyKey(<?php echo $api['api_id']; ?>, '<?php echo $api['api_key']; ?>')">
                                                <i class="bi bi-clipboard"></i> Copy
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge <?php echo $api['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                            <?php echo strtoupper($api['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($api['created_at'])); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($api['updated_at'])); ?></td>
                                    <td>
                                        <button class="btn-icon"
                                            onclick="editAPI(<?php echo htmlspecialchars(json_encode($api)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($api['status'] === 'active'): ?>
                                            <a href="?archive=<?php echo $api['api_id']; ?>" class="btn-icon"
                                                onclick="return confirm('Archive this API key?')">
                                                <i class="bi bi-archive"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?activate=<?php echo $api['api_id']; ?>" class="btn-icon"
                                                style="color: var(--success);">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <?php echo ($search || $status_filter) ? 'No APIs match your filters' : 'No API keys found. Add your first API!'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border);">
                    <h5 class="modal-title"
                        style="font-family: 'Chakra Petch', sans-serif; color: var(--hazard); text-transform: uppercase;">
                        <i class="bi bi-pencil"></i> Edit API Key
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="filter: invert(1);"></button>
                </div>
                <form method="POST" action="process_api.php">
                    <?php echo fitstop_csrf_input(); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="api_id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">API Name</label>
                            <input type="text" name="api_name" id="edit_name" class="form-input" maxlength="100"
                                required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">API URL</label>
                            <input type="url" name="api_url" id="edit_url" class="form-input" maxlength="255" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">API Key</label>
                            <input type="text" name="api_key" id="edit_key" class="form-input" maxlength="255" required>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--border);">
                        <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="bi bi-check-circle"></i> Update API
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editAPI(api) {
            document.getElementById('edit_id').value = api.api_id;
            document.getElementById('edit_name').value = api.api_name;
            document.getElementById('edit_url').value = api.api_url;
            document.getElementById('edit_key').value = api.api_key;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function toggleKey(id) {
            const keyElement = document.getElementById('key-' + id);
            const iconElement = document.getElementById('icon-' + id);

            if (keyElement.classList.contains('masked-key')) {
                keyElement.classList.remove('masked-key');
                iconElement.classList.remove('bi-eye');
                iconElement.classList.add('bi-eye-slash');
            } else {
                keyElement.classList.add('masked-key');
                iconElement.classList.remove('bi-eye-slash');
                iconElement.classList.add('bi-eye');
            }
        }

        function copyKey(id, key) {
            navigator.clipboard.writeText(key).then(() => {
                const btn = event.target.closest('.copy-btn');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> Copied!';
                btn.style.background = 'var(--success)';
                btn.style.color = '#fff';
            })
        }
    </script>

    <?php include('includes/footer_admin.php') ?>
</body>

</html>