<?php
require_once __DIR__ . '/auth_user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout</title>
  <link rel="stylesheet" href="user.css" />
</head>
<body>
  <main class="main-content" style="min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div class="profile-card" style="max-width:520px;width:100%;padding:2rem;">
      <h2>Sign out</h2>
      <p>Are you sure you want to log out of your account?</p>
      <form action="../Login/logout.php" method="POST">
        <?php echo fitstop_csrf_input(); ?>
        <div style="display:flex;gap:12px;margin-top:16px;">
          <a href="user.php" class="btn-action secondary" style="text-decoration:none;">Cancel</a>
          <button type="submit" class="btn-action">Logout</button>
        </div>
      </form>
    </div>
  </main>
</body>
</html>

