<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function fitstop_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function fitstop_csrf_input(): string
{
    $token = htmlspecialchars(fitstop_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function fitstop_validate_csrf_or_exit(?string $token): void
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || !is_string($token) || $token === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function fitstop_require_user_session(): int
{
    if (empty($_SESSION['id']) || strtolower((string)($_SESSION['user_type'] ?? '')) !== 'user') {
        header('Location: ../Login/Login_Page.php');
        exit();
    }

    return (int)$_SESSION['id'];
}
