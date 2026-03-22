<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Announcements</title>
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
    <?php include('includes/header_admin.php') ?>
    <div class="container pb-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <h1 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>Create Announcement</h1>
                    <form action="process_create_announcement.php" method="POST" enctype="multipart/form-data">
                        <?php echo fitstop_csrf_input(); ?>
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
                            <button type="submit" class="btn form-button">
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
                    <div class='announcement-header'>
                    <h1 class='announcement-title'>$title</h1>
                    <div class='announcement-meta'>
                        Posted by $created_by • $date
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
                <h3>No announcements yet</h3>
                <p>Check back later for updates</p>
            </div>
        </div>
        ";
    }
    ?>
    <?php include('includes/footer_admin.php') ?>
</body>

</html>