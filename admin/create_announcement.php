<?php
session_start();

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
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
<style>
    body {
        background-color: #f8f9fa;
    }

    .form-card {
        background: white;
        border-radius: 10px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .image-preview {
        max-width: 100%;
        max-height: 300px;
        margin-top: 10px;
        border-radius: 8px;
        display: none;
    }

    .custom-file-upload {
        border: 2px dashed #ccc;
        display: inline-block;
        padding: 40px;
        cursor: pointer;
        width: 100%;
        text-align: center;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .custom-file-upload:hover {
        border-color: #3498db;
        background-color: #f8f9fa;
    }

    .custom-file-upload i {
        font-size: 48px;
        color: #3498db;
        margin-bottom: 10px;
    }

    .announcement-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .announcement-card {
        background: white;
        border-radius: 12px;
        padding: 40px;
        max-width: 800px;
        width: 100%;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .announcement-image {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 24px;
        max-height: 500px;
        object-fit: cover;
    }

    .announcement-title {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 16px;
    }

    .announcement-description {
        font-size: 18px;
        color: #666;
        line-height: 1.8;
        margin-bottom: 24px;
    }

    .announcement-meta {
        font-size: 14px;
        color: #999;
        padding-top: 16px;
        border-top: 1px solid #eee;
    }

    .no-announcements {
        text-align: center;
        color: #999;
        padding: 60px 20px;
    }
</style>

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
                        <a class="nav-link active" href="create_announcement.php">
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
    <div class="container pb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                <h1 class="mb-0 text-dark"><i class="bi bi-megaphone-fill me-2 text-dark"></i>Create Announcement</h1>
                    <form action="process_create_announcement.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="title" class="form-label fw-bold">
                                <i class="bi bi-card-heading me-2"></i>Announcement Title
                            </label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title"
                                placeholder="Enter announcement title" required>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">
                                <i class="bi bi-text-paragraph me-2"></i>Description
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="6"
                                placeholder="Enter announcement details..." required></textarea>
                            <small class="text-muted">Provide detailed information about your announcement</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-image me-2"></i>Announcement Image (Optional)
                            </label>
                            <label for="image" class="custom-file-upload">
                                <div>
                                    <i class="bi bi-cloud-upload"></i>
                                    <p class="mb-0"><strong>Click to upload image</strong></p>
                                    <small class="text-muted">PNG, JPG, JPEG (Max 5MB)</small>
                                </div>
                            </label>
                            <input type="file" class="d-none" id="image" name="image"
                                accept="image/png, image/jpeg, image/jpg" onchange="previewImage(event)">
                            <img id="imagePreview" class="image-preview" alt="Image preview">
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <?php
                            if (!empty($_GET['error']) && $_GET['error'] = "invalid_type") {
                                echo '<span class="text-danger">invalid File type</span>';
                            } elseif (!empty($_GET['error']) && $_GET['error'] = "file_too_large") {
                                echo '<span class="text-danger">File too large</span>';
                            }
                            ?>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-megaphone-fill me-2"></i>Publish Announcement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');

            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    event.target.value = '';
                    preview.style.display = 'none';
                    return;
                }

                const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only PNG, JPG, and JPEG files are allowed');
                    event.target.value = '';
                    preview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

    <div class="page-header">
        <div class="container">
            <h1 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>View Recent Announcements</h1>
        </div>
    </div>
    <?php
    include("../Login/connection.php");

    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll();
    if (count($announcements) > 0) {
        foreach ($announcements as $announcement) {
            $image_html = '';
            if ($announcement['image']) {
                $image_src = 'data:image/jpeg;base64,' . base64_encode($announcement['image']);
                $image_alt = htmlspecialchars($announcement['title']);
                $image_html = "<img src='$image_src' class='announcement-image' alt='$image_alt'>";
            }

            $title = htmlspecialchars($announcement['title']);
            $description = nl2br(htmlspecialchars($announcement['description']));
            $created_by = htmlspecialchars($announcement['created_by']);
            $date = date('F j, Y \a\t g:i A', strtotime($announcement['created_at']));

            echo "
            <div class='announcement-container'>
                <div class='announcement-card'>
                    $image_html
                    <h1 class='announcement-title'>$title</h1>
                    <p class='announcement-description'>$description</p>
                    <div class='announcement-meta'>
                        Posted by $created_by • $date
                    </div>
                </div>
            </div>
            ";
        }
    } else {
        echo "
        <div class='announcement-container'>
            <div class='no-announcements'>
                <h3>No announcements yet</h3>
                <p>Check back later for updates</p>
            </div>
        </div>
        ";
    }
    ?>
</body>

</html>