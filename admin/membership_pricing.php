<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $price = floatval($_POST['price']);
            $promo_type = trim($_POST['promo_type']);

            $stmt = $pdo->prepare("INSERT INTO membership_price (price, promo_type) VALUES (:price, :promo_type)");
            try {
                $stmt->execute(['price' => $price, 'promo_type' => $promo_type]);
                header('Location: membership_pricing.php?success=added');
                exit();
            } catch (PDOException $e) {
                header('Location: membership_pricing.php?error=duplicate');
                exit();
            }
        } elseif ($_POST['action'] === 'update') {
            $id = (int) $_POST['m_price_id'];
            $price = floatval($_POST['price']);

            $stmt = $pdo->prepare("UPDATE membership_price SET price = :price, updated_at = CURRENT_TIMESTAMP WHERE m_price_id = :id");
            $stmt->execute(['price' => $price, 'id' => $id]);

            header('Location: membership_pricing.php?success=updated');
            exit();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM membership_price WHERE m_price_id = :id");
    $stmt->execute(['id' => $id]);

    // Log to notification history
    $notif = $pdo->prepare("INSERT INTO notification_history (name, description, remarks, category) VALUES (?, ?, ?, ?)");
    $notif->execute([
        'MEMBERSHIP PRICE DELETED',
        'A membership pricing option has been removed from the system',
        'Deleted by ' . $_SESSION['username'],
        'Pricing'
    ]);

    header('Location: membership_pricing.php?success=deleted');
    exit();
}

// Get all pricing
$stmt = $pdo->query("SELECT * FROM membership_price ORDER BY price ASC");
$pricing = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Membership Pricing - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <?php include('includes/header_admin.php') ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-tag-fill"></i> Membership Pricing</h1>
                <p>Manage membership prices and promotion types</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-2 col-xl-offset-4">
                <div class="topbar-badge">
                    <i class="bi bi-cash-stack"></i>
                    <span><?php echo count($pricing); ?> Pricing Plans</span>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i>
                <?php
                if ($_GET['success'] === 'added')
                    echo 'Pricing added successfully';
                elseif ($_GET['success'] === 'updated')
                    echo 'Pricing updated successfully';
                elseif ($_GET['success'] === 'deleted')
                    echo 'Pricing deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div
                style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i>
                <?php
                if ($_GET['error'] === 'duplicate')
                    echo 'This membership type already exists';
                ?>
            </div>
        <?php endif; ?>

        <!-- Add New Pricing -->
        <section>
            <h2><i class="bi bi-plus-circle"></i> Add New Pricing</h2>
            <div class="registration-card">
                <form method="POST" id="addPricingForm">
                    <div class="row">
                        <input type="hidden" name="action" value="add" maxlength="30">
                        <div class="form-group col-sm-12 col-xl-5">
                            <label class="form-label">Membership Type</label>
                            <input type="text" name="promo_type" class="form-input text-only"
                                placeholder="e.g., Daily, Weekly, Monthly" maxlength="50" required>
                        </div>
                        <div class="form-group col-sm-12 col-xl-5">
                            <label class="form-label">Price (₱)</label>
                            <input type="text" name="price" class="form-input number-only" placeholder="0.00"
                                maxlength="10" required>
                        </div>
                        <div class="form-group col-sm-12 col-xl-2 mt-4">
                            <button type="submit" class="btn-primary">
                                <i class="bi bi-plus-circle"></i> Add Pricing
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Pricing Table -->
        <section>
            <h2><i class="bi bi-list-ul"></i> Current Pricing Plans</h2>
            <div class="inventory-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Membership Type</th>
                            <th>Price</th>
                            <th>Created At</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pricing) > 0): ?>
                            <?php foreach ($pricing as $plan): ?>
                                <tr>
                                    <td><?php echo $plan['m_price_id']; ?></td>
                                    <td>
                                        <span
                                            style="padding: 6px 12px; background: rgba(255, 204, 0, 0.1); border: 1px solid rgba(255, 204, 0, 0.3); color: var(--hazard); font-weight: 700; text-transform: uppercase; font-size: 11px; font-family: 'Chakra Petch', sans-serif;">
                                            <?php echo htmlspecialchars($plan['promo_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong
                                            style="color: var(--hazard); font-size: 16px;">₱<?php echo number_format($plan['price'], 2); ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($plan['created_at'])); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($plan['updated_at'])); ?></td>
                                    <td>
                                        <button class="btn-icon"
                                            onclick="editPrice(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?php echo $plan['m_price_id']; ?>" class="btn-icon"
                                            onclick="return confirm('Delete this pricing plan?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    No pricing plans found. Add your first pricing plan above.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Edit Modal -->
    <div id="editModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: var(--bg-surface); border: 1px solid var(--border); max-width: 500px; width: 90%;">
            <div
                style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3
                    style="font-family: 'Chakra Petch', sans-serif; color: var(--hazard); text-transform: uppercase; margin: 0;">
                    Edit Pricing</h3>
                <button onclick="closeModal()"
                    style="background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" id="editPricingForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="m_price_id" id="edit_id">
                <div style="padding: 20px;">
                    <div class="form-group">
                        <label class="form-label">Membership Type</label>
                        <input type="text" id="edit_type" class="form-input" disabled
                            style="opacity: 0.6; cursor: not-allowed;">
                        <small style="color: var(--text-muted); font-size: 10px; margin-top: 5px; display: block;">Type
                            cannot be changed. Delete and create new if needed.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="text" name="price" id="edit_price" class="form-input number-only" maxlength="10"
                            required>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" onclick="closeModal()" class="btn-secondary"
                            style="flex: 1;">Cancel</button>
                        <button type="submit" class="btn-primary" style="flex: 1;">
                            <i class="bi bi-check-circle"></i> Update Price
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="validation.js"></script>
    <script>
        function editPrice(plan) {
            document.getElementById('edit_id').value = plan.m_price_id;
            document.getElementById('edit_type').value = plan.promo_type;
            document.getElementById('edit_price').value = plan.price;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <?php include('includes/footer_admin.php') ?>
</body>

</html>