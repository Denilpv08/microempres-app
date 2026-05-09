<?php

require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/ui.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ui_flash_set('error', 'Método no permitido.');
    header('Location: sales.php');
    exit;
}

$clienteId = (int) ($_POST['cliente_id'] ?? 0);
$productoId = (int) ($_POST['producto_id'] ?? 0);
$cantidad = (int) ($_POST['cantidad'] ?? 0);

if ($clienteId <= 0 || $productoId <= 0 || $cantidad <= 0) {
    ui_flash_set('error', 'Completa todos los campos para registrar la venta.');
    header('Location: sales.php');
    exit;
}

mysqli_begin_transaction($conn);

$stockStmt = mysqli_prepare($conn, 'SELECT stock, precio FROM productos WHERE id = ? LIMIT 1 FOR UPDATE');
mysqli_stmt_bind_param($stockStmt, 'i', $productoId);
mysqli_stmt_execute($stockStmt);
mysqli_stmt_bind_result($stockStmt, $stockActual, $precioUnitario);
$hasProduct = mysqli_stmt_fetch($stockStmt);
mysqli_stmt_close($stockStmt);

if (!$hasProduct) {
    mysqli_rollback($conn);
    ui_flash_set('error', 'Producto no encontrado.');
    header('Location: sales.php');
    exit;
}

if ((int) $stockActual < $cantidad) {
    mysqli_rollback($conn);
    ui_flash_set('error', 'Stock insuficiente para completar la venta.');
    header('Location: sales.php');
    exit;
}

$total = round(((float) $precioUnitario) * $cantidad, 2);

$stmt = mysqli_prepare($conn, 'INSERT INTO ventas (cliente_id, producto_id, cantidad, total) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iiid', $clienteId, $productoId, $cantidad, $total);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    $updateStockStmt = mysqli_prepare($conn, 'UPDATE productos SET stock = stock - ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStockStmt, 'ii', $cantidad, $productoId);
    mysqli_stmt_execute($updateStockStmt);
    mysqli_stmt_close($updateStockStmt);

    mysqli_commit($conn);
    ui_flash_set('success', 'Venta registrada correctamente.');
    header('Location: sales.php');
    exit;
}

mysqli_stmt_close($stmt);
mysqli_rollback($conn);

ui_flash_set('error', 'Error al registrar la venta. Intenta de nuevo.');
header('Location: sales.php');
exit;
