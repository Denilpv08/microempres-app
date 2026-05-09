<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

ui_flash_set('info', 'El CRUD de clientes ahora se realiza desde Gestion de Clientes.');
header('Location: clientes-list.php');
exit;
