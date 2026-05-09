<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_login();

if (!isset($_SESSION['reportes_filtro_desde'])) {
  $_SESSION['reportes_filtro_desde'] = '';
}
if (!isset($_SESSION['reportes_filtro_hasta'])) {
  $_SESSION['reportes_filtro_hasta'] = '';
}

if (isset($_GET['reset'])) {
  $_SESSION['reportes_filtro_desde'] = '';
  $_SESSION['reportes_filtro_hasta'] = '';
  header('Location: reportes.php');
  exit;
}

$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
  $desde = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
  $hasta = '';
}

if ($desde === '' && $hasta === '' && ($_SESSION['reportes_filtro_desde'] !== '' || $_SESSION['reportes_filtro_hasta'] !== '')) {
  $desde = (string) $_SESSION['reportes_filtro_desde'];
  $hasta = (string) $_SESSION['reportes_filtro_hasta'];
}

$_SESSION['reportes_filtro_desde'] = $desde;
$_SESSION['reportes_filtro_hasta'] = $hasta;

$ventas = [];
$montoTotal = 0.0;
$hayFiltroFecha = $desde !== '' || $hasta !== '';

$sql = '
  SELECT
    v.id,
    COALESCE(c.nombre, "Sin cliente") AS cliente,
    COALESCE(p.nombre, "Sin producto") AS producto,
    v.cantidad,
    v.total,
    v.fecha
  FROM ventas v
  LEFT JOIN clientes c ON c.id = v.cliente_id
  LEFT JOIN productos p ON p.id = v.producto_id
  WHERE 1 = 1
';

$types = '';
$params = [];

if ($desde !== '') {
  $sql .= ' AND DATE(v.fecha) >= ?';
  $types .= 's';
  $params[] = $desde;
}

if ($hasta !== '') {
  $sql .= ' AND DATE(v.fecha) <= ?';
  $types .= 's';
  $params[] = $hasta;
}

$sql .= ' ORDER BY v.fecha DESC';

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
  if (count($params) === 1) {
    mysqli_stmt_bind_param($stmt, $types, $params[0]);
  } elseif (count($params) === 2) {
    mysqli_stmt_bind_param($stmt, $types, $params[0], $params[1]);
  }

  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $id, $cliente, $producto, $cantidad, $total, $fecha);

  while (mysqli_stmt_fetch($stmt)) {
    $ventas[] = [
      'id' => $id,
      'cliente' => $cliente,
      'producto' => $producto,
      'cantidad' => $cantidad,
      'total' => $total,
      'fecha' => $fecha,
    ];
    $montoTotal += (float) $total;
  }

  mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reportes</title>
    <link rel="stylesheet" href="assets/css/style.css" />
  </head>
  <body>
    <div class="container panel">
      <div class="page-header">
        <div>
          <p class="eyebrow">Resumen</p>
          <h1>Reportes</h1>
        </div>
        <div class="view-actions">
          <a class="button-link secondary" href="dashboard.php">Volver</a>
        </div>
      </div>
      

      <form class="list-filter" method="GET" action="reportes.php">
        <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>" />
        <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>" />
        <button type="submit">Filtrar</button>
        <a class="button-link secondary" href="reportes.php?reset=1">Limpiar</a>
      </form>
      <p class="helper-text">El filtro por fecha es opcional. Si no seleccionas fechas, se muestran todas las ventas.</p>

      <div class="report-summary">
        <strong><?php echo $hayFiltroFecha ? 'Total filtrado:' : 'Total general:'; ?></strong>
        <span>$<?php echo number_format($montoTotal, 2, '.', ','); ?></span>
      </div>

      <?php if (count($ventas) > 0) { ?>
      <div class="table-wrapper">
      <table class="report-table">
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Producto</th>
          <th>Cantidad</th>
          <th>Total</th>
          <th>Fecha</th>
        </tr>
        <?php foreach ($ventas as $venta) { ?>
        <tr>
          <td><?php echo htmlspecialchars((string) $venta['id']); ?></td>
          <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
          <td><?php echo htmlspecialchars($venta['producto']); ?></td>
          <td><?php echo htmlspecialchars((string) $venta['cantidad']); ?></td>
          <td>$<?php echo number_format((float) $venta['total'], 2, '.', ','); ?></td>
          <td><?php echo htmlspecialchars((string) $venta['fecha']); ?></td>
        </tr>
        <?php } ?>
      </table>
      </div>
      <?php } else { ?>
      <p class="status-empty">No hay ventas registradas para mostrar en reportes.</p>
      <?php } ?>
    </div>
  </body>
</html>
