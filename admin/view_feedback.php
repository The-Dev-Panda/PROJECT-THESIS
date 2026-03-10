<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Feedback - FITSTOP Admin</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
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
            <a class="navbar-brand brand-front" href="../index.php.php">
                <i class="bi bi-lightning-fill"></i> FITSTOP - <span class="text-danger">Admin</span>
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
                        <a class="nav-link" href="view_inventory.php">
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
                        <a class="nav-link active" href="view_feedback.php">
                            <i class="bi bi-chat-square-text"></i> Feedback
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
            <h1 class="mb-0"><i class="bi bi-chat-square-text-fill me-2"></i>Machine Feedback</h1>
        </div>
    </div>

    <div class="container pb-5">
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-light filter-btn active" data-filter="all">ALL</button>
            <button class="btn btn-sm btn-outline-light filter-btn" data-filter="member">MEMBER</button>
            <button class="btn btn-sm btn-outline-light filter-btn" data-filter="guest">GUEST</button>
        </div>

        <?php
        include("../Login/connection.php");

        try {
            $stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC");
            $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($feedbacks) > 0) {
                foreach ($feedbacks as $feedback) {
                    $id = htmlspecialchars($feedback['id']);
                    $about = htmlspecialchars($feedback['about']);
                    $description = nl2br(htmlspecialchars($feedback['desc']));
                    $reporterID = $feedback['reporterID'];
                    $lastName = $feedback['last_name'] ? htmlspecialchars($feedback['last_name']) : 'Anonymous';
                    $date = date('F j, Y \a\t g:i A', strtotime($feedback['created_at']));
                    $status = isset($feedback['status']) ? htmlspecialchars($feedback['status']) : 'pending';
                    
                    // Determine if guest or member
                    $isGuest = ($reporterID === null || $reporterID === '');
                    $filterType = $isGuest ? 'guest' : 'member';
                    
                    // Get reporter name
                    if ($isGuest) {
                        $reporterName = 'Guest';
                    } else {
                        $reporterName = $lastName;
                        $userStmt = $pdo->prepare("SELECT first_name FROM users WHERE id = :id");
                        $userStmt->execute(['id' => $reporterID]);
                        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                        if ($user && $user['first_name']) {
                            $reporterName = htmlspecialchars($user['first_name']) . ' ' . $lastName;
                        }
                    }

                    echo "
                    <div class='announcement-container feedback-item' data-feedback-id='$id' data-filter='$filterType'>
                        <div class='announcement-card'>
                            <div class='announcement-header'>
                                <h2 class='announcement-title'><i class='bi bi-wrench me-2'></i>$about</h2>
                                <div class='announcement-meta'>
                                    Reported by $reporterName • $date
                                </div>
                            </div>
                            <p class='announcement-description'>$description</p>
                            
                            <div class='mt-3' style='border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;'>
                                <label class='form-label' style='font-size: 0.9rem;'>Status:</label>
                                <select class='form-select form-select-sm status-select' data-feedback-id='$id' style='max-width: 200px; display: inline-block;'>
                                    <option value='pending'" . ($status === 'pending' ? ' selected' : '') . ">Pending</option>
                                    <option value='in_progress'" . ($status === 'in_progress' ? ' selected' : '') . ">In Progress</option>
                                    <option value='resolved'" . ($status === 'resolved' ? ' selected' : '') . ">Resolved</option>
                                    <option value='closed'" . ($status === 'closed' ? ' selected' : '') . ">Closed</option>
                                </select>
                                <button class='btn btn-sm btn-primary ms-2 update-feedback-btn' data-feedback-id='$id'>Update</button>
                                <span class='feedback-message ms-2' style='font-size: 0.85rem;'></span>
                            </div>
                        </div>
                    </div>
                    ";
                }
            } else {
                echo "
                <div class='announcement-container'>
                    <div class='no-announcements'>
                        <i class='bi bi-chat-square-text' style='font-size: 3rem; opacity: 0.5;'></i>
                        <h3>No feedback yet</h3>
                        <p>Member feedback will appear here</p>
                    </div>
                </div>
                ";
            }
        } catch (PDOException $e) {
            echo "
            <div class='announcement-container'>
                <div class='no-announcements'>
                    <i class='bi bi-exclamation-triangle text-danger' style='font-size: 3rem;'></i>
                    <h3>Error loading feedback</h3>
                    <p>Please try again later</p>
                </div>
            </div>
            ";
        }
        ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const feedbackItems = document.querySelectorAll('.feedback-item');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter feedback items
                feedbackItems.forEach(item => {
                    if (filter === 'all' || item.dataset.filter === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Update status functionality
        const updateButtons = document.querySelectorAll('.update-feedback-btn');
        
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const feedbackId = this.dataset.feedbackId;
                const container = this.closest('[data-feedback-id]');
                const statusSelect = container.querySelector('.status-select');
                const messageSpan = container.querySelector('.feedback-message');
                
                const newStatus = statusSelect.value;
                
                this.disabled = true;
                this.textContent = 'Updating...';
                messageSpan.textContent = '';
                
                fetch('update_feedback_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: feedbackId,
                        status: newStatus
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        messageSpan.className = 'feedback-message ms-2 text-success';
                        messageSpan.textContent = '✓ ' + data.message;
                        setTimeout(() => {
                            messageSpan.textContent = '';
                        }, 3000);
                    } else {
                        messageSpan.className = 'feedback-message ms-2 text-danger';
                        messageSpan.textContent = '✗ ' + data.message;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    messageSpan.className = 'feedback-message ms-2 text-danger';
                    messageSpan.textContent = '✗ Error: ' + error.message;
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Update';
                });
            });
        });
    });
    </script>

</body>
</html>