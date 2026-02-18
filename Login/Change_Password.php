<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Chnage Password</title>
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
        style="object-fit: cover; position: absolute; opacity: 10%; z-index: -1">
    </div>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 rounded">
            <div class="col-md-4">
                <div class="container p-4 rounded"
                    style="background-color:rgba(155, 155, 155, 0.18); box-shadow: inset;">
                    <div class="card shadow">
                        <div class="card-body p-3" style="background-color:rgba(153, 153, 153, 0.5)">
                            <h3 class="text-center mb-4">Password Change - <span style="color:rgb(197, 184, 0);">FitStop
                                    Gym</span></h3>
                            <h4 class="text-center mb-4 border border-dark shadow p-1 rounded"><?php
                            session_start();
                            echo $_SESSION['reset_password_email'];
                            ?></h4>
                            <form action="Process_Change_Password.php" method="POST">
                                <div class="col px-4">
                                    <div class="row mb-2">
                                        <p>Please enter your new password</p>
                                        <label for="password" style="font-weight: 500;">password</label>
                                        <input type="text" name="password" id="password" required
                                            placeholder="New Password"
                                            style="border: none; border-bottom: 1px solid black; background: none;">
                                    </div>
                                    <div class="row mt-4">
                                        <input type="submit" value="Chnage Password" class="btn"
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

</html>