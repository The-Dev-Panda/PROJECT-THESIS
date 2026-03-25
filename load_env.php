<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        // On production (e.g. Render), environment variables are injected by the platform.
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        if ($name === '') continue;
        // Do not override variables already set by the platform/runtime.
        if (!isset($_ENV[$name]) && getenv($name) === false) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

loadEnv(__DIR__ . '/load_env.env');
?>