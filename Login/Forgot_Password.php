<?php
require_once __DIR__ . '/../includes/security.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Forgot Password — FitStop</title>
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
                <i class="fa-solid fa-lock me-1"></i> ACCOUNT RECOVERY
            </div>
            <h2 class="brand-font text-white mb-1">FORGOT <span class="text-hazard">PASSWORD</span></h2>
            <p class="text-white small mb-4">Enter the email address associated with your account and we'll send you a
                reset code.</p>

            <form action="Process_Forgot_Password.php" method="POST">
                <?php echo fitstop_csrf_input(); ?>

                <div class="mb-4">
                    <label class="text-white small brand-font" style="font-size: 0.7rem; letter-spacing: 2px;">Email
                        Address</label>
                    <input type="email" name="email" id="email" class="form-control-custom" maxlength="50" required
                        placeholder="e.g. johndoe@email.com">
                </div>

                <?php if (isset($_GET['c'])): ?>
                    <?php if ($_GET['c'] == '1'): ?>
                        <div class="error-box"><i class="fa-solid fa-clock me-2"></i>Reset code has expired. Please try again.
                        </div>
                    <?php elseif ($_GET['c'] == '500'): ?>
                        <div class="error-box"><i class="fa-solid fa-triangle-exclamation me-2"></i>Failed to send email. Please
                            try again.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="mt-4">
                    <button type="submit" class="btn btn-hazard w-100">
                        <i class="fa-solid fa-paper-plane me-2"></i>Send Reset Code
                    </button>
                </div>
            </form>

            <hr style="border-color: #2a2a2a; margin: 1.5rem 0;">

            <a href="Login_Page.php" class="text-white small"
                style="text-decoration: none; transition: color 0.2s ease;"
                onmouseover="this.style.color='var(--hazard-yellow)'" onmouseout="this.style.color=''">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
            </a>
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
        function updateHiddenCode() {
            let code = '';
            inputs.forEach(input => code += input.value);
            hiddenInput.value = code;
        }

        document.querySelector('form').addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Sending...';

            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check me-2"></i>Send Reset Code';
            }, 15000);
        });
    </script>
</body>

</html>