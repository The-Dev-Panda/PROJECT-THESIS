<?php
session_start();
require_once __DIR__ . '/../includes/security.php';
if (isset($_SESSION["username"]) && $_SESSION["username"] != "") {
    header('Location: success.php');
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login — FitStop</title>
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
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
        }

        .auth-left {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border: 1px solid #2a2a2a;
            border-top: 3px solid var(--hazard-yellow);
            padding: 2.5rem;
        }

        .auth-right {
            flex: 1;
            position: relative;
            overflow: hidden;
            display: none;
        }

        @media (min-width: 992px) {
            .auth-right {
                display: block;
            }
        }

        .auth-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.4);
        }

        .auth-right-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
            background: linear-gradient(to top, rgba(10, 10, 10, 0.9) 0%, transparent 60%);
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

        .btn-ghost {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 0.8rem;
            padding: 0;
            transition: color 0.2s ease;
        }

        .btn-ghost:hover {
            color: var(--hazard-yellow);
        }
    </style>
</head>

<body>

    <div class="cursor" id="cursor"></div>
    <?php include('../includes/header.php'); ?>
    <div class="row bg-dark" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: -1;">
        <div class="auth-background">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.4;">
                <use xlink:href="BACKGROUND.svg" />
            </svg>
        </div>
        <div class="auth-background">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.4;">
                <use xlink:href="BACKGROUND.svg" />
            </svg>
        </div>
    </div>
    <div class="auth-wrapper">
        <div class="auth-left">
            <div class="auth-card">
                <div class="hazard-stripes mb-4"></div>

                <div class="d-inline-block bg-warning text-black px-2 py-1 mb-3 fw-bold small brand-font">
                    <i class="fa-solid fa-bolt me-1"></i> LOGIN PORTAL
                </div>
                <h2 class="brand-font text-white mb-1">FIT<span class="text-hazard">STOP</span></h2>
                <p class="text-muted small mb-4" style="letter-spacing: 2px; text-transform: uppercase;">Login to your
                    account</p>

                <form action="Process_Login.php" method="POST">
                    <?php echo fitstop_csrf_input(); ?>

                    <div class="mb-4">
                        <label class="text-muted small brand-font"
                            style="font-size: 0.7rem; letter-spacing: 2px;">Username</label>
                        <input type="text" name="username" id="username" class="form-control-custom" required
                            placeholder="e.g. johndoe">
                    </div>

                    <div class="mb-4">
                        <label class="text-muted small brand-font"
                            style="font-size: 0.7rem; letter-spacing: 2px;">Password</label>
                        <input type="password" name="password" id="password" class="form-control-custom" required
                            placeholder="••••••••">
                    </div>

                    <?php if (isset($_GET['c']) && $_GET['c'] == 'false'): ?>
                        <div class="error-box">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Invalid username or password.
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-hazard w-100">
                            <i class="fa-solid fa-bolt me-2"></i>Login
                        </button>
                    </div>
                </form>

                <hr style="border-color: #2a2a2a; margin: 1.5rem 0;">

                <form action="Forgot_Password.php" method="POST">
                    <?php echo fitstop_csrf_input(); ?>
                    <button type="submit" class="btn-ghost">Forgot your password?</button>
                </form>
            </div>
        </div>

        <div class="auth-right">
            <img src="../images/Fitstop.png" alt="FitStop Gym">
            <div class="auth-right-overlay">
                <h1 class="brand-font text-white">Bakal Meets <span class="text-hazard">Tech</span></h1>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>

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