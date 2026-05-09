<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
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
$montoSubtotal = 0.0;
$montoIva = 0.0;
$montoTotal = 0.0;
$hayFiltroFecha = $desde !== '' || $hasta !== '';

$sql = '
  SELECT
    v.id,
    COALESCE(c.nombre, "Sin cliente") AS cliente,
    COALESCE(p.nombre, "Sin producto") AS producto,
    v.cantidad,
    v.subtotal,
    v.iva,
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
  mysqli_stmt_bind_result($stmt, $id, $cliente, $producto, $cantidad, $subtotal, $iva, $total, $fecha);

  while (mysqli_stmt_fetch($stmt)) {
    $ventas[] = [
      'id' => $id,
      'cliente' => $cliente,
      'producto' => $producto,
      'cantidad' => $cantidad,
      'subtotal' => $subtotal,
      'iva' => $iva,
      'total' => $total,
      'fecha' => $fecha,
    ];
    $montoSubtotal += (float) $subtotal;
    $montoIva += (float) $iva;
    $montoTotal += (float) $total;
  }

  mysqli_stmt_close($stmt);
}

function format_cop($amount): string
{
  return '$' . number_format($amount, 2, '.', ',');
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
    <?php require_once __DIR__ . '/config/sidebar.php'; ?>

      <div class="page-header">
        <div>
          <p class="eyebrow">Resumen</p>
          <h1>Reportes</h1>
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
        <div>
          <strong><?php echo $hayFiltroFecha ? 'Subtotal filtrado:' : 'Subtotal general:'; ?></strong>
          <span><?php echo format_cop($montoSubtotal); ?></span>
        </div>
        <div>
          <strong>IVA 19%:</strong>
          <span><?php echo format_cop($montoIva); ?></span>
        </div>
        <div>
          <strong><?php echo $hayFiltroFecha ? 'Total filtrado:' : 'Total general:'; ?></strong>
          <span><?php echo format_cop($montoTotal); ?></span>
        </div>
      </div>

      <?php if (count($ventas) > 0) { ?>
      <div class="table-wrapper">
      <table class="report-table">
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Producto</th>
          <th>Cantidad</th>
          <th>Subtotal</th>
          <th>IVA 19%</th>
          <th>Total</th>
          <th>Fecha</th>
        </tr>
        <?php foreach ($ventas as $venta) { ?>
        <tr>
          <td><?php echo htmlspecialchars((string) $venta['id']); ?></td>
          <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
          <td><?php echo htmlspecialchars($venta['producto']); ?></td>
          <td><?php echo htmlspecialchars((string) $venta['cantidad']); ?></td>
          <td><?php echo format_cop((float) $venta['subtotal']); ?></td>
          <td><?php echo format_cop((float) $venta['iva']); ?></td>
          <td><?php echo format_cop((float) $venta['total']); ?></td>
          <td><?php echo htmlspecialchars((string) $venta['fecha']); ?></td>
        </tr>
        <?php } ?>
      </table>
      </div>
      <?php } else { ?>
      <p class="status-empty">No hay ventas registradas para mostrar en reportes.</p>
      <?php } ?>

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
