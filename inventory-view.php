<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

$flash = ui_flash_get();
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventario</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script defer src="assets/js/ui.js"></script>
  </head>
  <body>
    <div class="container panel">
      <?php if ($flash) { ?>
      <div id="page-flash" class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" data-message="1">
        <strong>Notificacion</strong>
        <p><?php echo htmlspecialchars($flash['message']); ?></p>
      </div>
      <?php } ?>

      <div class="page-header">
        <div>
          <p class="eyebrow">Modulo de inventario</p>
          <h1>Acceso a Inventario</h1>
          <p class="page-subtitle">El CRUD de productos ahora se administra desde la gestion centralizada.</p>
        </div>
        <a class="button-link secondary" data-loading-link href="dashboard.php"><span class="button-icon"><?php echo ui_icon('dashboard'); ?></span>Volver</a>
      </div>

      <div class="dashboard-card inventory">
        <span class="card-icon"><?php echo ui_icon('inventory'); ?></span>
        <div>
          <strong>Gestion de Inventario</strong>
          <p>Usa este modulo para crear, editar, buscar y eliminar productos.</p>
        </div>
      </div>

      <div class="hero-actions">
        <a class="button-link" data-loading-link href="inventario-list.php"><span class="button-icon"><?php echo ui_icon('inventory'); ?></span>Ir a gestion de inventario</a>
        <a class="button-link secondary" data-loading-link href="dashboard.php"><span class="button-icon"><?php echo ui_icon('dashboard'); ?></span>Volver al dashboard</a>
      </div>
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
