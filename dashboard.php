<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

$username = $_SESSION['username'] ?? 'Usuario';
$userRole = $_SESSION['user_role'] ?? 'user';
$flash = ui_flash_get();
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script defer src="assets/js/ui.js"></script>
  </head>
  <body>
    <?php require_once __DIR__ . '/config/sidebar.php'; ?>
        <?php if ($flash) { ?>
        <div id="page-flash" class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" data-message="1">
          <strong>Notificacion</strong>
          <p><?php echo htmlspecialchars($flash['message']); ?></p>
        </div>
        <?php } ?>

        <div class="page-header">
          <div>
            <p class="eyebrow">Bienvenido</p>
            <h1>Hola, <?php echo htmlspecialchars($username); ?></h1>
            <p class="page-subtitle">Centraliza la gestion operativa desde aqui. Los accesos directos llevan solo a los modulos de administracion que usas para el CRUD.</p>
          </div>
          <?php if ($userRole === 'admin') { ?>
          <a class="button-link secondary" data-loading-link href="usuarios-list.php"><span class="button-icon"><?php echo ui_icon('users'); ?></span>Ir a usuarios</a>
          <?php } ?>
        </div>

        <div class="dashboard-grid">
          <?php if ($userRole === 'admin') { ?>
          <a class="dashboard-card users" data-loading-link href="usuarios-list.php">
            <span class="card-icon"><?php echo ui_icon('users'); ?></span>
            <div>
              <strong>Gestion de Usuarios</strong>
              <p>Altas, cambios y bajas de acceso del sistema.</p>
            </div>
          </a>
          <?php } ?>
          <a class="dashboard-card clients" data-loading-link href="clientes-list.php">
            <span class="card-icon"><?php echo ui_icon('clients'); ?></span>
            <div>
              <strong>Gestion de Clientes</strong>
              <p>Todo el CRUD de clientes desde el modulo dedicado.</p>
            </div>
          </a>
          <a class="dashboard-card inventory" data-loading-link href="inventario-list.php">
            <span class="card-icon"><?php echo ui_icon('inventory'); ?></span>
            <div>
              <strong>Gestion de Inventario</strong>
              <p>Productos, stock y precios en una sola pantalla.</p>
            </div>
          </a>
          <a class="dashboard-card sales" data-loading-link href="sales.php">
            <span class="card-icon"><?php echo ui_icon('sales'); ?></span>
            <div>
              <strong>Ventas</strong>
              <p>Registro de operaciones comerciales.</p>
            </div>
          </a>
          <a class="dashboard-card reports" data-loading-link href="reportes.php">
            <span class="card-icon"><?php echo ui_icon('reports'); ?></span>
            <div>
              <strong>Reportes</strong>
              <p>Consulta indicadores y movimientos clave.</p>
            </div>
          </a>
        </div>
      </main>
    </div>

    <div id="page-loader" class="page-loader" hidden>
      <div class="loader-card">
        <span class="loader-icon"><?php echo ui_icon('loading'); ?></span>
        <strong>Cargando...</strong>
        <span>Procesando la solicitud.</span>
      </div>
    </div>
  </body>
</html>
