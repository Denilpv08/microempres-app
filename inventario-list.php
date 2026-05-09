<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

$isAdmin = ($_SESSION['user_role'] ?? 'user') === 'admin';

$searchTerm = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$allowedSort = [
    'id' => 'id',
    'nombre' => 'nombre',
    'stock' => 'stock',
    'precio' => 'precio',
];
$sortKey = $_GET['sort'] ?? 'id';
if (!isset($allowedSort[$sortKey])) {
    $sortKey = 'id';
}
$dirInput = strtolower($_GET['dir'] ?? 'desc');
$sortDirection = $dirInput === 'asc' ? 'ASC' : 'DESC';
$sortDirectionQuery = $sortDirection === 'ASC' ? 'asc' : 'desc';
$flash = ui_flash_get();

function inventario_build_url(array $params): string
{
    return 'inventario-list.php?' . http_build_query($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        ui_flash_set('error', 'No tienes permiso para modificar el inventario.');
        header('Location: inventario-list.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $stock = (int) ($_POST['stock'] ?? 0);
        $precio = (float) ($_POST['precio'] ?? 0);
        if ($nombre !== '') {
            $stmt = mysqli_prepare($conn, 'INSERT INTO productos (nombre, stock, precio) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sid', $nombre, $stock, $precio);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Producto creado correctamente.');
        } else {
            ui_flash_set('error', 'El nombre del producto es obligatorio.');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $stock = (int) ($_POST['stock'] ?? 0);
        $precio = (float) ($_POST['precio'] ?? 0);
        if ($id > 0 && $nombre !== '') {
            $stmt = mysqli_prepare($conn, 'UPDATE productos SET nombre = ?, stock = ?, precio = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sidi', $nombre, $stock, $precio, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Producto actualizado correctamente.');
        } else {
            ui_flash_set('error', 'Selecciona un producto valido para actualizar.');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM productos WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Producto eliminado correctamente.');
        } else {
            ui_flash_set('error', 'No se pudo eliminar el producto.');
        }
    }

    $redirectParams = [
        'q' => $searchTerm,
        'page' => $page,
        'sort' => $sortKey,
        'dir' => $sortDirectionQuery,
    ];
    header('Location: ' . inventario_build_url($redirectParams));
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT id, nombre, stock, precio FROM productos WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $idValue, $nombreValue, $stockValue, $precioValue);
    if (mysqli_stmt_fetch($stmt)) {
        $editRow = [
            'id' => $idValue,
            'nombre' => $nombreValue,
            'stock' => $stockValue,
            'precio' => $precioValue,
        ];
    }
    mysqli_stmt_close($stmt);
}

$totalRows = 0;
if ($searchTerm === '') {
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM productos');
    mysqli_stmt_execute($countStmt);
} else {
    $like = '%' . $searchTerm . '%';
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM productos WHERE nombre LIKE ?');
    mysqli_stmt_bind_param($countStmt, 's', $like);
    mysqli_stmt_execute($countStmt);
}
mysqli_stmt_bind_result($countStmt, $totalRows);
mysqli_stmt_fetch($countStmt);
mysqli_stmt_close($countStmt);

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$orderBy = $allowedSort[$sortKey] . ' ' . $sortDirection;
$rows = [];

if ($searchTerm === '') {
    $sql = 'SELECT id, nombre, stock, precio FROM productos ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $offset, $perPage);
} else {
    $like = '%' . $searchTerm . '%';
    $sql = 'SELECT id, nombre, stock, precio FROM productos WHERE nombre LIKE ? ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sii', $like, $offset, $perPage);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $idCol, $nombreCol, $stockCol, $precioCol);
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'id' => $idCol,
        'nombre' => $nombreCol,
        'stock' => $stockCol,
        'precio' => $precioCol,
    ];
}
mysqli_stmt_close($stmt);

function inventario_next_sort_dir(string $currentSort, string $targetSort, string $currentDir): string
{
    if ($currentSort !== $targetSort) {
        return 'asc';
    }
    return $currentDir === 'asc' ? 'desc' : 'asc';
}

