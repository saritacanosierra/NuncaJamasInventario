<?php

/**
 * Una venta cobrada en WooCommerce baja el mismo stock de esta app.
 * El código de barras es el SKU. Con stock 0 la tienda oculta la prenda y no la borra.
 */
class WordpressInventario {
    public static function pedidoCobrado(array $pedido) {
        $estado = strtolower(trim((string) ($pedido['status'] ?? '')));
        if ($estado !== 'processing' && $estado !== 'completed') {
            return false;
        }
        $pagadoEn = trim((string) ($pedido['date_paid'] ?? ''));
        $pasarela = trim((string) ($pedido['payment_method'] ?? ''));
        return $pagadoEn !== '' && $pasarela !== '';
    }

    public static function inventario(PDO $db) {
        $stmt = $db->query(
            "SELECT id, codigo_barras, nombre, stock, estado
             FROM productos
             WHERE codigo_barras <> '' AND estado <> 'Vendido'
             ORDER BY codigo_barras"
        );
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $lista[(string) $fila['codigo_barras']] = [
                'id' => (int) $fila['id'],
                'codigo_barras' => (string) $fila['codigo_barras'],
                'nombre' => (string) $fila['nombre'],
                'stock' => (int) $fila['stock'],
                'estado' => (string) $fila['estado'],
            ];
        }
        return $lista;
    }

    public static function tiendaDesdeInventario(array $inventario) {
        $tienda = [];
        foreach ($inventario as $prenda) {
            $sku = trim((string) ($prenda['codigo_barras'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $stock = (int) ($prenda['stock'] ?? 0);
            $tienda[$sku] = self::pieza($sku, (string) ($prenda['nombre'] ?? ''), $stock);
        }
        return $tienda;
    }

    public static function diferencias(array $inventario, array $tienda) {
        $avisos = [];
        foreach ($inventario as $sku => $prenda) {
            if (!isset($tienda[$sku])) {
                $avisos[] = 'Falta en la tienda: ' . $sku;
                continue;
            }
            foreach (self::avisosDePrenda($prenda, $tienda[$sku]) as $aviso) {
                $avisos[] = $aviso;
            }
        }
        foreach ($tienda as $sku => $pieza) {
            if (!isset($inventario[$sku])) {
                $avisos[] = 'La tienda tiene una prenda que no está en el inventario: ' . $sku;
            }
        }
        return $avisos;
    }

    public static function reflejarVenta(PDO $db, array &$tienda, array $pedido, array &$aplicados) {
        if (!self::pedidoCobrado($pedido)) {
            return ['ok' => true, 'aplicado' => false, 'motivo' => 'sin_cobro'];
        }
        $id = (int) ($pedido['id'] ?? 0);
        if ($id < 1) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'sin_id'];
        }
        if (isset($aplicados[$id])) {
            return ['ok' => true, 'aplicado' => false, 'motivo' => 'ya_aplicado'];
        }
        $lineas = $pedido['line_items'] ?? [];
        if (!is_array($lineas) || $lineas === []) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'sin_prendas'];
        }

        $producto = new Producto($db);
        $plan = [];
        foreach ($lineas as $linea) {
            $preparada = self::prepararLinea($db, $producto, $tienda, $linea);
            if (!$preparada['ok']) {
                return $preparada;
            }
            $clave = $preparada['producto_id'] . ':' . $preparada['talla_id'];
            if (!isset($plan[$clave])) {
                $plan[$clave] = $preparada;
                $plan[$clave]['cantidad'] = 0;
            }
            $plan[$clave]['cantidad'] += $preparada['cantidad'];
        }
        $porSku = [];
        foreach ($plan as $paso) {
            if ($paso['disponible'] < $paso['cantidad']) {
                return ['ok' => false, 'aplicado' => false, 'motivo' => 'sin_stock', 'sku' => $paso['sku']];
            }
            if (!isset($porSku[$paso['sku']])) {
                $porSku[$paso['sku']] = 0;
            }
            $porSku[$paso['sku']] += $paso['cantidad'];
        }
        foreach ($porSku as $sku => $cantidad) {
            if ((int) $tienda[$sku]['stock_quantity'] < $cantidad) {
                return ['ok' => false, 'aplicado' => false, 'motivo' => 'sin_stock', 'sku' => $sku];
            }
        }

        foreach ($plan as $paso) {
            $producto->bajarStock($paso['producto_id'], $paso['talla_id'], $paso['cantidad']);
            $queda = (int) $tienda[$paso['sku']]['stock_quantity'] - $paso['cantidad'];
            $tienda[$paso['sku']]['stock_quantity'] = $queda;
            $tienda[$paso['sku']]['catalog_visibility'] = $queda > 0 ? 'visible' : 'hidden';
            $tienda[$paso['sku']]['status'] = 'publish';
        }
        $aplicados[$id] = true;
        return ['ok' => true, 'aplicado' => true, 'motivo' => ''];
    }

    private static function prepararLinea(PDO $db, Producto $producto, array $tienda, array $linea) {
        $sku = trim((string) ($linea['sku'] ?? ''));
        $cantidad = (int) ($linea['quantity'] ?? 0);
        if ($sku === '' || $cantidad < 1) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'linea_invalida'];
        }
        if (!isset($tienda[$sku])) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'no_esta_en_la_tienda', 'sku' => $sku];
        }
        $fila = $producto->getByCodigoBarras($sku);
        if (!$fila) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'sku_desconocido', 'sku' => $sku];
        }
        try {
            $tallaId = self::tallaParaLinea($fila['tallas'] ?? [], $linea);
        } catch (Exception $e) {
            return ['ok' => false, 'aplicado' => false, 'motivo' => 'talla', 'sku' => $sku];
        }
        return [
            'ok' => true,
            'producto_id' => (int) $fila['id'],
            'talla_id' => $tallaId,
            'sku' => $sku,
            'cantidad' => $cantidad,
            'disponible' => self::stockDe($db, (int) $fila['id'], $tallaId),
            'en_tienda' => (int) $tienda[$sku]['stock_quantity'],
        ];
    }

    private static function tallaParaLinea(array $tallas, array $linea) {
        if (count($tallas) === 0) {
            return 0;
        }
        if (count($tallas) === 1) {
            return (int) $tallas[0]['id'];
        }
        $buscada = '';
        $metas = $linea['meta_data'] ?? [];
        if (is_array($metas)) {
            foreach ($metas as $meta) {
                $clave = strtolower(trim((string) ($meta['key'] ?? '')));
                if ($clave === 'talla' || $clave === 'pa_talla' || $clave === 'attribute_pa_talla') {
                    $buscada = trim((string) ($meta['value'] ?? ''));
                }
            }
        }
        if ($buscada === '') {
            throw new Exception('talla');
        }
        foreach ($tallas as $talla) {
            if (strcasecmp((string) $talla['talla'], $buscada) === 0) {
                return (int) $talla['id'];
            }
        }
        throw new Exception('talla');
    }

    private static function stockDe(PDO $db, $productoId, $tallaId) {
        if ($tallaId > 0) {
            $stmt = $db->prepare('SELECT stock FROM producto_tallas WHERE id = :id AND producto_id = :producto_id');
            $stmt->execute([':id' => $tallaId, ':producto_id' => $productoId]);
            return (int) $stmt->fetchColumn();
        }
        $stmt = $db->prepare('SELECT stock FROM productos WHERE id = :id');
        $stmt->execute([':id' => $productoId]);
        return (int) $stmt->fetchColumn();
    }

    private static function pieza($sku, $nombre, $stock) {
        return [
            'sku' => $sku,
            'name' => $nombre,
            'stock_quantity' => (int) $stock,
            'catalog_visibility' => $stock > 0 ? 'visible' : 'hidden',
            'status' => 'publish',
        ];
    }

    private static function avisosDePrenda(array $prenda, array $pieza) {
        $avisos = [];
        $sku = (string) $prenda['codigo_barras'];
        if ((string) ($pieza['status'] ?? '') === 'trash') {
            $avisos[] = 'La tienda borró la prenda ' . $sku;
        }
        if ((string) ($pieza['name'] ?? '') !== (string) $prenda['nombre']) {
            $avisos[] = 'El nombre no coincide en ' . $sku;
        }
        if ((int) ($pieza['stock_quantity'] ?? -1) !== (int) $prenda['stock']) {
            $avisos[] = 'El stock no coincide en ' . $sku;
        }
        $visible = (string) ($pieza['catalog_visibility'] ?? '');
        if ((int) $prenda['stock'] > 0 && $visible !== 'visible') {
            $avisos[] = 'La tienda oculta una prenda que aún tiene stock: ' . $sku;
        }
        if ((int) $prenda['stock'] <= 0 && $visible !== 'hidden') {
            $avisos[] = 'La tienda sigue mostrando una prenda agotada: ' . $sku;
        }
        return $avisos;
    }
}
