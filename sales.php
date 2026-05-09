<?php
require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_login();

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
  </head>
  <body>
    <div class="container panel">
      <div class="page-header">
        <div>
          <p class="eyebrow">Modulo de ventas</p>
          <h1>Registro de Ventas</h1>
        </div>
        
        <div class="view-actions">
          <a class="button-link secondary" href="dashboard.php">Volver</a>
        </div>
      </div>
      <form action="php.php" method="POST">
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
        <p id="precioUnitarioPreview" class="helper-text">Precio unitario: $0.00</p>
        <p id="stockDisponiblePreview" class="helper-text">Stock disponible: 0</p>
        <input type="number" name="cantidad" placeholder="Cantidad" min="1" required />
        <p id="stockErrorPreview" class="helper-text helper-error" aria-live="polite"></p>
        <input type="number" id="totalPreview" placeholder="Total calculado" min="0" step="0.01" readonly />
        <button type="submit" <?php echo $canSave ? '' : 'disabled'; ?>>Guardar Venta</button>
      </form>

      <?php if (count($clientes) === 0) { ?>
      <p class="status-empty">No hay clientes registrados. Primero registra clientes para poder crear ventas.</p>
      <?php } ?>

      <?php if (count($productos) === 0) { ?>
      <p class="status-empty">No hay productos con stock disponible para vender.</p>
      <?php } ?>

      
    </div>

    <script>
      (function () {
        const productoSelect = document.querySelector('select[name="producto_id"]');
        const cantidadInput = document.querySelector('input[name="cantidad"]');
        const totalPreview = document.getElementById('totalPreview');
        const precioUnitarioPreview = document.getElementById('precioUnitarioPreview');
        const stockDisponiblePreview = document.getElementById('stockDisponiblePreview');
        const stockErrorPreview = document.getElementById('stockErrorPreview');
        const form = document.querySelector('form[action="php.php"]');
        const submitButton = form ? form.querySelector('button[type="submit"]') : null;

        if (!productoSelect || !cantidadInput || !totalPreview || !precioUnitarioPreview || !stockDisponiblePreview || !stockErrorPreview || !form) {
          return;
        }

        function recalcularTotalYValidar() {
          const selectedOption = productoSelect.options[productoSelect.selectedIndex];
          const precio = Number(selectedOption?.dataset?.precio || 0);
          const stock = Number(selectedOption?.dataset?.stock || 0);
          const cantidad = Number(cantidadInput.value || 0);
          const total = precio > 0 && cantidad > 0 ? precio * cantidad : 0;

          cantidadInput.max = stock > 0 ? String(stock) : '';
          precioUnitarioPreview.textContent = `Precio unitario: $${precio.toFixed(2)}`;
          stockDisponiblePreview.textContent = `Stock disponible: ${stock}`;
          totalPreview.value = total.toFixed(2);

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