$idIndicator = $sortKey === 'id' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$nombreIndicator = $sortKey === 'nombre' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$stockIndicator = $sortKey === 'stock' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$precioIndicator = $sortKey === 'precio' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion de Inventario</title>
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
          <p class="eyebrow">Inventario</p>
          <h1>Gestion de Inventario</h1>
          <p class="page-subtitle">Registra productos, modifica stock y controla precios desde un unico modulo.</p>
        </div>
      </div>

      <?php if ($isAdmin) { ?>
      <form method="POST" class="crud-form" data-loading-form>
        <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>" />
        <?php if ($editRow) { ?>
        <input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>" />
        <?php } ?>
        <input type="text" name="nombre" placeholder="Producto" value="<?php echo htmlspecialchars($editRow['nombre'] ?? ''); ?>" required />
        <input type="number" name="stock" placeholder="Stock" min="0" value="<?php echo htmlspecialchars((string) ($editRow['stock'] ?? '')); ?>" required />
        <input type="number" name="precio" placeholder="Precio" min="0" step="0.01" value="<?php echo htmlspecialchars((string) ($editRow['precio'] ?? '')); ?>" required />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('save'); ?></span><?php echo $editRow ? 'Guardar cambios' : 'Crear producto'; ?></button>
        <?php if ($editRow) { ?>
        <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Cancelar edicion</a>
        <?php } ?>
      </form>
      <?php } else { ?>
      <div class="alert alert-info" style="margin-bottom: 18px;">
        <strong>Modo solo lectura</strong>
        <p>No tienes permisos para modificar el inventario. Solo puedes visualizar los productos registrados.</p>
      </div>
      <?php } ?>

      <form class="list-filter" method="GET" action="inventario-list.php" data-loading-form>
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortKey); ?>" />
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDirectionQuery); ?>" />
        <input type="text" name="q" placeholder="Buscar producto" value="<?php echo htmlspecialchars($searchTerm); ?>" />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('search'); ?></span>Buscar</button>
        <a class="button-link secondary" data-loading-link href="inventario-list.php"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Limpiar</a>
      </form>

      <?php if (count($rows) > 0) { ?>
      <div class="table-wrapper">
        <table class="report-table">
          <tr>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'id', 'dir' => inventario_next_sort_dir($sortKey, 'id', $sortDirectionQuery)])); ?>">ID <span class="sort-indicator"><?php echo $idIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'nombre', 'dir' => inventario_next_sort_dir($sortKey, 'nombre', $sortDirectionQuery)])); ?>">Producto <span class="sort-indicator"><?php echo $nombreIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'stock', 'dir' => inventario_next_sort_dir($sortKey, 'stock', $sortDirectionQuery)])); ?>">Stock <span class="sort-indicator"><?php echo $stockIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'precio', 'dir' => inventario_next_sort_dir($sortKey, 'precio', $sortDirectionQuery)])); ?>">Precio <span class="sort-indicator"><?php echo $precioIndicator; ?></span></a></th>
            <th>Acciones</th>
          </tr>
          <?php foreach ($rows as $row) { ?>
          <tr>
            <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td><?php echo htmlspecialchars((string) $row['stock']); ?></td>
            <td>$<?php echo number_format((float) $row['precio'], 2, '.', ','); ?></td>
            <td>
              <?php if ($isAdmin) { ?>
              <div class="table-actions">
                <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery, 'edit' => (int) $row['id']])); ?>"><span class="button-icon"><?php echo ui_icon('edit'); ?></span>Editar</a>
                <form method="POST" class="inline-form" data-confirm="¿Eliminar producto?" data-loading-form>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>" />
                  <button type="submit" class="danger"><span class="button-icon"><?php echo ui_icon('delete'); ?></span>Eliminar</button>
                </form>
              </div>
              <?php } else { ?>
              <span class="helper-text" style="font-size:0.85rem;">—</span>
              <?php } ?>
            </td>
          </tr>
          <?php } ?>
        </table>
      </div>
      <?php } else { ?>
      <p class="status-empty">No hay productos registrados.</p>
      <?php } ?>

      <div class="pagination">
        <span class="pagination-info">Pagina <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalRows; ?> registros)</span>
        <div class="view-actions">
          <?php if ($page > 1) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => $page - 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Anterior</a>
          <?php } ?>
          <?php if ($page < $totalPages) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(inventario_build_url(['q' => $searchTerm, 'page' => $page + 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Siguiente</a>
          <?php } ?>
        </div>
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
