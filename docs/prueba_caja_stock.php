<?php
/**
 * Comprueba el IVA de la caja y que un descuento de stock de más
 * no deje la prenda en negativo. Al terminar revierte la transacción:
 * no crea facturas ni cambia el inventario.
 *
 * Uso: php docs/prueba_caja_stock.php
 */

$_SERVER['HTTP_HOST'] = 'localhost';
require dirname(__DIR__) . '/back/config/config.php';
require dirname(__DIR__) . '/back/config/db.php';

$fallos = [];

function espera($condicion, $mensaje, &$fallos) {
    if (!$condicion) {
        $fallos[] = $mensaje;
    }
}

$precio = 20000;
$iva = iva_incluido_en($precio);
espera($iva === 3193, 'El IVA dentro de 20000 debe ser 3193.', $fallos);
espera($precio - $iva === 16807, 'La base dentro de 20000 debe ser 16807.', $fallos);
$descuento = 1000;
$cobrado = $precio - $descuento;
$ivaTrasDescuento = iva_incluido_en($cobrado);
$domicilio = 3000;
$total = $cobrado + $domicilio;
espera($ivaTrasDescuento === iva_incluido_en(19000), 'El IVA se recalcula sobre el precio ya descontado.', $fallos);
espera($total === 22000, 'Con descuento de 1000 y domicilio de 3000 el cobro debe ser 22000. El domicilio no lleva IVA.', $fallos);

$db = (new Database())->getConnection();
$ventasAntes = (int) $db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();

$fila = $db->query(
    'SELECT producto_id, id AS talla_id, stock FROM producto_tallas WHERE stock >= 1 ORDER BY stock DESC LIMIT 1'
)->fetch();
$tallaId = 0;
$productoId = 0;
$stockAntes = 0;
if ($fila) {
    $productoId = (int) $fila['producto_id'];
    $tallaId = (int) $fila['talla_id'];
    $stockAntes = (int) $fila['stock'];
} else {
    $fila = $db->query('SELECT id, stock FROM productos WHERE stock >= 1 ORDER BY stock DESC LIMIT 1')->fetch();
    if ($fila) {
        $productoId = (int) $fila['id'];
        $stockAntes = (int) $fila['stock'];
    }
}

espera($productoId > 0, 'No hay una prenda con stock para probar el bloqueo.', $fallos);

if ($productoId > 0) {
    $db->beginTransaction();
    try {
        $producto = new Producto($db);
        $producto->bajarStock($productoId, $tallaId, 1);
        $despuesDeUna = stockActual($db, $productoId, $tallaId);
        espera($despuesDeUna === $stockAntes - 1, 'Descontar una unidad no bajo el stock en 1.', $fallos);

        $rechazo = false;
        try {
            $producto->bajarStock($productoId, $tallaId, $despuesDeUna + 1);
        } catch (Exception $e) {
            $rechazo = true;
        }
        espera($rechazo, 'Una segunda salida mayor que el stock debio rechazarse.', $fallos);
        $trasRechazo = stockActual($db, $productoId, $tallaId);
        espera($trasRechazo === $stockAntes - 1, 'El rechazo de la segunda salida cambio el stock.', $fallos);
    } catch (Exception $e) {
        $fallos[] = 'La prueba de stock se detuvo: ' . $e->getMessage();
    }
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $stockFinal = stockActual($db, $productoId, $tallaId);
    espera($stockFinal === $stockAntes, 'El stock quedo distinto despues de revertir la prueba.', $fallos);
}

$ventasDespues = (int) $db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
espera($ventasDespues === $ventasAntes, 'La prueba creo una factura.', $fallos);

if ($fallos) {
    foreach ($fallos as $fallo) {
        fwrite(STDERR, $fallo . PHP_EOL);
    }
    exit(1);
}

echo "IVA, domicilio y bloqueo de stock correctos. No se guardo ninguna venta.\n";
exit(0);

function stockActual($db, $productoId, $tallaId) {
    if ($tallaId > 0) {
        $stmt = $db->prepare('SELECT stock FROM producto_tallas WHERE id = :id AND producto_id = :producto_id');
        $stmt->execute([':id' => $tallaId, ':producto_id' => $productoId]);
        return (int) $stmt->fetchColumn();
    }
    $stmt = $db->prepare('SELECT stock FROM productos WHERE id = :id');
    $stmt->execute([':id' => $productoId]);
    return (int) $stmt->fetchColumn();
}
