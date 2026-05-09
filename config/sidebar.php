<?php
$userRole = $_SESSION['user_role'] ?? 'user';
?>
<button id="sidebar-toggle" class="sidebar-toggle" aria-label="Abrir menu">
  <span></span>
  <span></span>
  <span></span>
</button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="layout-shell">
  <aside class="menu panel collapsed">
    <div class="brand-row compact">
      <img src="assets/icons/app-icon.svg" alt="Icono del sistema" class="brand-logo" />
      <div>
        <p class="eyebrow">Panel principal</p>
        <h2>Dashboard</h2>
      </div>
    </div>

    <ul class="nav-list">
      <?php if ($userRole === 'admin') { ?>
      <li><a data-loading-link href="usuarios-list.php"><span class="nav-link-icon"><?php echo ui_icon('users'); ?></span><span>Gestion de Usuarios</span></a></li>
      <?php } ?>
      <li><a data-loading-link href="clientes-list.php"><span class="nav-link-icon"><?php echo ui_icon('clients'); ?></span><span>Gestion de Clientes</span></a></li>
      <li><a data-loading-link href="inventario-list.php"><span class="nav-link-icon"><?php echo ui_icon('inventory'); ?></span><span>Gestion de Inventario</span></a></li>
      <li><a data-loading-link href="sales.php"><span class="nav-link-icon"><?php echo ui_icon('sales'); ?></span><span>Ventas</span></a></li>
      <li><a data-loading-link href="reportes.php"><span class="nav-link-icon"><?php echo ui_icon('reports'); ?></span><span>Reportes</span></a></li>
    </ul>

    <div class="view-actions">
      <a class="button-link danger" data-loading-link href="logout.php"><span class="button-icon"><?php echo ui_icon('logout'); ?></span>Cerrar sesion</a>
    </div>
  </aside>
  <main class="content panel">
