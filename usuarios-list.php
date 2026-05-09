<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    ui_flash_set('error', 'No tienes permiso para acceder a esta sección.');
    header('Location: dashboard.php');
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$allowedSort = [
    'id' => 'id',
    'usuario' => 'usuario',
];
$sortKey = $_GET['sort'] ?? 'id';
if (!isset($allowedSort[$sortKey])) {
    $sortKey = 'id';
}
$dirInput = strtolower($_GET['dir'] ?? 'desc');
$sortDirection = $dirInput === 'asc' ? 'ASC' : 'DESC';
$sortDirectionQuery = $sortDirection === 'ASC' ? 'asc' : 'desc';
$flash = ui_flash_get();

function usuarios_build_url(array $params): string
{
    return 'usuarios-list.php?' . http_build_query($params);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($usuario !== '' && $password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'INSERT INTO usuarios (usuario, password) VALUES (?, ?)');
            mysqli_stmt_bind_param($stmt, 'ss', $usuario, $passwordHash);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            ui_flash_set('success', 'Usuario creado correctamente.');
        } else {
            ui_flash_set('error', 'Completa usuario y contraseña para crear el registro.');
        }
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $usuario = trim($_POST['usuario'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($id > 0 && $usuario !== '') {
            $roleStmt = mysqli_prepare($conn, 'SELECT rol FROM usuarios WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($roleStmt, 'i', $id);
            mysqli_stmt_execute($roleStmt);
            mysqli_stmt_bind_result($roleStmt, $targetRole);
            mysqli_stmt_fetch($roleStmt);
            mysqli_stmt_close($roleStmt);

            if ($targetRole === 'admin') {
                ui_flash_set('error', 'No puedes editar el usuario administrador del sistema.');
            } else {
                if ($password === '') {
                    $stmt = mysqli_prepare($conn, 'UPDATE usuarios SET usuario = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'si', $usuario, $id);
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($conn, 'UPDATE usuarios SET usuario = ?, password = ? WHERE id = ?');
                    mysqli_stmt_bind_param($stmt, 'ssi', $usuario, $passwordHash, $id);
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                ui_flash_set('success', 'Usuario actualizado correctamente.');
            }
        } else {
            ui_flash_set('error', 'Selecciona un usuario valido para actualizar.');
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $roleStmt = mysqli_prepare($conn, 'SELECT rol FROM usuarios WHERE id = ? LIMIT 1');
            mysqli_stmt_bind_param($roleStmt, 'i', $id);
            mysqli_stmt_execute($roleStmt);
            mysqli_stmt_bind_result($roleStmt, $targetRole);
            mysqli_stmt_fetch($roleStmt);
            mysqli_stmt_close($roleStmt);

            if ($targetRole === 'admin') {
                ui_flash_set('error', 'No puedes eliminar el usuario administrador del sistema.');
            } elseif ($id === (int) $_SESSION['user_id']) {
                ui_flash_set('error', 'No puedes eliminar tu propia cuenta.');
            } else {
                $stmt = mysqli_prepare($conn, 'DELETE FROM usuarios WHERE id = ?');
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                ui_flash_set('success', 'Usuario eliminado correctamente.');
            }
        } else {
            ui_flash_set('error', 'No se pudo eliminar el usuario.');
        }
    }

    $redirectParams = [
        'q' => $searchTerm,
        'page' => $page,
        'sort' => $sortKey,
        'dir' => $sortDirectionQuery,
    ];
    header('Location: ' . usuarios_build_url($redirectParams));
    exit;
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT id, usuario FROM usuarios WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $idValue, $usuarioValue);
    if (mysqli_stmt_fetch($stmt)) {
        $editRow = ['id' => $idValue, 'usuario' => $usuarioValue];
    }
    mysqli_stmt_close($stmt);
}

$totalRows = 0;
if ($searchTerm === '') {
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM usuarios');
    mysqli_stmt_execute($countStmt);
} else {
    $like = '%' . $searchTerm . '%';
    $countStmt = mysqli_prepare($conn, 'SELECT COUNT(*) FROM usuarios WHERE usuario LIKE ?');
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
    $sql = 'SELECT id, usuario, rol FROM usuarios ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $offset, $perPage);
} else {
    $like = '%' . $searchTerm . '%';
    $sql = 'SELECT id, usuario, rol FROM usuarios WHERE usuario LIKE ? ORDER BY ' . $orderBy . ' LIMIT ?, ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sii', $like, $offset, $perPage);
}
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $rowId, $rowUsuario, $rowRol);
while (mysqli_stmt_fetch($stmt)) {
    $rows[] = ['id' => $rowId, 'usuario' => $rowUsuario, 'rol' => $rowRol];
}
mysqli_stmt_close($stmt);

