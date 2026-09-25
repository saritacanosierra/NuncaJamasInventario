<?php
/**
 * Simula una tienda WooCommerce con el mismo inventario de la app.
 * Un pedido cobrado por pasarela baja el stock aquí y en la tienda.
 * Un pedido sin pago no mueve nada. Al agotarse, la prenda sigue en la tienda, oculta.
 * Al terminar revierte la transacción: no deja ventas ni cambia el inventario.
 *
 * Uso: php docs/prueba_wordpress.php
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

function pedidoSimulado($id, $estado, $sku, $cantidad, $talla, $cobrado) {
    $linea = [
        'sku' => $sku,
        'quantity' => $cantidad,
        'name' => 'Prenda de simulación',
    ];
    if ($talla !== '') {
        $linea['meta_data'] = [['key' => 'pa_talla', 'value' => $talla]];
    }
    return [
        'id' => $id,
        'status' => $estado,
        'payment_method' => 'woo-mercado-pago-basic',
        'payment_method_title' => 'Mercado Pago',
        'date_paid' => $cobrado ? '2026-09-25T16:00:00' : '',
        'line_items' => [$linea],
    ];
}

$db = (new Database())->getConnection();
$ventasAntes = (int) $db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
$inventarioInicial = WordpressInventario::inventario($db);
$stockAntes = [];
foreach ($inventarioInicial as $sku => $prenda) {
    $stockAntes[$sku] = $prenda['stock'];
}

$elegida = null;
$talla = '';
$respaldo = null;
$tallaRespaldo = '';
$producto = new Producto($db);
foreach ($inventarioInicial as $prenda) {
    if ($prenda['stock'] < 1) {
        continue;
    }
    $fila = $producto->getByCodigoBarras($prenda['codigo_barras']);
    $tallas = $fila['tallas'] ?? [];
    if (count($tallas) <= 1) {
        $elegida = $prenda;
        break;
    }
    if ($respaldo !== null) {
        continue;
    }
    foreach ($tallas as $una) {
        if ((int) $una['stock'] >= 1) {
            $respaldo = $prenda;
            $tallaRespaldo = (string) $una['talla'];
            break;
        }
    }
}
if ($elegida === null && $respaldo !== null) {
    $elegida = $respaldo;
    $talla = $tallaRespaldo;
}

espera($elegida !== null, 'No hay una prenda con stock para simular la venta de WordPress.', $fallos);

if ($elegida !== null) {
    $sku = $elegida['codigo_barras'];
    $stockInicial = (int) $elegida['stock'];
    $db->beginTransaction();
    try {
        $tienda = WordpressInventario::tiendaDesdeInventario(WordpressInventario::inventario($db));
        $aplicados = [];
        espera(
            WordpressInventario::diferencias(WordpressInventario::inventario($db), $tienda) === [],
            'Al empezar, la tienda simulada no coincide con el inventario.',
            $fallos
        );

        $pendiente = pedidoSimulado(91001, 'pending', $sku, 1, $talla, false);
        $sinCobro = WordpressInventario::reflejarVenta($db, $tienda, $pendiente, $aplicados);
        espera($sinCobro['aplicado'] === false && $sinCobro['motivo'] === 'sin_cobro', 'Un pedido pendiente de la pasarela no debió descontar.', $fallos);

        $fallido = pedidoSimulado(91002, 'failed', $sku, 1, $talla, true);
        $rechazoPago = WordpressInventario::reflejarVenta($db, $tienda, $fallido, $aplicados);
        espera($rechazoPago['motivo'] === 'sin_cobro', 'Un pago fallido no debió descontar.', $fallos);
        espera(
            (int) WordpressInventario::inventario($db)[$sku]['stock'] === $stockInicial,
            'Un pedido sin cobro cambió el stock.',
            $fallos
        );

        $ajena = pedidoSimulado(91003, 'processing', 'SKU-QUE-NO-EXISTE', 1, '', true);
        $desconocida = WordpressInventario::reflejarVenta($db, $tienda, $ajena, $aplicados);
        espera($desconocida['motivo'] === 'no_esta_en_la_tienda', 'Una prenda que no está en la tienda no debió tocar el inventario.', $fallos);

        $cobrado = pedidoSimulado(91004, 'processing', $sku, 1, $talla, true);
        $venta = WordpressInventario::reflejarVenta($db, $tienda, $cobrado, $aplicados);
        espera($venta['ok'] === true && $venta['aplicado'] === true, 'La venta cobrada por la pasarela no se reflejó.', $fallos);
        $trasVenta = WordpressInventario::inventario($db)[$sku];
        espera((int) $trasVenta['stock'] === $stockInicial - 1, 'La venta de WordPress no bajó el stock en 1.', $fallos);
        espera(
            WordpressInventario::diferencias(WordpressInventario::inventario($db), $tienda) === [],
            'Después de la venta, la tienda dejó de coincidir con el inventario.',
            $fallos
        );

        $repetida = WordpressInventario::reflejarVenta($db, $tienda, $cobrado, $aplicados);
        espera($repetida['motivo'] === 'ya_aplicado', 'El mismo pedido se aplicó dos veces.', $fallos);
        espera(
            (int) WordpressInventario::inventario($db)[$sku]['stock'] === $stockInicial - 1,
            'Repetir el aviso de la pasarela volvió a bajar el stock.',
            $fallos
        );

        $queda = (int) WordpressInventario::inventario($db)[$sku]['stock'];
        if ($talla !== '') {
            $filaTalla = $producto->getByCodigoBarras($sku);
            foreach ($filaTalla['tallas'] ?? [] as $una) {
                if (strcasecmp((string) $una['talla'], $talla) === 0) {
                    $queda = (int) $una['stock'];
                }
            }
        }
        if ($queda > 0) {
            $agotado = pedidoSimulado(91005, 'completed', $sku, $queda, $talla, true);
            $cierre = WordpressInventario::reflejarVenta($db, $tienda, $agotado, $aplicados);
            espera($cierre['aplicado'] === true, 'No se pudo simular el agotado de la prenda.', $fallos);
        }
        $finalPrenda = WordpressInventario::inventario($db)[$sku];
        espera((int) $finalPrenda['stock'] === 0, 'La prenda no quedó en cero después de vender todo.', $fallos);
        espera($finalPrenda['estado'] === 'Agotado', 'La prenda agotada no quedó marcada en el inventario.', $fallos);
        espera(isset($tienda[$sku]), 'Al agotarse, la prenda desapareció de la tienda.', $fallos);
        espera($tienda[$sku]['catalog_visibility'] === 'hidden', 'La prenda agotada siguió visible en la tienda.', $fallos);
        espera($tienda[$sku]['status'] === 'publish', 'La prenda agotada se borró de la tienda.', $fallos);
        espera(
            WordpressInventario::diferencias(WordpressInventario::inventario($db), $tienda) === [],
            'Con la prenda oculta, la tienda no coincide con el inventario.',
            $fallos
        );

        $visibleDeMas = $tienda;
        $visibleDeMas[$sku]['catalog_visibility'] = 'visible';
        $avisosVisible = WordpressInventario::diferencias(WordpressInventario::inventario($db), $visibleDeMas);
        espera($avisosVisible !== [], 'Mostrar una prenda agotada no se detectó.', $fallos);

        $sinPrenda = $tienda;
        unset($sinPrenda[$sku]);
        espera(
            WordpressInventario::diferencias(WordpressInventario::inventario($db), $sinPrenda) !== [],
            'Borrar la prenda de la tienda no se detectó.',
            $fallos
        );

        $deMas = $tienda;
        $deMas['SKU-SOLO-TIENDA'] = [
            'sku' => 'SKU-SOLO-TIENDA',
            'name' => 'Solo en la web',
            'stock_quantity' => 3,
            'catalog_visibility' => 'visible',
            'status' => 'publish',
        ];
        espera(
            WordpressInventario::diferencias(WordpressInventario::inventario($db), $deMas) !== [],
            'Una prenda que solo está en la tienda no se detectó.',
            $fallos
        );

        $exceso = pedidoSimulado(91006, 'processing', $sku, 1, $talla, true);
        $sinStock = WordpressInventario::reflejarVenta($db, $tienda, $exceso, $aplicados);
        espera($sinStock['motivo'] === 'sin_stock', 'Una venta sin stock debió rechazarse.', $fallos);
        espera((int) WordpressInventario::inventario($db)[$sku]['stock'] === 0, 'El rechazo cambió el stock.', $fallos);
    } catch (Exception $e) {
        $fallos[] = 'La simulación se detuvo: ' . $e->getMessage();
    }
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    $inventarioFinal = WordpressInventario::inventario($db);
    foreach ($stockAntes as $codigo => $stock) {
        $actual = isset($inventarioFinal[$codigo]) ? $inventarioFinal[$codigo]['stock'] : null;
        espera($actual === $stock, 'El stock de ' . $codigo . ' quedó distinto después de revertir la simulación.', $fallos);
    }
}

$ventasDespues = (int) $db->query('SELECT COUNT(*) FROM ventas')->fetchColumn();
espera($ventasDespues === $ventasAntes, 'La simulación creó una factura.', $fallos);

if ($fallos) {
    foreach ($fallos as $fallo) {
        fwrite(STDERR, $fallo . PHP_EOL);
    }
    exit(1);
}

echo "Venta de WordPress, stock y catálogo coinciden. No se guardó ninguna venta.\n";
exit(0);
