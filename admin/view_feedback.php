<?php
session_start();

$allowedRoles = ['admin', 'staff'];
if (empty($_SESSION['username']) || empty($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowedRoles, true)) {
    header('Location: ../Login/Login_Page.php');
    exit();
}

$isAdmin = $_SESSION['user_type'] === 'admin';

include('../Login/connection.php');

$feedbacks = [];
$errorMessage = null;

try {
    $stmt = $pdo->query('SELECT *, COALESCE(id, rowid) AS feedback_id FROM feedback ORDER BY datetime(created_at) DESC, rowid DESC');
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = 'Unable to load feedback right now.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Feedback | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body>
    <?php include('includes/header_admin.php') ?>
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar row">
            <div class="topbar-left col-sm-12 col-xl-6">
                <h1><i class="bi bi-chat-square-text-fill"></i> Feedback</h1>
                <p>View and manage machine feedback</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-2 col-xl-offset-4">
                <div class="topbar-badge">
                    <i class="bi bi-chat-dots"></i>
                    <span><?php echo count($feedbacks); ?> Total</span>
                </div>
            </div>
        </div>

        <?php if ($errorMessage !== null): ?>
            <div
                style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
            <!-- Filter Buttons -->
            <section>
                <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;" class="reveal-right">
                    <button class="btn-primary filter-btn active" data-filter="all" style="padding: 9px 16px;">All</button>
                    <button class="btn-secondary filter-btn" data-filter="member"
                        style="padding: 9px 16px;">Members</button>
                    <button class="btn-secondary filter-btn" data-filter="guest" style="padding: 9px 16px;">Guests</button>
                    <button class="btn-secondary filter-btn" data-filter="pending"
                        style="padding: 9px 16px;">Pending</button>
                    <button class="btn-secondary filter-btn" data-filter="in_progress" style="padding: 9px 16px;">In
                        Progress</button>
                    <button class="btn-secondary filter-btn" data-filter="resolved"
                        style="padding: 9px 16px;">Resolved</button>
                    <button class="btn-secondary filter-btn" data-filter="closed" style="padding: 9px 16px;">Closed</button>
                </div>

                <!-- Feedback List -->
                <div id="feedbackList" style="display: flex; flex-direction: column; gap: 14px;">
                    <?php if (count($feedbacks) === 0): ?>
                        <div class="registration-card" style="text-align: center; padding: 60px 20px;">
                            <h3
                                style="font-family: 'Chakra Petch', sans-serif; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;">
                                No feedback found</h3>
                            <p style="color: var(--text-muted); font-size: 12px;">Check back later</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <?php
                            $id = isset($feedback['feedback_id']) ? (int) $feedback['feedback_id'] : 0;
                            $about = htmlspecialchars((string) $feedback['about']);
                            $desc = htmlspecialchars((string) $feedback['desc']);
                            $createdAt = !empty($feedback['created_at']) ? date('F j, Y g:i A', strtotime($feedback['created_at'])) : 'Unknown date';
                            $status = isset($feedback['status']) && $feedback['status'] !== '' ? (string) $feedback['status'] : 'pending';
                            $statusSafe = htmlspecialchars($status);
                            $isGuest = empty($feedback['reporterID']);
                            $sourceType = $isGuest ? 'guest' : 'member';

                            $reporterName = 'Guest';
                            if (!$isGuest) {
                                $reporterName = !empty($feedback['last_name']) ? (string) $feedback['last_name'] : 'Member';
                                try {
                                    $userStmt = $pdo->prepare('SELECT first_name FROM users WHERE id = :id');
                                    $userStmt->execute(['id' => $feedback['reporterID']]);
                                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                                    if (!empty($user['first_name'])) {
                                        $reporterName = $user['first_name'] . ' ' . $reporterName;
                                    }
                                } catch (PDOException $e) {
                                }
                            }
                            $reporterName = htmlspecialchars($reporterName);
                            $statusLabel = htmlspecialchars(strtoupper(str_replace('_', ' ', $status)));

                            $statusBadgeClass = 'active';
                            if ($status === 'pending')
                                $statusBadgeClass = 'maintenance';
                            if ($status === 'in_progress')
                                $statusBadgeClass = 'low-stock';
                            if ($status === 'resolved')
                                $statusBadgeClass = 'active';
                            if ($status === 'closed')
                                $statusBadgeClass = 'inactive';
                            ?>
                            <article class="registration-card feedback-item reveal" data-source="<?php echo $sourceType; ?>"
                                data-status="<?php echo $statusSafe; ?>" data-feedback-id="<?php echo $id; ?>">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: start; gap: 20px; margin-bottom: 16px;">
                                    <div>
                                        <h3
                                            style="font-family: 'Chakra Petch', sans-serif; font-size: 18px; font-weight: 700; color: var(--hazard); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                            <i class="bi bi-wrench"></i> <?php echo $about; ?>
                                        </h3>
                                        <div
                                            style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px;">
                                            Reported by <?php echo $reporterName; ?> • <?php echo htmlspecialchars($createdAt); ?>
                                        </div>
                                    </div>
                                    <span
                                        class="status-badge <?php echo $statusBadgeClass; ?> status-label"><?php echo $statusLabel; ?></span>
                                </div>

                                <p
                                    style="color: var(--text-sub); font-size: 14px; line-height: 1.7; margin-bottom: 20px; padding-top: 16px; border-top: 1px solid var(--border); white-space: pre-wrap;">
                                    <?php echo $desc; ?>
                                </p>

                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <label
                                        style="color: var(--text-muted); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; margin: 0;">Status:</label>
                                    <select id="status-<?php echo $id; ?>" class="form-input status-select"
                                        style="max-width: 200px; padding: 9px 12px;" data-feedback-id="<?php echo $id; ?>">
                                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending
                                        </option>
                                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In
                                            Progress</option>
                                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved
                                        </option>
                                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <button class="btn-primary update-feedback-btn" data-feedback-id="<?php echo $id; ?>"
                                        style="padding: 9px 18px;">
                                        <i class="bi bi-check-circle"></i> Update
                                    </button>
                                    <span class="feedback-message"></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const feedbackItems = document.querySelectorAll('.feedback-item');

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const filter = button.dataset.filter;
                    filterButtons.forEach((btn) => {
                        btn.classList.remove('active', 'btn-primary');
                        btn.classList.add('btn-secondary');
                    });
                    button.classList.remove('btn-secondary');
                    button.classList.add('active', 'btn-primary');

                    feedbackItems.forEach((item) => {
                        const source = item.dataset.source;
                        const status = item.dataset.status;
                        const show = filter === 'all' || filter === source || filter === status;
                        item.style.display = show ? '' : 'none';
                    });
                });
            });

            document.querySelectorAll('.update-feedback-btn').forEach((button) => {
                button.addEventListener('click', async () => {
                    const item = button.closest('.feedback-item');
                    const feedbackIdRaw = button.dataset.feedbackId || item?.dataset.feedbackId || '';
                    const feedbackId = Number(feedbackIdRaw);
                    const select = item.querySelector('.status-select');
                    const message = item.querySelector('.feedback-message');
                    const badge = item.querySelector('.status-label');
                    const previousText = button.innerHTML;

                    if (!Number.isInteger(feedbackId) || feedbackId <= 0) {
                        message.className = 'feedback-message text-danger';
                        message.textContent = 'Invalid feedback ID';
                        return;
                    }

                    button.disabled = true;
                    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
                    message.className = 'feedback-message';
                    message.textContent = '';

                    try {
                        const payload = new URLSearchParams();
                        payload.set('id', String(feedbackId));
                        payload.set('status', select.value);

                        const response = await fetch('update_feedback_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: payload.toString()
                        });

                        const data = await response.json();

                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to update');
                        }

                        item.dataset.status = select.value;

                        let badgeClass = 'active';
                        if (select.value === 'pending') badgeClass = 'maintenance';
                        if (select.value === 'in_progress') badgeClass = 'low-stock';
                        if (select.value === 'resolved') badgeClass = 'active';
                        if (select.value === 'closed') badgeClass = 'inactive';

                        badge.className = 'status-badge status-label ' + badgeClass;
                        badge.textContent = select.value.replace('_', ' ').toUpperCase();

                        message.className = 'feedback-message text-success';
                        message.textContent = '✓ ' + data.message;
                        setTimeout(() => {
                            message.textContent = '';
                        }, 3000);
                    } catch (error) {
                        message.className = 'feedback-message text-danger';
                        message.textContent = '✗ ' + error.message;
                    } finally {
                        button.disabled = false;
                        button.innerHTML = previousText;
                    }
                });
            });
        });
    </script>
    <?php //include('includes/footer_admin.php') ?>
</body>

</html>