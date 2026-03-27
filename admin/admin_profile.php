<?php
session_start();
require_once __DIR__ . '/../includes/security.php';

if (empty($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header('Location: Login_Page.php');
    exit();
}

include("../Login/connection.php");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        // Update Profile
        if ($_POST['action'] === 'update_profile') {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);

            $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'id' => $_SESSION['id']
            ]);

            header('Location: admin_profile.php?success=profile_updated');
            exit();
        }

        // Change Password
        if ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Get current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['id']]);
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 8) {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                        $stmt->execute(['password' => $hashed, 'id' => $_SESSION['id']]);

                        header('Location: admin_profile.php?success=password_changed');
                        exit();
                    } else {
                        header('Location: admin_profile.php?error=password_too_short');
                        exit();
                    }
                } else {
                    header('Location: admin_profile.php?error=passwords_dont_match');
                    exit();
                }
            } else {
                header('Location: admin_profile.php?error=current_password_incorrect');
                exit();
            }
        }

        // Update Profile Picture
        if ($_POST['action'] === 'update_picture') {
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_picture']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $image_data = file_get_contents($_FILES['profile_picture']['tmp_name']);

                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = :picture, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                    $stmt->execute([
                        'picture' => $image_data,
                        'id' => $_SESSION['id']
                    ]);

                    header('Location: admin_profile.php?success=picture_updated');
                    exit();
                } else {
                    header('Location: admin_profile.php?error=invalid_file_type');
                    exit();
                }
            }
        }
    }
}

