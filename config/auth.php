<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (!is_logged_in()) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('redirect_if_logged_in')) {
    function redirect_if_logged_in(): void
    {
        if (is_logged_in()) {
            header('Location: dashboard.php');
            exit;
        }
    }
}
