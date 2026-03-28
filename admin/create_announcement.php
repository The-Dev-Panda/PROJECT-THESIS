<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../Login/Login_Page.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Announcements | FITSTOP</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../staff/staff.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body>
    <?php include('includes/header_admin.php') ?>
    <div class="main-content">
        <section>
            <h2><i class="bi bi-plus-circle"></i> Create New Announcement</h2>

            <div class="registration-card reveal">
                <form action="process_create_announcement.php" method="POST" enctype="multipart/form-data">
                    <?php echo fitstop_csrf_input(); ?>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 3;">
                            <label>Announcement Title</label>
                            <input type="text" class="form-input" name="title" maxlength="50"
                                placeholder="Enter announcement title" required>
                        </div>

                        <div class="form-group" style="grid-column: span 3;">
                            <label>Description</label>
                            <textarea class="form-input" name="description" rows="6"
                                placeholder="Enter announcement details..." required></textarea>
                        </div>

                        <div class="form-group" style="grid-column: span 3;">
                            <label>Announcement Image (Optional)</label>
                            <input id="fileInput" type="file" class="form-input" name="image"
                                accept="image/png, image/jpeg, image/jpg" onchange="previewImage(event)">
                            <img id="imagePreview" style="display:none; max-width:200px;">
                            <small
                                style="color: var(--text-muted); font-size: 10.5px; margin-top: 5px; display: block;">PNG,
                                JPG, JPEG (Max 5MB)</small>
                            <button type="button" class="btn-secondary" id="clearBtn" onclick="clearImage()"
                                style="display:none;">
                                <i class="bi bi-x-circle"></i> Remove Image
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($_GET['error'])): ?>
                        <div
                            style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                            <?php
                            if ($_GET['error'] == "invalid_type")
                                echo '<i class="bi bi-exclamation-triangle"></i> Invalid file type';
                            if ($_GET['error'] == "file_too_large")
                                echo '<i class="bi bi-exclamation-triangle"></i> File too large';
                            if ($_GET['error'] == "database")
                                echo '<i class="bi bi-exclamation-triangle"></i> Database error';
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_GET['success'])): ?>
                        <div
                            style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                            <i class="bi bi-check-circle"></i> Announcement published successfully!
                        </div>
                    <?php endif; ?>

                    <div class="form-actions row">
                        <button type="button" class="btn-secondary col-sm-12 col-xl-2"
                            onclick="window.location.href='create_announcement.php'">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn-primary  col-sm-12 col-xl-2">
                            <i class="bi bi-megaphone-fill"></i> Publish Announcement
                        </button>
                    </div>
                </form>
            </div>
        </section>
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
                        clearBtn.style.display = 'inline-block';
                    };
                    reader.readAsDataURL(file);
                }

            }
            function clearImage() {
                const fileInput = document.getElementById('fileInput');
                const preview = document.getElementById('imagePreview');
                const clearBtn = document.getElementById('clearBtn');

                fileInput.value = ''; // remove selected file
                preview.src = '';
                preview.style.display = 'none';
                clearBtn.style.display = 'none';
            }
        </script>
        <section class="announcement-preview-section">
            <div class="row justify-content-center g-2">
                <div class="reveal-left">
                    <h2><i class="bi bi-clock-history"></i> Recent Announcements</h2>
                </div>
                <?php
                include("../Login/connection.php");

                $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                $announcements = $stmt->fetchAll();

                if (count($announcements) > 0) {
                    foreach ($announcements as $announcement) {
                        $image_html = '';
                        $counter = 0.0;
                        if ($announcement['image']) {
                            $image_src = 'data:image/jpeg;base64,' . base64_encode($announcement['image']);
                            $image_alt = $announcement['title'];
                            $image_html = "<img src='$image_src' style='width: 100%; border-radius: 2px; margin-bottom: 24px; border: 1px solid var(--border);' alt='$image_alt'>";
                        }

                        $title = $announcement['title'];
                        $description = $announcement['description'];
                        $created_by = $announcement['created_by'];
                        $date = date('M j, Y \a\t g:i A', strtotime($announcement['created_at']));

                        echo "
            <div class=' col-sm-12 col-xl-3 registration-card reveal-right' style='margin: 1%; aspect-ratio: 4/5'>
                $image_html
                <div style='border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;'>
                    <h3 style='font-family: \"Chakra Petch\", sans-serif; font-size: 20px; font-weight: 700; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px;'>$title</h3>
                    <div style='font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px;'>
                        Posted by $created_by • $date
                    </div>
                </div>
                <p style='color: var(--text-sub); font-size: 14px; line-height: 1.7; margin: 0;'>$description</p>
            </div>
            ";
                    }
                } else {
                    echo "
        <div class='registration-card' style='text-align: center; padding: 60px 20px;'>
            <h3 style='font-family: \"Chakra Petch\", sans-serif; color: var(--text-muted); text-transform: uppercase; letter-spacing: 2px; font-size: 14px; margin-bottom: 10px;'>No announcements yet</h3>
            <p style='color: var(--text-muted); font-size: 12px;'>Check back later for updates</p>
        </div>
        ";
                }
                ?>
            </div>
        </section>
    </div>
    <?php //include('includes/footer_admin.php') ?>
</body>

</html>