<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

$searchTerm = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$allowedSort = [
    'id' => 'id',
    'nombre' => 'nombre',
    'telefono' => 'telefono',
    'correo' => 'correo',
];
$sortKey = $_GET['sort'] ?? 'id';
if (!isset($allowedSort[$sortKey])) {
    $sortKey = 'id';
}
$dirInput = strtolower($_GET['dir'] ?? 'desc');
$sortDirection = $dirInput === 'asc' ? 'ASC' : 'DESC';
$sortDirectionQuery = $sortDirection === 'ASC' ? 'asc' : 'desc';
$flash = ui_flash_get();

function clientes_build_url(array $params): string
{
    return 'clientes-list.php?' . http_build_query($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        if ($nombre !== '') {
            $stmt = mysqli_prepare($conn, 'INSERT INTO clientes (nombre, telefono, correo) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'sss', $nombre, $telefono, $correo);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Cliente creado correctamente.');
        } else {
            ui_flash_set('error', 'El nombre del cliente es obligatorio.');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        if ($id > 0 && $nombre !== '') {
            $stmt = mysqli_prepare($conn, 'UPDATE clientes SET nombre = ?, telefono = ?, correo = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $telefono, $correo, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Cliente actualizado correctamente.');
        } else {
            ui_flash_set('error', 'Selecciona un cliente valido para actualizar.');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM clientes WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Cliente eliminado correctamente.');
        } else {
            ui_flash_set('error', 'No se pudo eliminar el cliente.');
        }
    }

    $redirectParams = [
        'q' => $searchTerm,
        'page' => $page,
        'sort' => $sortKey,
        'dir' => $sortDirectionQuery,
    ];
    header('Location: ' . clientes_build_url($redirectParams));
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT id, nombre, telefono, correo FROM clientes WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $idValue, $nombreValue, $telefonoValue, $correoValue);
    if (mysqli_stmt_fetch($stmt)) {
        $editRow = [
            'id' => $idValue,
            'nombre' => $nombreValue,
            'telefono' => $telefonoValue,
            'correo' => $correoValue,
        ];
    }
    mysqli_stmt_close($stmt);
}

$totalRows = 0;
if ($searchTerm === '') {
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM clientes');
    mysqli_stmt_execute($countStmt);
} else {
    $like = '%' . $searchTerm . '%';
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM clientes WHERE nombre LIKE ? OR telefono LIKE ? OR correo LIKE ?');
    mysqli_stmt_bind_param($countStmt, 'sss', $like, $like, $like);
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
    $sql = 'SELECT id, nombre, telefono, correo FROM clientes ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $offset, $perPage);
} else {
    $like = '%' . $searchTerm . '%';
    $sql = 'SELECT id, nombre, telefono, correo FROM clientes WHERE nombre LIKE ? OR telefono LIKE ? OR correo LIKE ? ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssii', $like, $like, $like, $offset, $perPage);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $idCol, $nombreCol, $telefonoCol, $correoCol);
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = [
        'id' => $idCol,
        'nombre' => $nombreCol,
        'telefono' => $telefonoCol,
        'correo' => $correoCol,
    ];
}
mysqli_stmt_close($stmt);

function clientes_next_sort_dir(string $currentSort, string $targetSort, string $currentDir): string
{
    if ($currentSort !== $targetSort) {
        return 'asc';
    }
    return $currentDir === 'asc' ? 'desc' : 'asc';
}

$idIndicator = $sortKey === 'id' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$nombreIndicator = $sortKey === 'nombre' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$telefonoIndicator = $sortKey === 'telefono' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$correoIndicator = $sortKey === 'correo' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion de Clientes</title>
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
          <p class="eyebrow">Clientes</p>
          <h1>Gestion de Clientes</h1>
          <p class="page-subtitle">Registra, edita y elimina clientes desde el modulo centralizado.</p>
        </div>
        <a class="button-link secondary" data-loading-link href="dashboard.php"><span class="button-icon"><?php echo ui_icon('dashboard'); ?></span>Volver</a>
      </div>

      <form method="POST" class="crud-form" data-loading-form>
        <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>" />
        <?php if ($editRow) { ?>
        <input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>" />
        <?php } ?>
        <input type="text" name="nombre" placeholder="Nombre" value="<?php echo htmlspecialchars($editRow['nombre'] ?? ''); ?>" required />
        <input type="text" name="telefono" placeholder="Telefono" value="<?php echo htmlspecialchars($editRow['telefono'] ?? ''); ?>" />
        <input type="email" name="correo" placeholder="Correo" value="<?php echo htmlspecialchars($editRow['correo'] ?? ''); ?>" />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('save'); ?></span><?php echo $editRow ? 'Guardar cambios' : 'Crear cliente'; ?></button>
        <?php if ($editRow) { ?>
        <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Cancelar edicion</a>
        <?php } ?>
      </form>

      <form class="list-filter" method="GET" action="clientes-list.php" data-loading-form>
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortKey); ?>" />
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDirectionQuery); ?>" />
        <input type="text" name="q" placeholder="Buscar por nombre, telefono o correo" value="<?php echo htmlspecialchars($searchTerm); ?>" />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('search'); ?></span>Buscar</button>
        <a class="button-link secondary" data-loading-link href="clientes-list.php"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Limpiar</a>
      </form>

      <?php if (count($rows) > 0) { ?>
      <div class="table-wrapper">
        <table class="report-table">
          <tr>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'id', 'dir' => clientes_next_sort_dir($sortKey, 'id', $sortDirectionQuery)])); ?>">ID <span class="sort-indicator"><?php echo $idIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'nombre', 'dir' => clientes_next_sort_dir($sortKey, 'nombre', $sortDirectionQuery)])); ?>">Nombre <span class="sort-indicator"><?php echo $nombreIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'telefono', 'dir' => clientes_next_sort_dir($sortKey, 'telefono', $sortDirectionQuery)])); ?>">Telefono <span class="sort-indicator"><?php echo $telefonoIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'correo', 'dir' => clientes_next_sort_dir($sortKey, 'correo', $sortDirectionQuery)])); ?>">Correo <span class="sort-indicator"><?php echo $correoIndicator; ?></span></a></th>
            <th>Acciones</th>
          </tr>
          <?php foreach ($rows as $row) { ?>
          <tr>
            <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
            <td><?php echo htmlspecialchars($row['correo']); ?></td>
            <td>
              <div class="table-actions">
                <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery, 'edit' => (int) $row['id']])); ?>"><span class="button-icon"><?php echo ui_icon('edit'); ?></span>Editar</a>
                <form method="POST" class="inline-form" data-confirm="¿Eliminar cliente?" data-loading-form>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>" />
                  <button type="submit" class="danger"><span class="button-icon"><?php echo ui_icon('delete'); ?></span>Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          <?php } ?>
        </table>
      </div>
      <?php } else { ?>
      <p class="status-empty">No hay clientes registrados.</p>
      <?php } ?>

      <div class="pagination">
        <span class="pagination-info">Pagina <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalRows; ?> registros)</span>
        <div class="view-actions">
          <?php if ($page > 1) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => $page - 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Anterior</a>
          <?php } ?>
          <?php if ($page < $totalPages) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(clientes_build_url(['q' => $searchTerm, 'page' => $page + 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Siguiente</a>
          <?php } ?>
        </div>
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
