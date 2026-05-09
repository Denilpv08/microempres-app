<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

ui_flash_set('info', 'El CRUD de inventario ahora se realiza desde Gestion de Inventario.');
header('Location: inventario-list.php');
exit;
