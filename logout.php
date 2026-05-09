<?php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

ui_flash_set('info', 'Sesión cerrada correctamente.');

header('Location: login.php');
exit;
