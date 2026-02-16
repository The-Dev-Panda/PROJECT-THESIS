<?php
session_start();
if (empty($_SESSION["username"])) {
    print ("<h1>Login First</h1>");
    echo "<br> Login Here:<a href='Login_Page.php'>Login Again</a>";
    exit();
} else {
    print ("Login Success. Welcome, " . $_SESSION["username"] . "  !  " . $_SESSION["user_type"] . "!");

    echo "<br>Session save path: " . session_save_path();
}
#redirect
if(!empty($_SESSION["username"])){
    if($_SESSION["user_type"] == "admin"){
        header('Location: ../ADMIN_PAGE_CHANGE.php'); #CHANGE THIS TO ADMIN PAGE
        exit();
    } else if($_SESSION["user_type"] == "user"){
        header('Location: ../USER_PAGE_CHANGE.php'); #CHANGE THIS TO USER PAGE
        exit();
    } else if($_SESSION["user_type"] == "staff"){
        header('Location: ../STAFF_PAGE_CHANGE.php'); #CHANGE THIS TO STAFF PAGE
        exit();
    } else {
        echo "Invalid user type.";
        header('Location: ../Login_Page.php');
        session_destroy();
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Welcome</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <form action="Logout.php" method="POST">
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>