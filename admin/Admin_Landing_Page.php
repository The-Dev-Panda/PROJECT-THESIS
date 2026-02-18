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
                <i class="bi bi-lightning-fill"></i> FITSTOP - <span class="text-danger">ADMIN</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="Admin_Landing_Page.php">
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
    <div class="dashboard-header">
        <div class="container">
            <h1 class="mb-2">Welcome back, Admin!</h1>
            <p class="mb-0">Here's what's happening with your gym today.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label">Total Members</p>
                            <h2 class="stat-number">1,247</h2>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> 12% from last month</small>
                        </div>
                        <div class="stat-icon border border-warning">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label">Active Staff</p>
                            <h2 class="stat-number">24</h2>
                            <small class="text-muted"><i class="bi bi-dash"></i> No change</small>
                        </div>
                        <div class="stat-icon border border-warning">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label">Revenue (MTD)</p>
                            <h2 class="stat-number">$28.5K</h2>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> 8% from last month</small>
                        </div>
                        <div class="stat-icon border border-warning">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stat-label">Check-ins Today</p>
                            <h2 class="stat-number">142</h2>
                            <small class="text-success"><i class="bi bi-arrow-up"></i> 5% from yesterday</small>
                        </div>
                        <div class="stat-icon border border-warning">
                            <i class="bi bi-door-open"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="mb-3">Member Growth</h5>
                    <canvas id="memberChart" height="80"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="mb-3">Membership Types</h5>
                    <canvas id="membershipChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <?php
        include("../Login/connection.php");

        // Pagination settings
        $records_per_page = 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $records_per_page;

        // Get total number of users
        $total_query = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'user'");
        $total_records = $total_query->fetch()['total'];
        $total_pages = ceil($total_records / $records_per_page);

        // Get users for current page
        $stmt = $pdo->prepare("SELECT username, first_name, last_name, email, last_logged_in, is_verified 
                       FROM users 
                       WHERE user_type = 'user' 
                       ORDER BY last_logged_in DESC 
                       LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();

        // Function to format last logged in time
        function timeAgo($datetime)
        {
            if (!$datetime)
                return 'Never';

            $time = strtotime($datetime);
            $diff = time() - $time;

            if ($diff < 60)
                return 'Just now';
            if ($diff < 3600)
                return floor($diff / 60) . ' minutes ago';
            if ($diff < 86400)
                return floor($diff / 3600) . ' hours ago';
            if ($diff < 604800)
                return floor($diff / 86400) . ' days ago';

            return date('M d, Y', $time);
        }
        ?>

        <!-- Recent Member Activity Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Member Activity</h5>
                        <span class="text-muted">Showing
                            <?php echo min($offset + 1, $total_records); ?>-
                            <?php echo min($offset + $records_per_page, $total_records); ?> of
                            <?php echo $total_records; ?> members
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Username</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-person-circle me-2"></i>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td>@
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td>
                                                <?php echo timeAgo($user['last_logged_in']); ?>
                                            </td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not Verified</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No members found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Member pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <!-- Previous Button -->
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>

                                <!-- Page Numbers -->
                                <?php
                                // Show max 5 page numbers at a time
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                // First page
                                if ($start_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Page number buttons -->
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Last page -->
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>">
                                            <?php echo $total_pages; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next Button -->
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <?php
        include("../login/connection.php");

        $member_growth = [];
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $month_name = date('M', strtotime("-$i months"));

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'user' AND strftime('%Y-%m', created_at) <= :date");
            $stmt->execute(['date' => $date]);
            $count = $stmt->fetch()['count'];

            $months[] = $month_name;
            $member_growth[] = $count;
        }

        // Get user distribution by type (Admin, Staff, User)
        $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
        $user_type_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $type_labels = [];
        $type_counts = [];
        $colors = ['#3498db', '#17a2b8', '#343a40', '#28a745', '#ffc107'];
        $color_index = 0;

        foreach ($user_type_data as $data) {
            $type_labels[] = ucfirst($data['user_type']);
            $type_counts[] = $data['count'];
        }
        ?>

        <script>
            // Member Growth Chart - Using real database data
            const memberCtx = document.getElementById('memberChart').getContext('2d');
            new Chart(memberCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Members',
                        data: <?php echo json_encode($member_growth); ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // User Types Distribution Chart
            const membershipCtx = document.getElementById('membershipChart').getContext('2d');
            new Chart(membershipCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($type_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($type_counts); ?>,
                        backgroundColor: ['#3498db', '#28a745', '#ffc107'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        </script>
</body>

</html>