// Get admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'admin'");
$stmt->execute(['id' => $_SESSION['id']]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Your Profile | FITSTOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../staff/staff.css">

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
                <h1><i class="bi bi-person-circle"></i> Admin Profile</h1>
                <p>Manage your account information and settings</p>
            </div>
            <div class="topbar-right col-sm-12 col-xl-2 col-xl-offset-4">
                <a href="settings.php" class="btn-secondary" style="text-decoration: none; padding: 10px 20px;">
                    <i class="bi bi-arrow-left"></i> Back to Settings
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div
                style="background: rgba(34, 208, 122, 0.1); border: 1px solid var(--success); color: var(--success); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-check-circle"></i>
                <?php
                if ($_GET['success'] === 'profile_updated')
                    echo 'Profile updated successfully';
                elseif ($_GET['success'] === 'password_changed')
                    echo 'Password changed successfully';
                elseif ($_GET['success'] === 'picture_updated')
                    echo 'Profile picture updated successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div
                style="background: rgba(255, 71, 87, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 10px 14px; margin-bottom: 20px; font-size: 12px; text-transform: uppercase;">
                <i class="bi bi-exclamation-triangle"></i>
                <?php
                if ($_GET['error'] === 'current_password_incorrect')
                    echo 'Current password is incorrect';
                elseif ($_GET['error'] === 'passwords_dont_match')
                    echo 'New passwords do not match';
                elseif ($_GET['error'] === 'password_too_short')
                    echo 'Password must be at least 8 characters';
                elseif ($_GET['error'] === 'invalid_file_type')
                    echo 'Invalid file type. Use JPG, PNG, or GIF';
                ?>
            </div>
        <?php endif; ?>

        <!-- Profile Overview -->
        <section>
            <h2><i class="bi bi-person-badge"></i> Profile Overview</h2>
            <div class="registration-card">
                <div class="row">
                    <!-- Profile Picture -->
                    <div style="text-align: center;">
                        <?php if ($admin['profile_picture']): ?>
                            <img src="data:image/jpeg;base64,<?php echo base64_encode($admin['profile_picture']); ?>"
                                style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--hazard);"
                                class="col-sm-12 col-xl-3">
                        <?php else: ?>
                            <div class="row d-flex justify-content-center align-items-center">
                            <div class="col-sm-12"
                                style="width: 150px; height: 150px; border-radius: 50%; background: rgba(255, 204, 0, 0.1); border: 3px solid var(--hazard); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-circle" style="font-size: 80px; color: var(--hazard);"></i>
                            </div>
                            </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <button onclick="document.getElementById('pictureUpload').click()" class="btn-primary"
                                style="margin-top: 15px; padding: 8px 16px; font-size: 11px;">
                                <i class="bi bi-camera-fill"></i> Change Photo
                            </button>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="pictureForm" style="display: none;">
                            <?php echo fitstop_csrf_input(); ?>
                            <input type="hidden" name="action" value="update_picture">
                            <input type="file" name="profile_picture" id="pictureUpload" accept="image/*"
                                onchange="document.getElementById('pictureForm').submit()">
                        </form>
                    </div>

                    <!-- Profile Info -->
                    <div style="text-align: center;" class="mt-4">
                        <h3
                            style="font-family: 'Chakra Petch', sans-serif; font-size: 24px; color: var(--hazard); margin-bottom: 10px; text-transform: uppercase;">
                            <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                        </h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                            <i class="bi bi-person-badge"></i> @<?php echo htmlspecialchars($admin['username']); ?> •
                            <span
                                style="padding: 3px 8px; background: rgba(255, 204, 0, 0.1); border: 1px solid var(--hazard); color: var(--hazard); font-size: 10px; text-transform: uppercase; font-weight: 700;">Administrator</span>
                        </p>

                        <div class="row">
                            <div>
                                <label
                                    style="color: var(--text-muted); font-size: 10px; text-transform: uppercase; display: block; margin-bottom: 5px;">Email</label>
                                <p style="color: var(--text-primary); margin: 0;">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?>
                                </p>
                            </div>
                            <div>
                                <label
                                    style="color: var(--text-muted); font-size: 10px; text-transform: uppercase; display: block; margin-bottom: 5px;">Member
                                    Since</label>
                                <p style="color: var(--text-primary); margin: 0;">
                                    <i class="bi bi-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($admin['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Edit Profile -->
        <section>
            <h2><i class="bi bi-pencil-square"></i> Edit Profile Information</h2>
            <div class="registration-card">
                <form method="POST">
                    <?php echo fitstop_csrf_input(); ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row">
                        <div class="form-group col-sm-12 col-xl-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-input text-only"
                                value="<?php echo htmlspecialchars($admin['first_name']); ?>" maxlength="50" required>
                        </div>
                        <div class="form-group col-sm-12 col-xl-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-input text-only"
                                value="<?php echo htmlspecialchars($admin['last_name']); ?>" maxlength="50" required>
                        </div>
                        <div class="form-group col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input"
                                value="<?php echo htmlspecialchars($admin['email']); ?>" maxlength="100" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top: 10px;">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </form>
            </div>
        </section>

        <!-- Change Password -->
        <section>
            <h2><i class="bi bi-shield-lock"></i> Change Password</h2>
            <div class="registration-card">
                <form method="POST">
                    <?php echo fitstop_csrf_input(); ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" minlength="8" required>
                            <small
                                style="color: var(--text-muted); font-size: 10px; margin-top: 5px; display: block;">Must
                                be at least 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-input" minlength="8" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary" style="margin-top: 10px;">
                        <i class="bi bi-key-fill"></i> Change Password
                    </button>
                </form>
            </div>
        </section>

        <!-- Account Statistics -->
        <section>
            <h2><i class="bi bi-graph-up"></i> Account Activity</h2>
            <div class="row">
                <?php
                $login_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
                $login_count->execute(['id' => $_SESSION['id']]);
                ?>
                <div class="stat-box col-sm-12 col-xl-6">
                    <div class="stat-icon members">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="stat-value">Admin</div>
                        <div class="stat-label">Account Type</div>
                    </div>
                </div>
                <div class="stat-box col-sm-12 col-xl-6">
                    <div class="stat-icon registrations">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $admin['is_verified'] ? 'Verified' : 'Not Verified'; ?></div>
                        <div class="stat-label">Verification Status</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>