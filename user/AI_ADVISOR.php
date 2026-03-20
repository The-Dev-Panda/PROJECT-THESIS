<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['chat_history']);
}

$aiFlash = '';
if (isset($_SESSION['ai_flash']) && is_string($_SESSION['ai_flash'])) {
    $aiFlash = $_SESSION['ai_flash'];
    unset($_SESSION['ai_flash']);
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>AI ADVISOR</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

    <link rel="stylesheet" href="styles.css">

    <link href="../../styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

</head>

<body>
    <?php include('../includes/header.php') ?>
    <div class="row mt-5"></div>
    <div class="row mt-5"></div>
    <div class="container alert alert-info alert-dismissible fade show">
        <?php
        if ($aiFlash !== '') {
            echo '<div class="alert alert-primary">';
            echo '<i class="bi bi-robot me-2"></i>';
            echo nl2br(htmlspecialchars($aiFlash, ENT_QUOTES, 'UTF-8'));
            echo '</div>';
        }

        // Optional session chat history (not persisted in database)
        if (!empty($_SESSION['chat_history'])) {
            foreach ($_SESSION['chat_history'] as $entry) {
                if ($entry['role'] == 'user') {
                    echo '
                <div class="alert alert-secondary">
                    <i class="bi bi-person-fill me-2"></i>';
                    echo htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8');
                    echo '<div class="text-muted">' . htmlspecialchars((string)$entry['timestamp'], ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '</div>';
                } elseif ($entry['role'] == 'ai') {
                    echo '
                <div class="alert alert-info">
                    <i class="bi bi-robot me-2"></i>';
                    echo nl2br(htmlspecialchars((string)$entry['message'], ENT_QUOTES, 'UTF-8'));
                    echo '<div class="text-muted">' . htmlspecialchars((string)$entry['timestamp'], ENT_QUOTES, 'UTF-8') . '</div>';
                    echo '</div>';
                }
            }
        }
        ?>
        <div class="container d-flex gap-2 align-items-center">
            <form action="process_AI.php" method="POST" class="d-flex gap-2 flex-grow-1">
                <input type="text" name="query" placeholder="Enter your query" class="form-control" required>
                <button type="submit" class="btn btn-success">Submit</button>
            </form>
            <a href="AI_ADVISOR.php?clear=1" class="btn btn-sm btn-danger"
                onclick="return confirm('Clear all messages?')" title="Clear chat">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    </div>
    <?php include('../includes/footer.php') ?>
</body>

</html>