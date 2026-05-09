<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

$flash = ui_flash_get();

$clientes = [];
$result = mysqli_query($conn, 'SELECT id, nombre FROM clientes ORDER BY nombre ASC');
if ($result) {
  while ($row = mysqli_fetch_assoc($result)) {
    $clientes[] = $row;
  }
}

$productos = [];
$resultProductos = mysqli_query($conn, 'SELECT id, nombre, stock, precio FROM productos WHERE stock > 0 ORDER BY nombre ASC');
if ($resultProductos) {
  while ($row = mysqli_fetch_assoc($resultProductos)) {
    $productos[] = $row;
  }
}

$canSave = count($clientes) > 0 && count($productos) > 0;
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ventas</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <script defer src="assets/js/ui.js"></script>
  </head>
  <body>
    <?php require_once __DIR__ . '/config/sidebar.php'; ?>

      <?php if ($flash) { ?>
      <div id="page-flash" class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" data-message="1">
        <strong>Notificación</strong>
        <p><?php echo htmlspecialchars($flash['message']); ?></p>
      </div>
      <?php } ?>

      <div class="page-header">
        <div>
          <p class="eyebrow">Modulo de ventas</p>
          <h1>Registro de Ventas</h1>
        </div>
      </div>

      <form action="php.php" method="POST" data-loading-form>
        <select name="cliente_id" required>
          <option value="">Selecciona un cliente</option>
          <?php foreach ($clientes as $cliente) { ?>
          <option value="<?php echo (int) $cliente['id']; ?>"><?php echo htmlspecialchars($cliente['nombre']); ?></option>
          <?php } ?>
        </select>
        <select name="producto_id" required>
          <option value="">Selecciona un producto</option>
          <?php foreach ($productos as $producto) { ?>
          <option value="<?php echo (int) $producto['id']; ?>" data-precio="<?php echo htmlspecialchars((string) $producto['precio']); ?>" data-stock="<?php echo (int) $producto['stock']; ?>"><?php echo htmlspecialchars($producto['nombre']); ?> </option>
          <?php } ?>
        </select>
        <p id="precioUnitarioPreview" class="helper-text">Precio unitario (con IVA): $0.00</p>
        <p id="precioSinIvaPreview" class="helper-text">Precio unitario (sin IVA): $0.00</p>
        <p id="stockDisponiblePreview" class="helper-text">Stock disponible: 0</p>
        <input type="number" name="cantidad" placeholder="Cantidad" min="1" required />
        <p id="stockErrorPreview" class="helper-text helper-error" aria-live="polite"></p>
        <div class="total-breakdown">
          <p>Subtotal (sin IVA): <strong id="subtotalPreview">$0.00</strong></p>
          <p>IVA 19%: <strong id="ivaPreview">$0.00</strong></p>
          <p>Total (con IVA): <strong id="totalPreview">$0.00</strong></p>
        </div>
        <button type="submit" <?php echo $canSave ? '' : 'disabled'; ?>>Guardar Venta</button>
      </form>

      <?php if (count($clientes) === 0) { ?>
      <p class="status-empty">No hay clientes registrados. Primero registra clientes para poder crear ventas.</p>
      <?php } ?>

      <?php if (count($productos) === 0) { ?>
      <p class="status-empty">No hay productos con stock disponible para vender.</p>
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

    <script>
      (function () {
        const IVA_RATE = 0.19;

        const productoSelect = document.querySelector('select[name="producto_id"]');
        const cantidadInput = document.querySelector('input[name="cantidad"]');
        const totalPreview = document.getElementById('totalPreview');
        const precioUnitarioPreview = document.getElementById('precioUnitarioPreview');
        const precioSinIvaPreview = document.getElementById('precioSinIvaPreview');
        const stockDisponiblePreview = document.getElementById('stockDisponiblePreview');
        const stockErrorPreview = document.getElementById('stockErrorPreview');
        const subtotalPreview = document.getElementById('subtotalPreview');
        const ivaPreview = document.getElementById('ivaPreview');
        const form = document.querySelector('form[action="php.php"]');
        const submitButton = form ? form.querySelector('button[type="submit"]') : null;

        if (!productoSelect || !cantidadInput || !totalPreview || !precioUnitarioPreview || !precioSinIvaPreview || !stockDisponiblePreview || !stockErrorPreview || !subtotalPreview || !ivaPreview || !form) {
          return;
        }

        function formatCOP(value) {
          return '$' + Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function recalcularTotalYValidar() {
          const selectedOption = productoSelect.options[productoSelect.selectedIndex];
          const precioConIva = Number(selectedOption?.dataset?.precio || 0);
          const stock = Number(selectedOption?.dataset?.stock || 0);
          const cantidad = Number(cantidadInput.value || 0);

          const precioSinIva = precioConIva / (1 + IVA_RATE);
          const subtotal = precioConIva > 0 && cantidad > 0 ? precioSinIva * cantidad : 0;
          const iva = subtotal * IVA_RATE;
          const total = precioConIva > 0 && cantidad > 0 ? precioConIva * cantidad : 0;

          cantidadInput.max = stock > 0 ? String(stock) : '';
          precioUnitarioPreview.textContent = `Precio unitario (con IVA): ${formatCOP(precioConIva)}`;
          precioSinIvaPreview.textContent = `Precio unitario (sin IVA): ${formatCOP(precioSinIva)}`;
          stockDisponiblePreview.textContent = `Stock disponible: ${stock}`;
          subtotalPreview.textContent = formatCOP(subtotal);
          ivaPreview.textContent = formatCOP(iva);
          totalPreview.textContent = formatCOP(total);

          if (stock > 0 && cantidad > stock) {
            const msg = `La cantidad no puede superar el stock disponible (${stock}).`;
            cantidadInput.setCustomValidity(msg);
            stockErrorPreview.textContent = msg;
            if (submitButton) {
              submitButton.disabled = true;
            }
          } else {
            cantidadInput.setCustomValidity('');
            stockErrorPreview.textContent = '';
            if (submitButton && <?php echo $canSave ? 'true' : 'false'; ?>) {
              submitButton.disabled = false;
            }
          }
        }

        productoSelect.addEventListener('change', recalcularTotalYValidar);
        cantidadInput.addEventListener('input', recalcularTotalYValidar);
        form.addEventListener('submit', function (event) {
          recalcularTotalYValidar();
          if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
          }
        });

        recalcularTotalYValidar();
      })();
    </script>
  </body>
</html>
