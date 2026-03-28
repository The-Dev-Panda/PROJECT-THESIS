<?php
require_once __DIR__ . '/../includes/security.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verification — FitStop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <style>
        .auth-center {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-center::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url('../images/Fitstop.png');
            background-size: cover;
            background-position: center;
            opacity: 0.05;
            z-index: 0;
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

        .code-input {
            width: 50px;
            height: 56px;
            font-size: 1.5rem;
            font-family: 'Chakra Petch', sans-serif;
            font-weight: 700;
            text-align: center;
            background: #111;
            border: 1px solid #333 !important;
            border-radius: 0 !important;
            color: var(--text-primary);
            transition: border-color 0.2s ease;
            outline: none;
        }

        .code-input:focus {
            border-color: var(--hazard-yellow) !important;
            box-shadow: none;
        }
    </style>
</head>
<body>

<div class="cursor" id="cursor"></div>

<div class="auth-center">
    <div class="auth-card">
        <div class="hazard-stripes mb-4"></div>

        <div class="d-inline-block bg-warning text-black px-2 py-1 mb-3 fw-bold small brand-font">
            <i class="fa-solid fa-shield-halved me-1"></i> VERIFICATION
        </div>
        <h2 class="brand-font text-white mb-1">VERIFY <span class="text-hazard">CODE</span></h2>
        <p class="text-white small mb-4">Enter the 6-digit code sent to your account.</p>

        <form action="Process_Verify_Password_Change.php" method="POST">
            <?php echo fitstop_csrf_input(); ?>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <input type="text" maxlength="1" class="code-input" data-index="0" required>
                <input type="text" maxlength="1" class="code-input" data-index="1" required>
                <input type="text" maxlength="1" class="code-input" data-index="2" required>
                <input type="text" maxlength="1" class="code-input" data-index="3" required>
                <input type="text" maxlength="1" class="code-input" data-index="4" required>
                <input type="text" maxlength="1" class="code-input" data-index="5" required>
            </div>
            <input type="hidden" name="code" id="code">

            <?php if (isset($_GET['c'])): ?>
                <?php if ($_GET['c'] == '2'): ?>
                    <div class="error-box"><i class="fa-solid fa-xmark me-2"></i>Invalid code. Please try again.</div>
                <?php elseif ($_GET['c'] == '3'): ?>
                    <div class="error-box"><i class="fa-solid fa-clock me-2"></i>Too many attempts. Please wait and try again.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-hazard w-100">
                    <i class="fa-solid fa-check me-2"></i>Verify
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const cursor = document.getElementById('cursor');
    document.addEventListener('mousemove', e => {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top  = e.clientY + 'px';
    });
    document.querySelectorAll('a, button, [onclick], input, select, textarea, label').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.classList.add('hovered'));
        el.addEventListener('mouseleave', () => cursor.classList.remove('hovered'));
    });

    const inputs = document.querySelectorAll('.code-input');
    const hiddenInput = document.getElementById('code');

    inputs.forEach((input, index) => {
        input.addEventListener('input', (e) => {
            if (e.target.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
            updateHiddenCode();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });
        input.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6);
            pastedData.split('').forEach((char, i) => {
                if (inputs[i]) inputs[i].value = char;
            });
            updateHiddenCode();
            if (pastedData.length > 0) inputs[Math.min(pastedData.length, 5)].focus();
        });
    });

    function updateHiddenCode() {
        let code = '';
        inputs.forEach(input => code += input.value);
        hiddenInput.value = code;
    }
</script>
</body>
</html>