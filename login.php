<?php

require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';

redirect_if_logged_in();

$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        ui_flash_set('error', 'Completa usuario y contraseña.');
    } else {
        $stmt = mysqli_prepare($conn, 'SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $usuario);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userId, $userName, $storedPassword, $userRole);

        if (mysqli_stmt_fetch($stmt)) {
            $isLegacyPlaintext = hash_equals((string) $storedPassword, $password);
            $isValidHash = password_verify($password, (string) $storedPassword);

            if ($isLegacyPlaintext || $isValidHash) {
                $_SESSION['user_id'] = (int) $userId;
                $_SESSION['username'] = $userName;
                $_SESSION['user_role'] = $userRole;
                mysqli_stmt_close($stmt);

                if ($isLegacyPlaintext || password_needs_rehash((string) $storedPassword, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upgradeStmt = mysqli_prepare($conn, 'UPDATE usuarios SET password = ? WHERE id = ?');
                    mysqli_stmt_bind_param($upgradeStmt, 'si', $newHash, $userId);
                    mysqli_stmt_execute($upgradeStmt);
                    mysqli_stmt_close($upgradeStmt);
                }

                header('Location: dashboard.php');
                exit;
            }
        }

        mysqli_stmt_close($stmt);
        ui_flash_set('error', 'Usuario o contraseña incorrectos.');
    }

    header('Location: login.php');
    exit;
}

$flash = ui_flash_get();
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Iniciar sesión — Mi Microempresa</title>
        <link rel="stylesheet" href="assets/css/style.css" />
        <script defer src="assets/js/ui.js"></script>
    </head>
    <body class="auth-page">
        <div class="auth-bg-decor"></div>

        <div class="auth-card">
            <div class="auth-header">
                <img src="assets/icons/app-icon.svg" alt="Icono de la app" class="auth-logo" />
                <div>
                    <p class="eyebrow">Sistema de gestión</p>
                    <h1>Microempresa</h1>
                </div>
            </div>

            <p class="auth-welcome">Ingresa tus credenciales para acceder al panel de administración.</p>

            <?php if ($flash) { ?>
            <div id="page-flash" class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" data-message="1">
                <strong>Notificación</strong>
                <p><?php echo htmlspecialchars($flash['message']); ?></p>
            </div>
            <?php } ?>

            <form action="login.php" method="POST" class="auth-form" data-loading-form>
                <div class="auth-field">
                    <span class="auth-field-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6"/></svg>
                    </span>
                    <input type="text" name="usuario" placeholder="Usuario" value="<?php echo htmlspecialchars($usuario); ?>" required autocomplete="username" />
                </div>

                <div class="auth-field">
                    <span class="auth-field-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    </span>
                    <input type="password" name="password" placeholder="Contraseña" required autocomplete="current-password" />
                </div>

                <button type="submit">
                    <span class="button-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6"/><path d="M21 3 9 15"/></svg>
                    </span>
                    Ingresar
                </button> 
            </form>

            <p class="auth-footer">&copy; <?php echo date('Y'); ?> Microempresa</p>
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
