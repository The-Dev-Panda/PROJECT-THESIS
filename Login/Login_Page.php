<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login Fitstop</title>
    <meta name="description" content="Login page for Fitstop application">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

</head>

<body class="bg-dark">
    <div class="container-fluid">
        <div class="row vh-100">
            <div class="col-md-6 col-sm-12 d-flex justify-content-center align-items-center">
                <div class="col-md-6 rounded border border-warning shadow p-4">
                    <form action="Process_Login.php" method="POST">
                        <div class="col" style="font-size: 1.2rem;">
                            <h3 class=" text-center text-warning m-3">FITSTOP GYM <span class="text-light">LOGIN</span>
                            </h3>
                            <div class="row mb-3">
                                <label for="username" class="text-warning" style="font-weight: 500;">USERNAME</label>
                                <input type="text" name="username" id="username" required placeholder="Enter username"
                                    style="border: none; border-bottom: 1px solid black; background: none;">
                            </div>
                            <div class="row mb-2">
                                <label for="password" class="text-warning" style="font-weight: 500;">PASSWORD</label>
                                <input type="password" name="password" id="password" required
                                    placeholder="Enter password"
                                    style="border: none; border-bottom: 1px solid black; background: none;">
                            </div>
                            <?php
                            if (isset($_GET['c'])) {
                                if ($_GET['c'] == 'false') {
                                    echo 'Wrong Password';
                                }
                            }
                            ?>
                            <div class="row mt-4">
                                <input type="submit" value="Login" class="btn"
                                    style="background-color:rgb(197, 184, 0);">
                            </div>
                        </div>
                    </form>
                    <form action="Forgot_Password.php" method="POST">
                        <div class="col-md-4 text-center">
                            <input type="submit" value="Forgot Password?" class="btn text-light text-center"
                                style="background-color:none;">
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-6 border-start border-warning p-0" style="box-shadow: 0px 0 10px 10px rgba(0, 0, 0, 0.5);">
                <img src="../images/Fitstop.png" alt="FITSTOP LOGIN" class="img-fluid w-100 h-100"
                    style="object-fit: cover;">
            </div>
        </div>
    </div>
</body>
<?php
session_start();
if (isset($_SESSION["username"]) && $_SESSION["username"] != "") {
    header('Location: success.php');
    exit();
}
?>

</html>