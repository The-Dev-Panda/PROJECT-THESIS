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
    <title>Feedback Management - FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .feedback-shell {
            min-height: 100vh;
            padding-bottom: 2rem;
        }

        .feedback-list {
            display: grid;
            gap: 1rem;
        }

        .feedback-card {
            background: var(--bg-surface);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1rem;
            color: var(--text-primary);
        }

        .feedback-title {
            color: var(--hazard-yellow);
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .feedback-meta {
            opacity: 0.75;
            font-size: 0.9rem;
        }

        .feedback-desc {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            white-space: pre-wrap;
        }

        .status-chip {
            font-size: 0.75rem;
            border-radius: 999px;
            padding: 0.3rem 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .chip-pending { background: #ffc107; color: #1c1c1c; }
        .chip-in_progress { background: #17a2b8; color: #fff; }
        .chip-resolved { background: #28a745; color: #fff; }
        .chip-closed { background: #6c757d; color: #fff; }

        .filter-btn.active {
            color: #1c1c1c !important;
            background: var(--hazard-yellow) !important;
            border-color: var(--hazard-yellow) !important;
        }

        .feedback-message {
            font-size: 0.9rem;
            min-height: 1.2rem;
            display: inline-block;
        }
    </style>
</head>
<body class="bg-dark">
    <img src="../images/Fitstop.png" alt="FITSTOP" class="img-fluid w-100 h-100" style="object-fit: cover; position: fixed; opacity: 0.1; z-index: -1;">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand brand-front" href="<?= $isAdmin ? 'Admin_Landing_Page.php' : '../staff/staff.php' ?>">
                <i class="bi bi-lightning-fill"></i> FITSTOP - <span class="text-danger"><?= $isAdmin ? 'Admin' : 'Staff' ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($isAdmin): ?>
                    <li class="nav-item"><a class="nav-link" href="Admin_Landing_Page.php"><i class="bi bi-graph-up"></i> Analytics</a></li>
                    <li class="nav-item"><a class="nav-link" href="create_announcement.php"><i class="bi bi-megaphone"></i> Announcements</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="view_inventory.php"><i class="bi bi-box-seam"></i> Inventory</a></li>
                    <li class="nav-item"><a class="nav-link active" href="view_feedback.php"><i class="bi bi-chat-square-text"></i> Feedback</a></li>
                    <li class="nav-item">
                        <form action="../Login/logout.php" method="POST" class="d-inline">
                            <button type="submit" class="nav-link border-0 bg-transparent" style="cursor:pointer;"><i class="bi bi-box-arrow-right"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1 class="mb-0"><i class="bi bi-chat-square-text-fill me-2"></i>Feedback Management</h1>
            <small class="text-muted">View, filter, and update machine feedback status</small>
        </div>
    </div>

    <main class="feedback-shell container mt-3">
        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-sm btn-outline-light filter-btn active" data-filter="all">All</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="member">Members</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="guest">Guests</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="pending">Pending</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="in_progress">In Progress</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="resolved">Resolved</button>
                <button class="btn btn-sm btn-outline-light filter-btn" data-filter="closed">Closed</button>
            </div>

            <div id="feedbackList" class="feedback-list">
                <?php if (count($feedbacks) === 0): ?>
                    <div class="alert alert-secondary mb-0">No feedback found yet.</div>
                <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <?php
                            $id = isset($feedback['feedback_id']) ? (int)$feedback['feedback_id'] : 0;
                            $about = htmlspecialchars((string)$feedback['about']);
                            $desc = htmlspecialchars((string)$feedback['desc']);
                            $createdAt = !empty($feedback['created_at']) ? date('F j, Y g:i A', strtotime($feedback['created_at'])) : 'Unknown date';
                            $status = isset($feedback['status']) && $feedback['status'] !== '' ? (string)$feedback['status'] : 'pending';
                            $statusSafe = htmlspecialchars($status);
                            $isGuest = empty($feedback['reporterID']);
                            $sourceType = $isGuest ? 'guest' : 'member';

                            $reporterName = 'Guest';
                            if (!$isGuest) {
                                $reporterName = !empty($feedback['last_name']) ? (string)$feedback['last_name'] : 'Member';
                                try {
                                    $userStmt = $pdo->prepare('SELECT first_name FROM users WHERE id = :id');
                                    $userStmt->execute(['id' => $feedback['reporterID']]);
                                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                                    if (!empty($user['first_name'])) {
                                        $reporterName = $user['first_name'] . ' ' . $reporterName;
                                    }
                                } catch (PDOException $e) {
                                    // Keep fallback reporter name.
                                }
                            }
                            $reporterName = htmlspecialchars($reporterName);
                            $statusLabel = htmlspecialchars(strtoupper(str_replace('_', ' ', $status)));
                        ?>
                        <article class="feedback-card feedback-item" data-source="<?= $sourceType ?>" data-status="<?= $statusSafe ?>" data-feedback-id="<?= $id ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <h2 class="feedback-title h5 mb-1"><i class="bi bi-wrench me-2"></i><?= $about ?></h2>
                                    <div class="feedback-meta">Reported by <?= $reporterName ?> • <?= htmlspecialchars($createdAt) ?></div>
                                </div>
                                <span class="status-chip chip-<?= $statusSafe ?> status-label"><?= $statusLabel ?></span>
                            </div>

                            <p class="feedback-desc mb-3"><?= $desc ?></p>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <label class="form-label mb-0 me-1" for="status-<?= $id ?>">Status:</label>
                                <select id="status-<?= $id ?>" class="form-select form-select-sm status-select" style="max-width: 220px;" data-feedback-id="<?= $id ?>">
                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                                </select>
                                <button class="btn btn-sm btn-primary update-feedback-btn" data-feedback-id="<?= $id ?>">Update</button>
                                <span class="feedback-message"></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const feedbackItems = document.querySelectorAll('.feedback-item');

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const filter = button.dataset.filter;
                    filterButtons.forEach((btn) => btn.classList.remove('active'));
                    button.classList.add('active');

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
                    const previousText = button.textContent;

                    if (!Number.isInteger(feedbackId) || feedbackId <= 0) {
                        message.className = 'feedback-message text-danger';
                        message.textContent = 'Invalid feedback ID in page data';
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Updating...';
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
                            throw new Error(data.message || 'Failed to update feedback status');
                        }

                        item.dataset.status = select.value;
                        badge.className = 'status-chip status-label chip-' + select.value;
                        badge.textContent = select.value.replace('_', ' ').toUpperCase();

                        message.className = 'feedback-message text-success';
                        message.textContent = data.message;
                        setTimeout(() => {
                            message.textContent = '';
                        }, 3000);
                    } catch (error) {
                        message.className = 'feedback-message text-danger';
                        message.textContent = error.message;
                    } finally {
                        button.disabled = false;
                        button.textContent = previousText;
                    }
                });
            });
        });
    </script>
</body>
</html>