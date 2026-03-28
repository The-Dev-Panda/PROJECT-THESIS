<?php
require_once __DIR__ . '/../includes/security.php';

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Change Password — FitStop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Inter:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <style>
        /* Auth-specific only */
        .auth-center {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid #2a2a2a;
            border-top: 3px solid var(--hazard-yellow);
            padding: 2.5rem;
        }

        .email-badge {
            background: #111;
            border: 1px solid #2a2a2a;
            border-left: 3px solid var(--hazard-yellow);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            color: var(--text-white);
            margin-bottom: 2rem;
        }

        .form-control-custom {
            background: transparent;
            border: none;
            border-bottom: 1px solid #333;
            color: var(--text-primary);
            padding: 0.5rem 0;
            width: 100%;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .form-control-custom::placeholder {
            color: #444;
        }

        .form-control-custom:focus {
            border-bottom-color: var(--hazard-yellow);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url('BACKGROUND.svg') repeat;
            background-size: 1/2;
            opacity: 0.4;
            z-index: -1;
        }
    </style>
</head>

<body>

    <div class="cursor" id="cursor"></div>
    <div class="auth-center">
        <div class="auth-card shadow-lg">
            <div class="hazard-stripes mb-4"></div>

            <div class="d-inline-block bg-warning text-black px-2 py-1 mb-3 fw-bold small brand-font">
                <i class="fa-solid fa-key me-1"></i> PASSWORD RESET
            </div>
            <h2 class="brand-font text-white mb-1">NEW <span class="text-hazard">PASSWORD</span></h2>
            <p class="text-white small mb-3">Set a new password for your account.</p>

            <div class="email-badge">
                <i class="fa-solid fa-user me-2 text-hazard"></i>
                <?php
                if ($_SESSION['reset_password_email'] == null) {
                    header('Location: Login_Page.php');
                    exit();

                } else {
                    echo htmlspecialchars($_SESSION['reset_password_email']);
                }
                ?>
            </div>

            <form action="Process_Change_Password.php" method="POST">
                <?php echo fitstop_csrf_input(); ?>

                <div class="mb-4">
                    <label class="text-white small brand-font" style="font-size: 0.7rem; letter-spacing: 2px;">New
                        Password</label>
                    <input type="text" name="password" id="password" class="form-control-custom" required minlength="8" maxlength="24"
                        placeholder="Enter new password (min 8 characters)">
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-hazard w-100">
                        <i class="fa-solid fa-check me-2"></i>Change Password
                    </button>
                </div>
            </form>
            <?php if (isset($_GET['c']) && $_GET['c'] == 'same'): ?>
                <div class="error-box mt-2">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>New password cannot be the same as your current
                    password.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const cursor = document.getElementById('cursor');
        document.addEventListener('mousemove', e => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });
        document.querySelectorAll('a, button, [onclick], input, select, textarea, label').forEach(el => {
            el.addEventListener('mouseenter', () => cursor.classList.add('hovered'));
            el.addEventListener('mouseleave', () => cursor.classList.remove('hovered'));
        });
    </script>
</body>

</html>