function usuarios_next_sort_dir(string $currentSort, string $targetSort, string $currentDir): string
{
    if ($currentSort !== $targetSort) {
        return 'asc';
    }
    return $currentDir === 'asc' ? 'desc' : 'asc';
}

$idIndicator = $sortKey === 'id' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
$usuarioIndicator = $sortKey === 'usuario' ? ($sortDirectionQuery === 'asc' ? '↑' : '↓') : '';
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion de Usuarios</title>
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
          <p class="eyebrow">Usuarios</p>
          <h1>Gestion de Usuarios</h1>
          <p class="page-subtitle">Administra cuentas, contraseñas y accesos desde aqui.</p>
        </div>
      </div>

      <form method="POST" class="crud-form" data-loading-form>
        <input type="hidden" name="action" value="<?php echo $editRow ? 'update' : 'create'; ?>" />
        <?php if ($editRow) { ?>
        <input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>" />
        <?php } ?>
        <input type="text" name="usuario" placeholder="Usuario" value="<?php echo htmlspecialchars($editRow['usuario'] ?? ''); ?>" required />
        <input type="password" name="password" placeholder="<?php echo $editRow ? 'Nueva contraseña (opcional)' : 'Contraseña'; ?>" <?php echo $editRow ? '' : 'required'; ?> />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('save'); ?></span><?php echo $editRow ? 'Guardar cambios' : 'Crear usuario'; ?></button>
        <?php if ($editRow) { ?>
        <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Cancelar edicion</a>
        <?php } ?>
      </form>

      <form class="list-filter" method="GET" action="usuarios-list.php" data-loading-form>
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortKey); ?>" />
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDirectionQuery); ?>" />
        <input type="text" name="q" placeholder="Buscar usuario" value="<?php echo htmlspecialchars($searchTerm); ?>" />
        <button type="submit"><span class="button-icon"><?php echo ui_icon('search'); ?></span>Buscar</button>
        <a class="button-link secondary" data-loading-link href="usuarios-list.php"><span class="button-icon"><?php echo ui_icon('close'); ?></span>Limpiar</a>
      </form>

      <?php if (count($rows) > 0) { ?>
      <div class="table-wrapper">
        <table class="report-table">
          <tr>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'id', 'dir' => usuarios_next_sort_dir($sortKey, 'id', $sortDirectionQuery)])); ?>">ID <span class="sort-indicator"><?php echo $idIndicator; ?></span></a></th>
            <th><a class="sort-link" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => 1, 'sort' => 'usuario', 'dir' => usuarios_next_sort_dir($sortKey, 'usuario', $sortDirectionQuery)])); ?>">Usuario <span class="sort-indicator"><?php echo $usuarioIndicator; ?></span></a></th>
            <th>Acciones</th>
          </tr>
          <?php foreach ($rows as $row) { ?>
          <tr>
            <td><?php echo htmlspecialchars((string) $row['id']); ?></td>
            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
            <td>
              <?php if (($row['rol'] ?? 'user') === 'admin') { ?>
                <span class="admin-badge">Administrador</span>
              <?php } else { ?>
              <div class="table-actions">
                <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => $page, 'sort' => $sortKey, 'dir' => $sortDirectionQuery, 'edit' => (int) $row['id']])); ?>"><span class="button-icon"><?php echo ui_icon('edit'); ?></span>Editar</a>
                <form method="POST" class="inline-form" data-confirm="¿Eliminar usuario?" data-loading-form>
                  <input type="hidden" name="action" value="delete" />
                  <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>" />
                  <button type="submit" class="danger"><span class="button-icon"><?php echo ui_icon('delete'); ?></span>Eliminar</button>
                </form>
              </div>
              <?php } ?>
            </td>
          </tr>
          <?php } ?>
        </table>
      </div>
      <?php } else { ?>
      <p class="status-empty">No hay usuarios registrados.</p>
      <?php } ?>

      <div class="pagination">
        <span class="pagination-info">Pagina <?php echo $page; ?> de <?php echo $totalPages; ?> (<?php echo $totalRows; ?> registros)</span>
        <div class="view-actions">
          <?php if ($page > 1) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => $page - 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Anterior</a>
          <?php } ?>
          <?php if ($page < $totalPages) { ?>
          <a class="button-link secondary" data-loading-link href="<?php echo htmlspecialchars(usuarios_build_url(['q' => $searchTerm, 'page' => $page + 1, 'sort' => $sortKey, 'dir' => $sortDirectionQuery])); ?>">Siguiente</a>
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
