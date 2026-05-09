<?php

require_once 'db.php';
require_once __DIR__ . '/config/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sales.php');
    exit;
}

$clienteId = (int) ($_POST['cliente_id'] ?? 0);
$productoId = (int) ($_POST['producto_id'] ?? 0);
$cantidad = (int) ($_POST['cantidad'] ?? 0);

if ($clienteId <= 0 || $productoId <= 0 || $cantidad <= 0) {
    die('Completa cliente y producto.');
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
    die('Producto no encontrado.');
}

if ((int) $stockActual < $cantidad) {
    mysqli_rollback($conn);
    die('Stock insuficiente para completar la venta.');
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
    header('Location: sales.php?success=1');
    exit;
}

mysqli_stmt_close($stmt);
mysqli_rollback($conn);

die('Error al registrar la venta.');

?>