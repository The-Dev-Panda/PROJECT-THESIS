<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verification</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
</head>

<body class="bg-dark">
    <img src="../images/Fitstop.png" alt="FITSTOP LOGIN" class="img-fluid w-100 h-100"
        style="object-fit: cover; position: absolute; opacity: 10%;">
    </div>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 rounded">
            <div class="col-md-4">
                <div class="container p-4 rounded"
                    style="background-color:rgba(155, 155, 155, 0.18); box-shadow: inset;">
                    <div class="card shadow">
                        <div class="card-body p-3" style="background-color:rgba(153, 153, 153, 0.5)">
                            <h3 class="text-center mb-4">Verify Code - <span style="color:rgb(197, 184, 0);">FitStop
                                    Gym</span></h3>
                            <form action="Process_Verify_Password_Change.php" method="POST">
                                <div class="col px-4">
                                    <div class="row mb-2">
                                        <p>Please enter the code sent to your account</p>
                                        <label for="code" style="font-weight: 500;">6-Digit Code</label>

                                        <div class="d-flex justify-content-center gap-2 mt-2">
                                            <input type="text" maxlength="1" class="code-input text-center border-dark"
                                                data-index="0" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                            <input type="text" maxlength="1" class="code-input text-center"
                                                data-index="1" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                            <input type="text" maxlength="1" class="code-input text-center"
                                                data-index="2" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                            <input type="text" maxlength="1" class="code-input text-center"
                                                data-index="3" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                            <input type="text" maxlength="1" class="code-input text-center"
                                                data-index="4" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                            <input type="text" maxlength="1" class="code-input text-center"
                                                data-index="5" required
                                                style="width: 50px; height: 50px; font-size: 24px; border: 2px solid #ccc; border-radius: 8px;">
                                        </div>
                                        <input type="hidden" name="code" id="code">
                                    </div>
                                    <?php
                                    #debugging
                                    #date_default_timezone_set('Asia/Manila');
                                    #echo 'Time Started: ' . date('H:i:s');
                                    #debugging
                                    if (isset($_GET['c'])) {
                                        if ($_GET['c'] == '2') {
                                            echo '<span class="text-danger py-2">Invalid Code</span>';
                                        }
                                    }
                                    ?>
                                    <div class="row mt-4">
                                        <input type="submit" value="Verify" class="btn"
                                            style="background-color:rgb(197, 184, 0);">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
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
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 6);
            pastedData.split('').forEach((char, i) => {
                if (inputs[i]) {
                    inputs[i].value = char;
                }
            });
            updateHiddenCode();
            if (pastedData.length > 0) {
                inputs[Math.min(pastedData.length, 5)].focus();
            }
        });
    });

    function updateHiddenCode() {
        let code = '';
        inputs.forEach(input => {
            code += input.value;
        });
        hiddenInput.value = code;
    }
</script>

</html>