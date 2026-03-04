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
        <?php
        include("../Login/connection.php");

        try {
            $stmt = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC");
            $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($feedbacks) > 0) {
                foreach ($feedbacks as $feedback) {
                    $about = htmlspecialchars($feedback['about']);
                    $description = nl2br(htmlspecialchars($feedback['desc']));
                    $reporterID = htmlspecialchars($feedback['reporterID']);
                    $lastName = $feedback['last_name'] ? htmlspecialchars($feedback['last_name']) : 'Anonymous';
                    $date = date('F j, Y \a\t g:i A', strtotime($feedback['created_at']));
                    
                    // Get reporter's first name if available
                    $reporterName = $lastName;
                    if ($reporterID) {
                        $userStmt = $pdo->prepare("SELECT first_name FROM users WHERE id = :id");
                        $userStmt->execute(['id' => $reporterID]);
                        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                        if ($user && $user['first_name']) {
                            $reporterName = htmlspecialchars($user['first_name']) . ' ' . $lastName;
                        }
                    }

                    echo "
                    <div class='announcement-container'>
                        <div class='announcement-card'>
                            <div class='announcement-header'>
                                <h2 class='announcement-title'><i class='bi bi-wrench me-2'></i>$about</h2>
                                <div class='announcement-meta'>
                                    Reported by $reporterName • $date
                                </div>
                            </div>
                            <p class='announcement-description'>$description</p>
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
</body>

</html>
